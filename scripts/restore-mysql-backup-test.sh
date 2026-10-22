#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_FILE="${1:?Uso: $0 /caminho/backup.sql.gz}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
ROOT_USER="${DB_ROOT_USERNAME:-root}"
ROOT_PASSWORD="${DB_ROOT_PASSWORD:?DB_ROOT_PASSWORD is required}"
TEST_DATABASE="${RESTORE_TEST_DATABASE:-oiai_restore_test}"

[[ -s "$BACKUP_FILE" ]] || { echo "Backup inexistente ou vazio: $BACKUP_FILE" >&2; exit 1; }
[[ "${RESTORE_TEST_CONFIRM:-}" == YES ]] || { echo "Defina RESTORE_TEST_CONFIRM=YES." >&2; exit 1; }

umask 077
mysql_cnf="$(mktemp)"
trap 'rm -f "$mysql_cnf"' EXIT
chmod 600 "$mysql_cnf"
{
    echo '[client]'
    echo "host=$DB_HOST"
    echo "port=$DB_PORT"
    echo "user=$ROOT_USER"
    echo "password=$ROOT_PASSWORD"
} > "$mysql_cnf"

mysql --defaults-extra-file="$mysql_cnf" -e "DROP DATABASE IF EXISTS \`$TEST_DATABASE\`; CREATE DATABASE \`$TEST_DATABASE\`;"
gzip -dc -- "$BACKUP_FILE" | mysql --defaults-extra-file="$mysql_cnf" "$TEST_DATABASE"
mysql --defaults-extra-file="$mysql_cnf" "$TEST_DATABASE" -e 'SELECT 1 AS restore_ok;'
mysql --defaults-extra-file="$mysql_cnf" -e "DROP DATABASE \`$TEST_DATABASE\`;"
echo "Restauração validada com sucesso: $BACKUP_FILE"
