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
while ! mysqladmin ping -h"db" -u"root" -p"secret" --silent; do
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

echo "Iniciando Queue Worker em background..."
# Worker rodando EM BACKGROUND (não controlado pelo Supervisor)
php artisan queue:listen --daemon --sleep=3 --tries=3 --timeout=60 > /var/www/html/storage/logs/worker.log 2>&1 &

echo "Tudo pronto! Iniciando Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
