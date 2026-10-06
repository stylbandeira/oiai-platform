# Docker em produção

O compose de desenvolvimento é `docker-compose.yml`. A VPS deve usar somente
`docker-compose.prod.yml` e um arquivo de ambiente próprio, como
`apps/api/.env.prod`, fora do versionamento.

A API é executada em PHP-FPM atrás de um Nginx dedicado. O frontend também é
compilado durante o build e servido por Nginx; nenhum serviço usa `php artisan
serve` ou o servidor de desenvolvimento do Vite.

Antes de subir a stack, configure no ambiente da VPS:

```bash
export API_ENV_FILE=./apps/api/.env.prod
export DB_DATABASE=oiai
export DB_USERNAME=oiai
export DB_PASSWORD='senha-forte'
export DB_ROOT_PASSWORD='senha-raiz-forte'
export MEILISEARCH_KEY='chave-forte'
export VITE_API_URL=''
```

O arquivo `.env.prod` deve usar `DB_HOST=db`, `MEILISEARCH_HOST=http://meilisearch:7700`,
`APP_ENV=production`, `APP_DEBUG=false`, SMTP real e `QUEUE_CONNECTION=database`.
Os caches `config`, `route` e `view` são gerados no startup do container PHP-FPM.
Com `VITE_API_URL` vazio, o Nginx do frontend encaminha `/api` internamente para
o serviço da API. A stack publica apenas HTTP na porta 80; TLS/443 deve ser
terminado no reverse proxy da VPS.

Subida da stack:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

A composição de produção não inicia Mailhog, phpMyAdmin, Vite, `artisan serve` ou
bind mounts do código-fonte. MySQL, Meilisearch e PHP-FPM ficam acessíveis apenas
na rede interna do Compose; não há portas públicas de infraestrutura.
