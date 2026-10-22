#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_DIR="${BACKUP_DIR:-/backups/oiai}"
LOG_FILE="${BACKUP_LOG_FILE:-/var/log/oiai-backup.log}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:?DB_DATABASE is required}"
DB_USER="${DB_USERNAME:?DB_USERNAME is required}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD is required}"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
umask 077

log() {
    printf '%s %s\n' "$(date --iso-8601=seconds)" "$1" | tee -a "$LOG_FILE"
}

cleanup() { rm -f "${MYSQL_CNF:-}" 2>/dev/null || true; }
trap cleanup EXIT

MYSQL_CNF="$(mktemp)"
chmod 600 "$MYSQL_CNF"
{
    echo '[client]'
    echo "host=$DB_HOST"
    echo "port=$DB_PORT"
    echo "user=$DB_USER"
    echo "password=$DB_PASSWORD"
} > "$MYSQL_CNF"

timestamp="$(date +%Y-%m-%d-%H%M)"
backup_file="$BACKUP_DIR/oiai-$timestamp.sql.gz"

if ! mysqldump --defaults-extra-file="$MYSQL_CNF" --single-transaction --quick \
    --routines --events --triggers --no-tablespaces "$DB_NAME" | gzip -9 > "$backup_file"; then
    rm -f "$backup_file"
    log "ERRO: falha ao gerar backup de $DB_NAME"
    exit 1
fi

if [[ ! -s "$backup_file" ]]; then
    rm -f "$backup_file"
    log "ERRO: backup vazio: $backup_file"
    exit 1
fi

chmod 600 "$backup_file"
log "Backup criado: $backup_file"

# Mantém 7 snapshots recentes, 4 pontos semanais e 3 pontos mensais.
mapfile -t backups < <(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'oiai-*.sql.gz' -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2-)
for index in "${!backups[@]}"; do
    keep=false
    if (( index < 7 )); then keep=true
    elif (( index >= 7 && index < 35 && (index - 7) % 7 == 0 )); then keep=true
    elif (( index >= 35 && index < 125 && (index - 35) % 30 == 0 )); then keep=true
    fi
    [[ "$keep" == true ]] || rm -f -- "${backups[$index]}"
done
