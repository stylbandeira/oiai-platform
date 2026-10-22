#!/bin/bash
set -e

echo "Iniciando serviços Laravel..."

# Configurar permissões
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Criar diretórios de log
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs

# AGUARDAR MySQL ficar pronto
echo "Aguardando MySQL ficar disponível..."
while ! mysqladmin ping -h"${DB_HOST:-db}" -u"${DB_USERNAME:?DB_USERNAME is required}" -p"${DB_PASSWORD:?DB_PASSWORD is required}" --silent; do
    echo "MySQL não está pronto... aguardando 5 segundos"
    sleep 5
done

echo "MySQL está pronto! Continuando..."

# Rodar migrações
php artisan migrate --force

# Limpar caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "Tudo pronto! Iniciando Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
