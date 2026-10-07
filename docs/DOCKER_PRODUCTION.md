# Docker em produção

O compose de desenvolvimento é `docker-compose.yml`. A VPS deve usar somente
`docker-compose.prod.yml` e um arquivo de ambiente próprio, como
`apps/api/.env.prod`, fora do versionamento.

A API é executada em PHP-FPM atrás de um Nginx dedicado. O frontend também é
compilado durante o build e servido por Nginx; nenhum serviço usa `php artisan
serve` ou o servidor de desenvolvimento do Vite.

O Nginx externo da VPS acessa somente `127.0.0.1:3000` (frontend) e
`127.0.0.1:8001` (API). MySQL e Meilisearch ficam exclusivamente na rede
interna do Compose.

Antes de subir a stack, configure no ambiente da VPS:

```bash
cp .env.production.example .env
cp apps/api/.env.production.example apps/api/.env.prod
# Edite os dois arquivos e substitua todos os valores change-me.
```

O arquivo `.env.prod` deve usar `DB_HOST=db`, `MEILISEARCH_HOST=http://meilisearch:7700`,
`APP_ENV=production`, `APP_DEBUG=false`, SMTP real e `QUEUE_CONNECTION=database`.
Os caches `config`, `route` e `view` são gerados no startup do container PHP-FPM.
Com `VITE_API_URL` vazio, o Nginx do frontend encaminha `/api` internamente para
o serviço da API. Para uma API em subdomínio separado, defina `VITE_API_URL` no
`.env` da raiz com a origem da API, sem o sufixo `/api`.

O `APP_KEY` deve ser gerado antes do primeiro deploy (`php artisan key:generate`
ou usando uma chave já criada). O `DB_HOST` do arquivo `apps/api/.env.prod` deve
ser `db`, e o `MEILISEARCH_HOST` deve ser `http://meilisearch:7700`.

Subida da stack :

```bash
docker compose -f docker-compose.prod.yml up -d --build

docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
```

## Atualização da VPS a partir do GitHub

O clone na VPS não acompanha o GitHub automaticamente. Depois que um PR de
`develop` for promovido e integrado em `main`, atualize a VPS explicitamente:

```bash
cd /caminho/do/oiai-monorepo
git fetch origin
git switch main
git pull --ff-only origin main
docker compose --env-file .env -f docker-compose.prod.yml up -d --build
docker compose --env-file .env -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env -f docker-compose.prod.yml exec app php artisan optimize:clear
docker compose --env-file .env -f docker-compose.prod.yml exec app php artisan optimize
```

Os arquivos `.env` locais não devem ser sobrescritos pelo Git e permanecem
separados do código. Antes de atualizar, confira `git status` e faça backup do
banco. Se a VPS usa outro remote ou autenticação SSH, substitua `origin` pelo
remote correto.

A composição de produção não inicia Mailhog, phpMyAdmin, Vite, `artisan serve` ou
bind mounts do código-fonte. MySQL, Meilisearch e PHP-FPM ficam acessíveis apenas
na rede interna do Compose; não há portas públicas de infraestrutura.
