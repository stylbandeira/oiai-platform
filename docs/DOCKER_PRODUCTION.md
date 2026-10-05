# Docker em produção

O compose de desenvolvimento é `docker-compose.yml`. A VPS deve usar somente
`docker-compose.prod.yml` e um arquivo de ambiente próprio, como
`apps/api/.env.prod`, fora do versionamento.

Antes de subir a stack, configure no ambiente da VPS:

```bash
export API_ENV_FILE=./apps/api/.env.prod
export DB_DATABASE=oiai
export DB_USERNAME=oiai
export DB_PASSWORD='senha-forte'
export DB_ROOT_PASSWORD='senha-raiz-forte'
export MEILISEARCH_KEY='chave-forte'
export VITE_API_URL='https://api.exemplo.com'
```

O arquivo `.env.prod` deve usar `DB_HOST=db`, `MEILISEARCH_HOST=http://meilisearch:7700`,
`APP_ENV=production`, `APP_DEBUG=false`, SMTP real e `QUEUE_CONNECTION=database`.

Subida da stack:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

A composição de produção não inicia Mailhog, phpMyAdmin, Vite, `artisan serve` ou
bind mounts do código-fonte. MySQL e Meilisearch ficam acessíveis apenas na rede
interna do Compose; somente as portas HTTP configuradas para API e Web são
publicadas.
