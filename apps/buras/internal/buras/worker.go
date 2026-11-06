package buras

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net"
	"net/http"
	"time"
)

type Worker struct {
	Store       *Store
	Config      Config
	Client      *http.Client
	AlertClient *http.Client
	Logger      *slog.Logger
}

func (w *Worker) Run(ctx context.Context) {
	poll := time.NewTicker(time.Second)
	defer poll.Stop()
	probe := time.NewTicker(w.Config.ProbeInterval)
	defer probe.Stop()
	prune := time.NewTicker(24 * time.Hour)
	defer prune.Stop()
	if w.Config.ActiveEnabled {
		if err := w.Store.EnqueueProbe(ctx); err != nil {
			w.Logger.Error("enqueue probe", "error", err)
		}
	}
	for {
		select {
		case <-ctx.Done():
			return
		case <-probe.C:
			if w.Config.ActiveEnabled {
				if err := w.Store.EnqueueProbe(ctx); err != nil {
					w.Logger.Error("enqueue probe", "error", err)
				}
			}
		case <-prune.C:
			if err := w.Store.Prune(ctx, w.Config.RetentionDays); err != nil {
				w.Logger.Error("prune events", "error", err)
			}
		case <-poll.C:
			for i := 0; i < 20; i++ {
				ok, err := w.ProcessOnce(ctx)
				if err != nil {
					w.Logger.Error("process check", "error", err)
					break
				}
				if !ok {
					break
				}
			}
		}
	}
}

func (w *Worker) ProcessOnce(ctx context.Context) (bool, error) {
	j, err := w.Store.ClaimJob(ctx)
	if errors.Is(err, sql.ErrNoRows) {
		return false, nil
	}
	if err != nil {
		return false, err
	}
	var check Check
	switch j.Kind {
	case "passive":
		e, err := w.Store.EventByID(ctx, j.EventID.String)
		if err != nil {
			w.Store.FailJob(ctx, j.ID)
			return true, err
		}
		check = Check{Layer: "passive", Name: "integration_event", Environment: e.Environment, Provider: e.Provider, Operation: e.Operation, Status: "pass", DetailCode: e.Result, OccurredAt: time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z")}
		if !w.Config.PassiveEnabled {
			check.Status = "skipped"
			check.DetailCode = "disabled"
			return true, w.Store.FinishJob(ctx, check, j.ID)
		}
		if isFailure(e.Result) {
			check.Status = "fail"
		}
		if e.DurationMS != nil {
			check.DurationMS = *e.DurationMS
		}
		if err := w.Store.FinishJob(ctx, check, j.ID); err != nil {
			return true, err
		}
		if e.Type == "external_call" {
			w.evaluateAlert(ctx, e)
		}
	case "active":
		if !w.Config.ActiveEnabled {
			check = Check{Layer: "active", Name: "http_reachability", Environment: w.Config.ProbeEnvironment, Provider: "oiai-api", Operation: "health", Status: "skipped", DetailCode: "disabled", OccurredAt: time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z")}
		} else {
			check = w.probe(ctx)
		}
		if err := w.Store.FinishJob(ctx, check, j.ID); err != nil {
			return true, err
		}
	default:
		w.Store.FailJob(ctx, j.ID)
		return true, fmt.Errorf("unknown job kind")
	}
	return true, nil
}

func (w *Worker) probe(parent context.Context) Check {
	now := time.Now()
	c := Check{Layer: "active", Name: "http_reachability", Environment: w.Config.ProbeEnvironment, Provider: "oiai-api", Operation: "health", Status: "fail", DetailCode: "network", OccurredAt: now.UTC().Format("2006-01-02T15:04:05.000000000Z")}
	ctx, cancel := context.WithTimeout(parent, w.Config.ProbeTimeout)
	defer cancel()
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, w.Config.ProbeURL, nil)
	if err != nil {
		c.DetailCode = "configuration"
		return c
	}
	client := w.Client
	if client == nil {
		client = &http.Client{Timeout: w.Config.ProbeTimeout, CheckRedirect: func(*http.Request, []*http.Request) error { return http.ErrUseLastResponse }}
	}
	res, err := client.Do(req)
	c.DurationMS = float64(time.Since(now).Microseconds()) / 1000
	if err != nil {
		var netErr net.Error
		if errors.Is(err, context.DeadlineExceeded) || (errors.As(err, &netErr) && netErr.Timeout()) {
			c.DetailCode = "timeout"
		}
		return c
	}
	defer res.Body.Close()
	if res.StatusCode >= 200 && res.StatusCode < 300 {
		c.Status = "pass"
		c.DetailCode = "http_2xx"
	} else {
		c.DetailCode = "http_non_2xx"
	}
	return c
}

func (w *Worker) evaluateAlert(ctx context.Context, e Event) {
	if !isFailure(e.Result) {
		if err := w.Store.ResolveAlert(ctx, e.Environment, e.Provider, e.Operation); err != nil {
			w.Logger.Error("resolve alert", "error", err)
		}
		return
	}
	failures, err := w.Store.RecentFailures(ctx, e.Environment, e.Provider, e.Operation, time.Now().Add(-w.Config.AlertWindow), w.Config.AlertFailures)
	if err != nil {
		w.Logger.Error("evaluate alert", "error", err)
		return
	}
	if failures < w.Config.AlertFailures {
		return
	}
	notify, err := w.Store.UpsertAlert(ctx, e.Environment, e.Provider, e.Operation, w.Config.AlertCooldown)
	if err != nil {
		w.Logger.Error("save alert", "error", err)
		return
	}
	if !notify {
		return
	}
	w.Logger.Warn("integration alert", "environment", e.Environment, "provider", e.Provider, "operation", e.Operation, "failures", failures)
	if w.Config.AlertWebhookURL == "" {
		_ = w.Store.MarkAlertNotified(ctx, e.Environment, e.Provider, e.Operation)
		return
	}
	body, _ := json.Marshal(map[string]any{"event": "buras_alert", "environment": e.Environment, "provider": e.Provider, "operation": e.Operation, "consecutive_failures": failures, "state": "firing"})
	request, err := http.NewRequestWithContext(ctx, http.MethodPost, w.Config.AlertWebhookURL, bytes.NewReader(body))
	if err != nil {
		return
	}
	request.Header.Set("Content-Type", "application/json")
	client := w.AlertClient
	if client == nil {
		client = &http.Client{Timeout: 3 * time.Second, CheckRedirect: func(*http.Request, []*http.Request) error { return http.ErrUseLastResponse }}
	}
	response, err := client.Do(request)
	if err != nil {
		w.Logger.Error("alert delivery failed")
		return
	}
	response.Body.Close()
	if response.StatusCode < 200 || response.StatusCode >= 300 {
		w.Logger.Error("alert delivery rejected", "status", response.StatusCode)
		return
	}
	if err := w.Store.MarkAlertNotified(ctx, e.Environment, e.Provider, e.Operation); err != nil {
		w.Logger.Error("mark alert notified", "error", err)
	}
}
