package buras

import (
	"errors"
	"net/url"
	"os"
	"strconv"
	"strings"
	"time"
)

type Config struct {
	Address          string
	DBPath           string
	IngestToken      string
	AdminToken       string
	PassiveEnabled   bool
	ActiveEnabled    bool
	ProbeURL         string
	ProbeEnvironment string
	ProbeInterval    time.Duration
	ProbeTimeout     time.Duration
	AlertFailures    int
	AlertWindow      time.Duration
	AlertCooldown    time.Duration
	AlertWebhookURL  string
	RetentionDays    int
}

func ConfigFromEnv() (Config, error) {
	c := Config{
		Address:     env("BURAS_LISTEN_ADDR", ":8085"),
		DBPath:      env("BURAS_DB_PATH", "/data/buras.sqlite"),
		IngestToken: os.Getenv("BURAS_INGEST_TOKEN"),
		AdminToken:  os.Getenv("BURAS_ADMIN_TOKEN"),

		ProbeURL:         os.Getenv("BURAS_PROBE_URL"),
		ProbeEnvironment: env("BURAS_PROBE_ENVIRONMENT", "production"),
		AlertWebhookURL:  os.Getenv("BURAS_ALERT_WEBHOOK_URL"),
	}
	var err error
	if c.PassiveEnabled, err = strconv.ParseBool(env("BURAS_PASSIVE_ENABLED", "true")); err != nil {
		return c, errors.New("invalid BURAS_PASSIVE_ENABLED")
	}
	if c.ActiveEnabled, err = strconv.ParseBool(env("BURAS_ACTIVE_ENABLED", "false")); err != nil {
		return c, errors.New("invalid BURAS_ACTIVE_ENABLED")
	}
	if c.ProbeInterval, err = time.ParseDuration(env("BURAS_PROBE_INTERVAL", "5m")); err != nil {
		return c, errors.New("invalid BURAS_PROBE_INTERVAL")
	}
	if c.ProbeTimeout, err = time.ParseDuration(env("BURAS_PROBE_TIMEOUT", "3s")); err != nil {
		return c, errors.New("invalid BURAS_PROBE_TIMEOUT")
	}
	if c.AlertWindow, err = time.ParseDuration(env("BURAS_ALERT_WINDOW", "1h")); err != nil {
		return c, errors.New("invalid BURAS_ALERT_WINDOW")
	}
	if c.AlertCooldown, err = time.ParseDuration(env("BURAS_ALERT_COOLDOWN", "1h")); err != nil {
		return c, errors.New("invalid BURAS_ALERT_COOLDOWN")
	}
	if c.AlertFailures, err = strconv.Atoi(env("BURAS_ALERT_FAILURES", "5")); err != nil {
		return c, errors.New("invalid BURAS_ALERT_FAILURES")
	}
	if c.RetentionDays, err = strconv.Atoi(env("BURAS_RETENTION_DAYS", "30")); err != nil {
		return c, errors.New("invalid BURAS_RETENTION_DAYS")
	}
	if len(c.IngestToken) < 32 || len(c.AdminToken) < 32 || c.IngestToken == c.AdminToken {
		return c, errors.New("BURAS_INGEST_TOKEN and BURAS_ADMIN_TOKEN must be distinct and at least 32 characters")
	}
	if c.ProbeInterval < time.Minute || c.ProbeTimeout <= 0 || c.ProbeTimeout > 10*time.Second || c.AlertWindow < time.Minute || c.AlertCooldown < time.Minute || c.AlertFailures < 2 || c.RetentionDays < 1 {
		return c, errors.New("invalid Buras limits")
	}
	if !safeName.MatchString(c.ProbeEnvironment) {
		return c, errors.New("invalid BURAS_PROBE_ENVIRONMENT")
	}
	if c.ActiveEnabled {
		u, err := url.Parse(c.ProbeURL)
		if err != nil || u == nil || (u.Scheme != "http" && u.Scheme != "https") || u.Host == "" || u.User != nil || u.RawQuery != "" || u.Fragment != "" {
			return c, errors.New("BURAS_PROBE_URL must be an HTTP(S) URL without credentials or query")
		}
	}
	if c.AlertWebhookURL != "" {
		u, err := url.Parse(c.AlertWebhookURL)
		if err != nil || u == nil || u.Scheme != "https" || u.Host == "" || u.User != nil {
			return c, errors.New("BURAS_ALERT_WEBHOOK_URL must be HTTPS without credentials")
		}
	}
	return c, nil
}

func env(key, fallback string) string {
	if v := strings.TrimSpace(os.Getenv(key)); v != "" {
		return v
	}
	return fallback
}
