package buras

import (
	"crypto/subtle"
	"encoding/json"
	"errors"
	"html/template"
	"io"
	"log/slog"
	"mime"
	"net/http"
	"regexp"
	"strconv"
	"time"
)

type Server struct {
	Store  *Store
	Config Config
	Logger *slog.Logger
}

func NewServer(store *Store, config Config, logger *slog.Logger) http.Handler {
	s := &Server{Store: store, Config: config, Logger: logger}
	mux := http.NewServeMux()
	mux.HandleFunc("GET /healthz", s.health)
	mux.HandleFunc("POST /v1/events", s.ingest)
	mux.HandleFunc("GET /v1/events", s.admin(s.events))
	mux.HandleFunc("GET /v1/summary", s.admin(s.summary))
	mux.HandleFunc("GET /v1/checks", s.admin(s.checks))
	mux.HandleFunc("GET /v1/alerts", s.admin(s.alerts))
	mux.HandleFunc("GET /dashboard", s.admin(s.dashboard))
	mux.HandleFunc("GET /", func(w http.ResponseWriter, r *http.Request) { http.Redirect(w, r, "/dashboard", http.StatusSeeOther) })
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("Cache-Control", "no-store")
		mux.ServeHTTP(w, r)
	})
}

func tokenEqual(a, b string) bool {
	return len(a) == len(b) && subtle.ConstantTimeCompare([]byte(a), []byte(b)) == 1
}
func (s *Server) ingestAuthorized(r *http.Request) bool {
	return tokenEqual(r.Header.Get("Authorization"), "Bearer "+s.Config.IngestToken)
}
func (s *Server) admin(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		user, password, ok := r.BasicAuth()
		allowed := ok && user == "buras" && tokenEqual(password, s.Config.AdminToken)
		if !allowed {
			allowed = tokenEqual(r.Header.Get("Authorization"), "Bearer "+s.Config.AdminToken)
		}
		if !allowed {
			w.Header().Set("WWW-Authenticate", `Basic realm="Buras"`)
			http.Error(w, "unauthorized", http.StatusUnauthorized)
			return
		}
		next(w, r)
	}
}

func (s *Server) health(w http.ResponseWriter, r *http.Request) {
	if err := s.Store.db.PingContext(r.Context()); err != nil {
		http.Error(w, "unavailable", http.StatusServiceUnavailable)
		return
	}
	writeJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}

func (s *Server) ingest(w http.ResponseWriter, r *http.Request) {
	if !s.ingestAuthorized(r) {
		http.Error(w, "unauthorized", http.StatusUnauthorized)
		return
	}
	mediaType, _, mediaErr := mime.ParseMediaType(r.Header.Get("Content-Type"))
	if mediaErr != nil || mediaType != "application/json" {
		http.Error(w, "expected application/json", http.StatusUnsupportedMediaType)
		return
	}
	decoder := json.NewDecoder(http.MaxBytesReader(w, r.Body, 4096))
	decoder.DisallowUnknownFields()
	var e Event
	if err := decoder.Decode(&e); err != nil {
		http.Error(w, "invalid event", http.StatusBadRequest)
		return
	}
	var trailing any
	if err := decoder.Decode(&trailing); err != io.EOF {
		http.Error(w, "invalid event", http.StatusBadRequest)
		return
	}
	if err := e.Validate(); err != nil {
		http.Error(w, "invalid event", http.StatusBadRequest)
		return
	}
	inserted, err := s.Store.InsertEvent(r.Context(), e, s.Config.PassiveEnabled)
	if err != nil {
		s.Logger.Error("persist event", "error", err)
		http.Error(w, "unavailable", http.StatusServiceUnavailable)
		return
	}
	writeJSON(w, http.StatusAccepted, map[string]bool{"accepted": inserted})
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(v)
}

var queryValue = regexp.MustCompile(`^[a-zA-Z0-9_.:-]{0,128}$`)

func filters(r *http.Request) (string, string, string, bool) {
	env := r.URL.Query().Get("environment")
	provider := r.URL.Query().Get("provider")
	corr := r.URL.Query().Get("correlation_id")
	return env, provider, corr, queryValue.MatchString(env) && queryValue.MatchString(provider) && queryValue.MatchString(corr)
}
func since(r *http.Request) (time.Time, error) {
	raw := r.URL.Query().Get("period")
	if raw == "" {
		raw = "24h"
	}
	d, err := time.ParseDuration(raw)
	if err != nil || d < time.Hour || d > 720*time.Hour {
		return time.Time{}, errors.New("invalid period")
	}
	return time.Now().Add(-d), nil
}
func (s *Server) events(w http.ResponseWriter, r *http.Request) {
	env, provider, corr, ok := filters(r)
	if !ok {
		http.Error(w, "invalid filter", 400)
		return
	}
	result, err := s.Store.ListEvents(r.Context(), env, provider, corr)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	writeJSON(w, 200, result)
}
func (s *Server) summary(w http.ResponseWriter, r *http.Request) {
	env, provider, _, ok := filters(r)
	period, err := since(r)
	if !ok || err != nil {
		http.Error(w, "invalid filter", 400)
		return
	}
	result, err := s.Store.Summary(r.Context(), env, provider, period)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	writeJSON(w, 200, result)
}
func (s *Server) checks(w http.ResponseWriter, r *http.Request) {
	env, provider, _, ok := filters(r)
	if !ok {
		http.Error(w, "invalid filter", 400)
		return
	}
	result, err := s.Store.ListChecks(r.Context(), env, provider)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	writeJSON(w, 200, result)
}
func (s *Server) alerts(w http.ResponseWriter, r *http.Request) {
	env, provider, _, ok := filters(r)
	if !ok {
		http.Error(w, "invalid filter", 400)
		return
	}
	result, err := s.Store.ListAlerts(r.Context(), env, provider)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	writeJSON(w, 200, result)
}

type dashboardData struct {
	Environment    string
	Provider       string
	Period         string
	Summaries      []Summary
	Events         []Event
	Checks         []Check
	Alerts         []Alert
	PassiveEnabled bool
	ActiveEnabled  bool
}

var dashboardTemplate = template.Must(template.New("dashboard").Funcs(template.FuncMap{"format": func(v float64) string { return strconv.FormatFloat(v, 'f', 1, 64) }}).Parse(`<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Buras · Observabilidade</title>
<style>body{font:15px system-ui,sans-serif;background:#f8f7f5;color:#251c24;margin:0;padding:2rem}main{max-width:1200px;margin:auto}h1{margin:0;color:#7d1426}h2{margin-top:2rem}form,.card{background:white;border:1px solid #e9e5e4;border-radius:10px;padding:1rem;margin:1rem 0}label{margin-right:1rem}input{padding:.5rem;border:1px solid #ccc;border-radius:5px}button{padding:.5rem 1rem;background:#7d1426;color:white;border:0;border-radius:5px}table{border-collapse:collapse;width:100%;background:white}th,td{border:1px solid #e9e5e4;padding:.6rem;text-align:left;overflow-wrap:anywhere}th{background:#f1eceb}.table{overflow-x:auto}.muted{color:#655b63}.fail,.firing{color:#a20d24;font-weight:700}.pass,.resolved{color:#17623f}small{color:#655b63}</style></head><body><main>
<h1>Buras</h1><p class="muted">Observabilidade do Óiaí · camada passiva: {{if .PassiveEnabled}}ativa{{else}}desligada{{end}} · sonda ativa: {{if .ActiveEnabled}}ativa{{else}}desligada{{end}}</p>
<form method="get"><label>Ambiente <input name="environment" value="{{.Environment}}"></label><label>Provedor <input name="provider" value="{{.Provider}}"></label><label>Período <input name="period" value="{{.Period}}" placeholder="24h"></label><button>Filtrar</button></form>
<h2>Saúde das integrações</h2><div class="table"><table><thead><tr><th>Provedor</th><th>Operação</th><th>Chamadas</th><th>Sucesso</th><th>Falhas</th><th>Timeouts</th><th>Média</th><th>p95</th></tr></thead><tbody>{{range .Summaries}}<tr><td>{{.Provider}}</td><td>{{.Operation}}</td><td>{{.Calls}}</td><td>{{.Successes}}</td><td>{{.Failures}}</td><td>{{.Timeouts}}</td><td>{{format .AverageMS}} ms</td><td>{{format .P95MS}} ms</td></tr>{{else}}<tr><td colspan="8">Sem chamadas no período.</td></tr>{{end}}</tbody></table></div>
<h2>Alertas</h2><div class="table"><table><tr><th>Integração</th><th>Estado</th><th>Última ocorrência</th></tr>{{range .Alerts}}<tr><td>{{.Environment}} / {{.Provider}} / {{.Operation}}</td><td class="{{.State}}">{{.State}}</td><td>{{.LastSeen}}</td></tr>{{else}}<tr><td colspan="3">Sem alertas.</td></tr>{{end}}</table></div>
<h2>Camadas de checagem</h2><div class="table"><table><tr><th>Camada</th><th>Checagem</th><th>Integração</th><th>Estado</th><th>Detalhe</th><th>Quando</th></tr>{{range .Checks}}<tr><td>{{.Layer}}</td><td>{{.Name}}</td><td>{{.Environment}} / {{.Provider}} / {{.Operation}}</td><td class="{{.Status}}">{{.Status}}</td><td>{{.DetailCode}}</td><td>{{.OccurredAt}}</td></tr>{{else}}<tr><td colspan="6">Sem checagens.</td></tr>{{end}}</table></div>
<h2>Eventos recentes</h2><div class="table"><table><tr><th>Quando</th><th>Tipo</th><th>Integração</th><th>Resultado</th><th>HTTP</th><th>Duração</th><th>Correlação</th></tr>{{range .Events}}<tr><td>{{.OccurredAt}}</td><td>{{.Type}}</td><td>{{.Provider}} / {{.Operation}}</td><td>{{.Result}}</td><td>{{if .HTTPStatus}}{{.HTTPStatus}}{{end}}</td><td>{{if .DurationMS}}{{.DurationMS}} ms{{end}}</td><td>{{.CorrelationID}}</td></tr>{{else}}<tr><td colspan="7">Sem eventos.</td></tr>{{end}}</table></div>
<small>Eventos são mantidos conforme BURAS_RETENTION_DAYS. Nenhum corpo ou segredo é armazenado.</small></main></body></html>`))

func (s *Server) dashboard(w http.ResponseWriter, r *http.Request) {
	env, provider, _, ok := filters(r)
	period, err := since(r)
	if !ok || err != nil {
		http.Error(w, "invalid filter", 400)
		return
	}
	summaries, err := s.Store.Summary(r.Context(), env, provider, period)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	events, err := s.Store.ListEventsSince(r.Context(), env, provider, "", period)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	checks, err := s.Store.ListChecks(r.Context(), env, provider)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	alerts, err := s.Store.ListAlerts(r.Context(), env, provider)
	if err != nil {
		http.Error(w, "unavailable", 503)
		return
	}
	data := dashboardData{Environment: env, Provider: provider, Period: r.URL.Query().Get("period"), Summaries: summaries, Events: events, Checks: checks, Alerts: alerts, PassiveEnabled: s.Config.PassiveEnabled, ActiveEnabled: s.Config.ActiveEnabled}
	if data.Period == "" {
		data.Period = "24h"
	}
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Content-Security-Policy", "default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'")
	_ = dashboardTemplate.Execute(w, data)
}
