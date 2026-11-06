package buras

import (
	"errors"
	"regexp"
	"strings"
	"time"
)

var safeName = regexp.MustCompile(`^[a-zA-Z0-9_.:-]{1,80}$`)
var safeCorrelation = regexp.MustCompile(`^(?:[0-9A-HJKMNP-TV-Z]{26}|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})$`)
var safeClass = regexp.MustCompile(`^[a-zA-Z0-9_\\.]{1,160}$`)

type Event struct {
	ID            string   `json:"event_id"`
	Type          string   `json:"event"`
	OccurredAt    string   `json:"occurred_at"`
	Environment   string   `json:"environment"`
	Provider      string   `json:"provider"`
	Operation     string   `json:"operation"`
	Result        string   `json:"result"`
	HTTPStatus    *int     `json:"http_status"`
	DurationMS    *float64 `json:"duration_ms"`
	FailureType   *string  `json:"failure_type"`
	CorrelationID string   `json:"correlation_id"`
	ErrorClass    *string  `json:"error_class"`
}

func (e *Event) Validate() error {
	if !safeCorrelation.MatchString(e.ID) || !safeCorrelation.MatchString(e.CorrelationID) {
		return errors.New("invalid event or correlation ID")
	}
	if e.Type != "external_call" && e.Type != "exception" {
		return errors.New("invalid event type")
	}
	if !safeName.MatchString(e.Environment) || !safeName.MatchString(e.Provider) || !safeName.MatchString(e.Operation) {
		return errors.New("invalid event dimensions")
	}
	switch e.Result {
	case "success", "not_found", "skipped", "failure", "timeout", "connection_error", "http_error", "invalid_payload", "rate_limited", "unauthorized":
	default:
		return errors.New("invalid result")
	}
	if e.HTTPStatus != nil && (*e.HTTPStatus < 100 || *e.HTTPStatus > 599) {
		return errors.New("invalid HTTP status")
	}
	if e.DurationMS != nil && (*e.DurationMS < 0 || *e.DurationMS > 3600000) {
		return errors.New("invalid duration")
	}
	if e.FailureType != nil && !safeName.MatchString(*e.FailureType) {
		return errors.New("invalid failure type")
	}
	if e.ErrorClass != nil && !safeClass.MatchString(*e.ErrorClass) {
		return errors.New("invalid error class")
	}
	if e.Type == "exception" && (e.Provider != "oiai-api" || e.Result != "failure") {
		return errors.New("invalid exception event")
	}
	t, err := time.Parse(time.RFC3339Nano, e.OccurredAt)
	if err != nil || t.After(time.Now().Add(5*time.Minute)) || t.Before(time.Now().AddDate(-1, 0, 0)) {
		return errors.New("invalid event time")
	}
	e.OccurredAt = timestamp(t)
	e.Environment = strings.ToLower(e.Environment)
	return nil
}

func isFailure(result string) bool {
	return result != "success" && result != "not_found" && result != "skipped"
}

func timestamp(t time.Time) string { return t.UTC().Format("2006-01-02T15:04:05.000000000Z") }
