package buras

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"time"

	_ "modernc.org/sqlite"
)

type Store struct{ db *sql.DB }

type Check struct {
	ID          int64   `json:"id"`
	Layer       string  `json:"layer"`
	Name        string  `json:"name"`
	Environment string  `json:"environment"`
	Provider    string  `json:"provider"`
	Operation   string  `json:"operation"`
	Status      string  `json:"status"`
	DetailCode  string  `json:"detail_code"`
	DurationMS  float64 `json:"duration_ms"`
	OccurredAt  string  `json:"occurred_at"`
}

type Alert struct {
	Key          string `json:"key"`
	Environment  string `json:"environment"`
	Provider     string `json:"provider"`
	Operation    string `json:"operation"`
	State        string `json:"state"`
	LastSeen     string `json:"last_seen"`
	LastNotified string `json:"last_notified"`
}

type Summary struct {
	Provider  string  `json:"provider"`
	Operation string  `json:"operation"`
	Calls     int64   `json:"calls"`
	Successes int64   `json:"successes"`
	Failures  int64   `json:"failures"`
	Timeouts  int64   `json:"timeouts"`
	AverageMS float64 `json:"average_ms"`
	P95MS     float64 `json:"p95_ms"`
}

func OpenStore(path string) (*Store, error) {
	if err := os.MkdirAll(filepath.Dir(path), 0700); err != nil {
		return nil, err
	}
	db, err := sql.Open("sqlite", path)
	if err != nil {
		return nil, err
	}
	db.SetMaxOpenConns(1)
	for _, statement := range []string{
		`PRAGMA journal_mode=WAL`, `PRAGMA busy_timeout=5000`, `PRAGMA foreign_keys=ON`,
		`CREATE TABLE IF NOT EXISTS events (
            id TEXT PRIMARY KEY, event_type TEXT NOT NULL, occurred_at TEXT NOT NULL,
            environment TEXT NOT NULL, provider TEXT NOT NULL, operation TEXT NOT NULL,
            result TEXT NOT NULL, http_status INTEGER, duration_ms REAL, failure_type TEXT,
            correlation_id TEXT NOT NULL, error_class TEXT, received_at TEXT NOT NULL)`,
		`CREATE INDEX IF NOT EXISTS events_lookup ON events(environment, provider, operation, occurred_at DESC)`,
		`CREATE INDEX IF NOT EXISTS events_correlation ON events(correlation_id)`,
		`CREATE TABLE IF NOT EXISTS check_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, event_id TEXT,
            status TEXT NOT NULL, created_at TEXT NOT NULL, claimed_at TEXT,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE)`,
		`CREATE UNIQUE INDEX IF NOT EXISTS check_job_event ON check_jobs(kind, event_id)`,
		`CREATE TABLE IF NOT EXISTS check_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT, job_id INTEGER NOT NULL UNIQUE,
            layer TEXT NOT NULL, name TEXT NOT NULL, environment TEXT NOT NULL,
            provider TEXT NOT NULL, operation TEXT NOT NULL, status TEXT NOT NULL,
            detail_code TEXT NOT NULL, duration_ms REAL NOT NULL, occurred_at TEXT NOT NULL,
            FOREIGN KEY(job_id) REFERENCES check_jobs(id) ON DELETE CASCADE)`,
		`CREATE INDEX IF NOT EXISTS checks_lookup ON check_results(environment, provider, occurred_at DESC)`,
		`CREATE TABLE IF NOT EXISTS alerts (
            key TEXT PRIMARY KEY, environment TEXT NOT NULL, provider TEXT NOT NULL,
            operation TEXT NOT NULL, state TEXT NOT NULL, last_seen TEXT NOT NULL,
            last_notified TEXT NOT NULL DEFAULT '')`,
	} {
		if _, err := db.Exec(statement); err != nil {
			db.Close()
			return nil, fmt.Errorf("initialize database: %w", err)
		}
	}
	if _, err := db.Exec(`UPDATE check_jobs SET status='pending', claimed_at=NULL WHERE status='running'`); err != nil {
		db.Close()
		return nil, err
	}
	return &Store{db: db}, nil
}

func (s *Store) Close() error { return s.db.Close() }

func (s *Store) InsertEvent(ctx context.Context, e Event, passive bool) (bool, error) {
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return false, err
	}
	defer tx.Rollback()
	res, err := tx.ExecContext(ctx, `INSERT OR IGNORE INTO events(id,event_type,occurred_at,environment,provider,operation,result,http_status,duration_ms,failure_type,correlation_id,error_class,received_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)`,
		e.ID, e.Type, e.OccurredAt, e.Environment, e.Provider, e.Operation, e.Result, e.HTTPStatus, e.DurationMS, e.FailureType, e.CorrelationID, e.ErrorClass, time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z"))
	if err != nil {
		return false, err
	}
	n, err := res.RowsAffected()
	if err != nil {
		return false, err
	}
	if n == 1 && passive {
		_, err = tx.ExecContext(ctx, `INSERT INTO check_jobs(kind,event_id,status,created_at) VALUES('passive',?,'pending',?)`, e.ID, time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z"))
		if err != nil {
			return false, err
		}
	}
	return n == 1, tx.Commit()
}

func (s *Store) EnqueueProbe(ctx context.Context) error {
	var pending int
	if err := s.db.QueryRowContext(ctx, `SELECT COUNT(*) FROM check_jobs WHERE kind='active' AND status IN ('pending','running')`).Scan(&pending); err != nil {
		return err
	}
	if pending > 0 {
		return nil
	}
	_, err := s.db.ExecContext(ctx, `INSERT INTO check_jobs(kind,status,created_at) VALUES('active','pending',?)`, time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z"))
	return err
}

type Job struct {
	ID      int64
	Kind    string
	EventID sql.NullString
}

func (s *Store) ClaimJob(ctx context.Context) (Job, error) {
	var j Job
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return j, err
	}
	defer tx.Rollback()
	err = tx.QueryRowContext(ctx, `SELECT id,kind,event_id FROM check_jobs WHERE status='pending' ORDER BY id LIMIT 1`).Scan(&j.ID, &j.Kind, &j.EventID)
	if err != nil {
		return j, err
	}
	if _, err = tx.ExecContext(ctx, `UPDATE check_jobs SET status='running',claimed_at=? WHERE id=?`, time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z"), j.ID); err != nil {
		return j, err
	}
	return j, tx.Commit()
}

func (s *Store) EventByID(ctx context.Context, id string) (Event, error) {
	var e Event
	err := s.db.QueryRowContext(ctx, `SELECT id,event_type,occurred_at,environment,provider,operation,result,http_status,duration_ms,failure_type,correlation_id,error_class FROM events WHERE id=?`, id).
		Scan(&e.ID, &e.Type, &e.OccurredAt, &e.Environment, &e.Provider, &e.Operation, &e.Result, &e.HTTPStatus, &e.DurationMS, &e.FailureType, &e.CorrelationID, &e.ErrorClass)
	return e, err
}

func (s *Store) FinishJob(ctx context.Context, c Check, jobID int64) error {
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer tx.Rollback()
	_, err = tx.ExecContext(ctx, `INSERT OR IGNORE INTO check_results(job_id,layer,name,environment,provider,operation,status,detail_code,duration_ms,occurred_at) VALUES(?,?,?,?,?,?,?,?,?,?)`, jobID, c.Layer, c.Name, c.Environment, c.Provider, c.Operation, c.Status, c.DetailCode, c.DurationMS, c.OccurredAt)
	if err != nil {
		return err
	}
	_, err = tx.ExecContext(ctx, `UPDATE check_jobs SET status='done' WHERE id=?`, jobID)
	if err != nil {
		return err
	}
	return tx.Commit()
}

func (s *Store) FailJob(ctx context.Context, jobID int64) {
	_, _ = s.db.ExecContext(ctx, `UPDATE check_jobs SET status='failed' WHERE id=?`, jobID)
}

func (s *Store) RecentFailures(ctx context.Context, environment, provider, operation string, since time.Time, limit int) (int, error) {
	rows, err := s.db.QueryContext(ctx, `SELECT result FROM events WHERE event_type='external_call' AND environment=? AND provider=? AND operation=? AND occurred_at>=? AND result NOT IN ('not_found','skipped') ORDER BY occurred_at DESC LIMIT ?`, environment, provider, operation, since.UTC().Format("2006-01-02T15:04:05.000000000Z"), limit)
	if err != nil {
		return 0, err
	}
	defer rows.Close()
	failures := 0
	for rows.Next() {
		var result string
		if err := rows.Scan(&result); err != nil {
			return 0, err
		}
		if !isFailure(result) {
			return failures, nil
		}
		failures++
	}
	return failures, rows.Err()
}

func alertKey(env, provider, operation string) string { return env + "/" + provider + "/" + operation }

func (s *Store) UpsertAlert(ctx context.Context, env, provider, operation string, cooldown time.Duration) (bool, error) {
	key := alertKey(env, provider, operation)
	now := time.Now().UTC()
	var last string
	err := s.db.QueryRowContext(ctx, `SELECT last_notified FROM alerts WHERE key=?`, key).Scan(&last)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return false, err
	}
	notify := last == ""
	if last != "" {
		if t, parseErr := time.Parse(time.RFC3339Nano, last); parseErr == nil {
			notify = now.Sub(t) >= cooldown
		}
	}
	_, err = s.db.ExecContext(ctx, `INSERT INTO alerts(key,environment,provider,operation,state,last_seen,last_notified) VALUES(?,?,?,?,'firing',?,?) ON CONFLICT(key) DO UPDATE SET state='firing',last_seen=excluded.last_seen`, key, env, provider, operation, now.Format(time.RFC3339Nano), last)
	return notify, err
}

func (s *Store) MarkAlertNotified(ctx context.Context, env, provider, operation string) error {
	_, err := s.db.ExecContext(ctx, `UPDATE alerts SET last_notified=? WHERE key=?`, time.Now().UTC().Format("2006-01-02T15:04:05.000000000Z"), alertKey(env, provider, operation))
	return err
}

func (s *Store) ResolveAlert(ctx context.Context, env, provider, operation string) error {
	_, err := s.db.ExecContext(ctx, `UPDATE alerts SET state='resolved' WHERE key=? AND state='firing'`, alertKey(env, provider, operation))
	return err
}

func (s *Store) ListAlerts(ctx context.Context, env, provider string) ([]Alert, error) {
	rows, err := s.db.QueryContext(ctx, `SELECT key,environment,provider,operation,state,last_seen,last_notified FROM alerts WHERE (?='' OR environment=?) AND (?='' OR provider=?) ORDER BY last_seen DESC LIMIT 100`, env, env, provider, provider)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Alert{}
	for rows.Next() {
		var a Alert
		if err := rows.Scan(&a.Key, &a.Environment, &a.Provider, &a.Operation, &a.State, &a.LastSeen, &a.LastNotified); err != nil {
			return nil, err
		}
		out = append(out, a)
	}
	return out, rows.Err()
}

func (s *Store) ListChecks(ctx context.Context, env, provider string) ([]Check, error) {
	rows, err := s.db.QueryContext(ctx, `SELECT id,layer,name,environment,provider,operation,status,detail_code,duration_ms,occurred_at FROM check_results WHERE (?='' OR environment=?) AND (?='' OR provider=?) ORDER BY id DESC LIMIT 50`, env, env, provider, provider)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Check{}
	for rows.Next() {
		var c Check
		if err := rows.Scan(&c.ID, &c.Layer, &c.Name, &c.Environment, &c.Provider, &c.Operation, &c.Status, &c.DetailCode, &c.DurationMS, &c.OccurredAt); err != nil {
			return nil, err
		}
		out = append(out, c)
	}
	return out, rows.Err()
}

func (s *Store) ListEvents(ctx context.Context, env, provider, correlation string) ([]Event, error) {
	return s.ListEventsSince(ctx, env, provider, correlation, time.Time{})
}

func (s *Store) ListEventsSince(ctx context.Context, env, provider, correlation string, since time.Time) ([]Event, error) {
	cutoff := ""
	if !since.IsZero() {
		cutoff = timestamp(since)
	}
	rows, err := s.db.QueryContext(ctx, `SELECT id,event_type,occurred_at,environment,provider,operation,result,http_status,duration_ms,failure_type,correlation_id,error_class FROM events WHERE (?='' OR environment=?) AND (?='' OR provider=?) AND (?='' OR correlation_id=?) AND (?='' OR occurred_at>=?) ORDER BY occurred_at DESC LIMIT 100`, env, env, provider, provider, correlation, correlation, cutoff, cutoff)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Event{}
	for rows.Next() {
		var e Event
		if err := rows.Scan(&e.ID, &e.Type, &e.OccurredAt, &e.Environment, &e.Provider, &e.Operation, &e.Result, &e.HTTPStatus, &e.DurationMS, &e.FailureType, &e.CorrelationID, &e.ErrorClass); err != nil {
			return nil, err
		}
		out = append(out, e)
	}
	return out, rows.Err()
}

func (s *Store) Summary(ctx context.Context, env, provider string, since time.Time) ([]Summary, error) {
	rows, err := s.db.QueryContext(ctx, `SELECT provider,operation,COUNT(*),SUM(CASE WHEN result='success' THEN 1 ELSE 0 END),SUM(CASE WHEN result NOT IN ('success','not_found','skipped') THEN 1 ELSE 0 END),SUM(CASE WHEN result IN ('timeout','connection_error') THEN 1 ELSE 0 END),COALESCE(AVG(duration_ms),0) FROM events WHERE event_type='external_call' AND occurred_at>=? AND (?='' OR environment=?) AND (?='' OR provider=?) GROUP BY provider,operation ORDER BY provider,operation`, since.UTC().Format("2006-01-02T15:04:05.000000000Z"), env, env, provider, provider)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Summary{}
	for rows.Next() {
		var v Summary
		if err := rows.Scan(&v.Provider, &v.Operation, &v.Calls, &v.Successes, &v.Failures, &v.Timeouts, &v.AverageMS); err != nil {
			return nil, err
		}
		out = append(out, v)
	}
	if err := rows.Err(); err != nil {
		rows.Close()
		return nil, err
	}
	if err := rows.Close(); err != nil {
		return nil, err
	}
	for i := range out {
		var count int
		err := s.db.QueryRowContext(ctx, `SELECT COUNT(*) FROM events WHERE event_type='external_call' AND occurred_at>=? AND (?='' OR environment=?) AND provider=? AND operation=? AND duration_ms IS NOT NULL`, since.UTC().Format("2006-01-02T15:04:05.000000000Z"), env, env, out[i].Provider, out[i].Operation).Scan(&count)
		if err != nil {
			return nil, err
		}
		if count > 0 {
			offset := (95*count+99)/100 - 1
			err = s.db.QueryRowContext(ctx, `SELECT duration_ms FROM events WHERE event_type='external_call' AND occurred_at>=? AND (?='' OR environment=?) AND provider=? AND operation=? AND duration_ms IS NOT NULL ORDER BY duration_ms LIMIT 1 OFFSET ?`, since.UTC().Format("2006-01-02T15:04:05.000000000Z"), env, env, out[i].Provider, out[i].Operation, offset).Scan(&out[i].P95MS)
			if err != nil {
				return nil, err
			}
		}
	}
	return out, nil
}

func (s *Store) Prune(ctx context.Context, days int) error {
	cutoff := time.Now().UTC().AddDate(0, 0, -days).Format(time.RFC3339Nano)
	_, err := s.db.ExecContext(ctx, `DELETE FROM events WHERE occurred_at<?`, cutoff)
	if err != nil {
		return err
	}
	_, err = s.db.ExecContext(ctx, `DELETE FROM check_jobs WHERE created_at<?`, cutoff)
	if err != nil {
		return err
	}
	_, err = s.db.ExecContext(ctx, `DELETE FROM alerts WHERE state='resolved' AND last_seen<?`, cutoff)
	return err
}
