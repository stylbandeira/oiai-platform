package buras

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"
)

func testSetup(t *testing.T) (*Store, Config, *Worker, http.Handler) {
	t.Helper()
	store, err := OpenStore(t.TempDir() + "/buras.sqlite")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = store.Close() })
	config := Config{IngestToken: strings.Repeat("i", 32), AdminToken: strings.Repeat("a", 32), PassiveEnabled: true, AlertFailures: 5, AlertWindow: time.Hour, AlertCooldown: time.Hour, ProbeInterval: time.Minute, ProbeTimeout: time.Second, ProbeEnvironment: "production", RetentionDays: 30}
	logger := slog.New(slog.NewTextHandler(io.Discard, nil))
	return store, config, &Worker{Store: store, Config: config, Logger: logger}, NewServer(store, config, logger)
}
func eventFor(n int, result string) Event {
	return Event{ID: fmt.Sprintf("00000000-0000-0000-0000-%012d", n), Type: "external_call", OccurredAt: time.Now().UTC().Format(time.RFC3339Nano), Environment: "production", Provider: "oscbr", Operation: "product.lookup", Result: result, CorrelationID: fmt.Sprintf("00000000-0000-0000-0000-%012d", n)}
}
func postEvent(t *testing.T, h http.Handler, token string, e any) *httptest.ResponseRecorder {
	t.Helper()
	b, err := json.Marshal(e)
	if err != nil {
		t.Fatal(err)
	}
	req := httptest.NewRequest(http.MethodPost, "/v1/events", bytes.NewReader(b))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	rr := httptest.NewRecorder()
	h.ServeHTTP(rr, req)
	return rr
}
func TestIngestSanitizationIdempotencyAndDashboard(t *testing.T) {
	store, c, worker, h := testSetup(t)
	e := eventFor(1, "success")
	payload := map[string]any{"event_id": e.ID, "event": e.Type, "occurred_at": e.OccurredAt, "environment": e.Environment, "provider": e.Provider, "operation": e.Operation, "result": e.Result, "correlation_id": e.CorrelationID, "token": "secret-token-sentinel"}
	if rr := postEvent(t, h, c.IngestToken, payload); rr.Code != 400 {
		t.Fatalf("sensitive field accepted: %d", rr.Code)
	}
	if rr := postEvent(t, h, "wrong", e); rr.Code != 401 {
		t.Fatalf("unauthorized ingest: %d", rr.Code)
	}
	if rr := postEvent(t, h, c.IngestToken, e); rr.Code != 202 {
		t.Fatalf("ingest: %d %s", rr.Code, rr.Body.String())
	}
	if rr := postEvent(t, h, c.IngestToken, e); rr.Code != 202 || !strings.Contains(rr.Body.String(), `"accepted":false`) {
		t.Fatalf("duplicate: %d %s", rr.Code, rr.Body.String())
	}
	if ok, err := worker.ProcessOnce(context.Background()); err != nil || !ok {
		t.Fatalf("process: %v", err)
	}
	req := httptest.NewRequest(http.MethodGet, "/v1/summary?environment=production&provider=oscbr", nil)
	req.SetBasicAuth("buras", c.AdminToken)
	rr := httptest.NewRecorder()
	h.ServeHTTP(rr, req)
	if rr.Code != 200 || !strings.Contains(rr.Body.String(), `"calls":1`) {
		t.Fatalf("summary: %d %s", rr.Code, rr.Body.String())
	}
	if events, err := store.ListEvents(context.Background(), "", "", ""); err != nil || len(events) != 1 {
		t.Fatalf("events: %v %d", err, len(events))
	}
	req = httptest.NewRequest(http.MethodGet, "/dashboard", nil)
	rr = httptest.NewRecorder()
	h.ServeHTTP(rr, req)
	if rr.Code != 401 {
		t.Fatalf("unauthorized dashboard: %d", rr.Code)
	}
	req.SetBasicAuth("buras", c.AdminToken)
	rr = httptest.NewRecorder()
	h.ServeHTTP(rr, req)
	if rr.Code != 200 || !strings.Contains(rr.Body.String(), "product.lookup") || strings.Contains(rr.Body.String(), "secret-token-sentinel") {
		t.Fatalf("dashboard: %d", rr.Code)
	}
}
func TestPassiveAlertAndRecovery(t *testing.T) {
	store, c, worker, h := testSetup(t)
	for i := 1; i <= 5; i++ {
		if rr := postEvent(t, h, c.IngestToken, eventFor(i, "timeout")); rr.Code != 202 {
			t.Fatalf("ingest %d: %d", i, rr.Code)
		}
		if _, err := worker.ProcessOnce(context.Background()); err != nil {
			t.Fatal(err)
		}
	}
	alerts, err := store.ListAlerts(context.Background(), "", "")
	if err != nil || len(alerts) != 1 || alerts[0].State != "firing" {
		t.Fatalf("alerts: %#v %v", alerts, err)
	}
	if rr := postEvent(t, h, c.IngestToken, eventFor(6, "success")); rr.Code != 202 {
		t.Fatal(rr.Code)
	}
	if _, err := worker.ProcessOnce(context.Background()); err != nil {
		t.Fatal(err)
	}
	alerts, err = store.ListAlerts(context.Background(), "", "")
	if err != nil || alerts[0].State != "resolved" {
		t.Fatalf("recovery: %#v %v", alerts, err)
	}
	summary, err := store.Summary(context.Background(), "production", "oscbr", time.Now().Add(-time.Hour))
	if err != nil || len(summary) != 1 || summary[0].Failures != 5 || summary[0].Successes != 1 || summary[0].Timeouts != 5 {
		t.Fatalf("summary: %#v %v", summary, err)
	}
}

type roundTripFunc func(*http.Request) (*http.Response, error)

func (f roundTripFunc) RoundTrip(r *http.Request) (*http.Response, error) { return f(r) }

func TestActiveProbe(t *testing.T) {
	store, c, worker, _ := testSetup(t)
	c.ProbeURL = "https://example.invalid/health"
	c.ActiveEnabled = true
	c.ProbeEnvironment = "staging"
	worker.Config = c
	worker.Client = &http.Client{Transport: roundTripFunc(func(r *http.Request) (*http.Response, error) {
		return &http.Response{StatusCode: 503, Body: io.NopCloser(strings.NewReader("")), Header: make(http.Header)}, nil
	})}
	if err := store.EnqueueProbe(context.Background()); err != nil {
		t.Fatal(err)
	}
	if _, err := worker.ProcessOnce(context.Background()); err != nil {
		t.Fatal(err)
	}
	checks, err := store.ListChecks(context.Background(), "staging", "oiai-api")
	if err != nil || len(checks) != 1 || checks[0].Layer != "active" || checks[0].Status != "fail" || checks[0].DetailCode != "http_non_2xx" {
		t.Fatalf("checks: %#v %v", checks, err)
	}
}

func TestAlertWebhookIsDeduplicatedAndSanitized(t *testing.T) {
	store, c, worker, h := testSetup(t)
	c.AlertWebhookURL = "https://alerts.example.test/ingest"
	worker.Config = c
	sent := 0
	worker.AlertClient = &http.Client{Transport: roundTripFunc(func(r *http.Request) (*http.Response, error) {
		sent++
		body, err := io.ReadAll(r.Body)
		if err != nil {
			t.Fatal(err)
		}
		if strings.Contains(string(body), "secret-token-sentinel") || strings.Contains(string(body), "correlation_id") || !strings.Contains(string(body), `"provider":"oscbr"`) {
			t.Fatalf("unsafe alert body: %s", body)
		}
		return &http.Response{StatusCode: 200, Body: io.NopCloser(strings.NewReader("")), Header: make(http.Header)}, nil
	})}
	for i := 1; i <= 6; i++ {
		e := eventFor(i, "timeout")
		failure := "timeout"
		e.FailureType = &failure
		if rr := postEvent(t, h, c.IngestToken, e); rr.Code != 202 {
			t.Fatalf("ingest: %d", rr.Code)
		}
		if _, err := worker.ProcessOnce(context.Background()); err != nil {
			t.Fatal(err)
		}
	}
	if sent != 1 {
		t.Fatalf("alerts sent: %d", sent)
	}
	alerts, err := store.ListAlerts(context.Background(), "", "")
	if err != nil || len(alerts) != 1 || alerts[0].LastNotified == "" {
		t.Fatalf("alert state: %#v %v", alerts, err)
	}
}

func TestExceptionEventWithPHPClass(t *testing.T) {
	_, c, _, h := testSetup(t)
	e := eventFor(1, "failure")
	e.Type = "exception"
	e.Provider = "oiai-api"
	e.Operation = "exception.unhandled"
	class := "App\\Exceptions\\ExampleException"
	e.ErrorClass = &class
	if rr := postEvent(t, h, c.IngestToken, e); rr.Code != 202 {
		t.Fatalf("exception rejected: %d %s", rr.Code, rr.Body.String())
	}
}

func TestConfigurationRejectsMissingTokensAndUnsafeProbe(t *testing.T) {
	t.Setenv("BURAS_INGEST_TOKEN", "")
	t.Setenv("BURAS_ADMIN_TOKEN", "")
	if _, err := ConfigFromEnv(); err == nil {
		t.Fatal("missing tokens accepted")
	}
	t.Setenv("BURAS_INGEST_TOKEN", strings.Repeat("i", 32))
	t.Setenv("BURAS_ADMIN_TOKEN", strings.Repeat("a", 32))
	t.Setenv("BURAS_ACTIVE_ENABLED", "true")
	t.Setenv("BURAS_PROBE_URL", "https://user:password@example.test/health?token=secret")
	if _, err := ConfigFromEnv(); err == nil || strings.Contains(err.Error(), "password") || strings.Contains(err.Error(), "secret") {
		t.Fatalf("unsafe probe error: %v", err)
	}
	t.Setenv("BURAS_PROBE_URL", "https://example.test/health")
	if _, err := ConfigFromEnv(); err != nil {
		t.Fatalf("valid config rejected: %v", err)
	}
}

func TestDisabledLayersSkipPendingJobs(t *testing.T) {
	store, c, worker, h := testSetup(t)
	if rr := postEvent(t, h, c.IngestToken, eventFor(1, "timeout")); rr.Code != 202 {
		t.Fatal(rr.Code)
	}
	c.PassiveEnabled = false
	worker.Config = c
	if _, err := worker.ProcessOnce(context.Background()); err != nil {
		t.Fatal(err)
	}
	checks, err := store.ListChecks(context.Background(), "production", "oscbr")
	if err != nil || len(checks) != 1 || checks[0].Status != "skipped" {
		t.Fatalf("passive toggle: %#v %v", checks, err)
	}
	if err := store.EnqueueProbe(context.Background()); err != nil {
		t.Fatal(err)
	}
	if _, err := worker.ProcessOnce(context.Background()); err != nil {
		t.Fatal(err)
	}
	checks, err = store.ListChecks(context.Background(), "production", "oiai-api")
	if err != nil || len(checks) != 1 || checks[0].Status != "skipped" {
		t.Fatalf("active toggle: %#v %v", checks, err)
	}
}

func TestEventsSurviveStoreReopen(t *testing.T) {
	path := t.TempDir() + "/buras.sqlite"
	store, err := OpenStore(path)
	if err != nil {
		t.Fatal(err)
	}
	e := eventFor(1, "success")
	if err := e.Validate(); err != nil {
		t.Fatal(err)
	}
	if _, err := store.InsertEvent(context.Background(), e, true); err != nil {
		t.Fatal(err)
	}
	if err := store.Close(); err != nil {
		t.Fatal(err)
	}
	reopened, err := OpenStore(path)
	if err != nil {
		t.Fatal(err)
	}
	defer reopened.Close()
	events, err := reopened.ListEvents(context.Background(), "production", "oscbr", "")
	if err != nil || len(events) != 1 {
		t.Fatalf("persistent events: %d %v", len(events), err)
	}
}
