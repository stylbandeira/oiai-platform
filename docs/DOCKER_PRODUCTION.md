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

Subida da stack (a partir da raiz do clone):

```bash
bash scripts/deploy-production.sh "$(git rev-parse HEAD)"
```

## Atualização automática da VPS a partir do GitHub

O workflow CI Gate executa o CD após um push em main, somente quando as
validações de API e frontend terminam com sucesso. Ele conecta por SSH à VPS,
atualiza o clone e recria a stack; nenhum git pull manual é necessário.

Configure estes secrets no GitHub, de preferência no environment production:

- VPS_HOST: hostname ou IP da VPS;
- VPS_PORT: porta SSH (opcional; padrão 22);
- VPS_USER: usuário de deploy;
- VPS_APP_PATH: caminho absoluto do clone na VPS;
- VPS_SSH_PRIVATE_KEY: chave privada usada pelo GitHub Actions para acessar a VPS;
- VPS_KNOWN_HOSTS: saída de ssh-keyscan -H <host> para fixar a chave do servidor.

O usuário de deploy precisa conseguir executar o Docker (por exemplo, estar no
grupo docker) e o clone precisa ter origin configurado para o GitHub. A chave
SSH da VPS e os arquivos .env continuam somente na VPS.

O clone na VPS não acompanha o GitHub automaticamente; o workflow faz essa
atualização após a integração em `main`. Para atualizar manualmente:

```bash
cd /caminho/do/oiai-monorepo
git fetch origin
git switch main
git pull --ff-only origin main
bash scripts/deploy-production.sh "$(git rev-parse HEAD)"
```

O job de deploy registra o `GITHUB_SHA` solicitado, confere o SHA do clone e
mostra o SHA de cada container da aplicação. Para conferir o commit que está
rodando na API (e não apenas o checkout atual), execute na VPS:

```bash
docker inspect --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}' \
  "$(docker compose --env-file .env -f docker-compose.prod.yml ps -q app)"
```

Substitua `app` por `web`, `api-web`, `queue` ou `scheduler` para conferir os
demais containers. O deploy falha se algum SHA não corresponder ao commit
solicitado. Os serviços de infraestrutura (`db` e `meilisearch`) não recebem
esse rótulo. Para executar `docker compose` diretamente nessa stack, exporte
`DEPLOY_SHA=$(git rev-parse HEAD)` antes; o script faz isso automaticamente.

Os arquivos `.env` locais não devem ser sobrescritos pelo Git e permanecem
separados do código. Antes de atualizar, confira `git status` e faça backup do
banco. Se a VPS usa outro remote ou autenticação SSH, substitua `origin` pelo
remote correto.

## Rollback para um commit anterior

Use o SHA completo de um deploy anterior que passou no health check. Ele pode
ser obtido nos logs do job `Deploy production to VPS` ou no rótulo do container
mostrado acima. **Não suponha que `HEAD^` foi a última versão saudável**: um
commit intermediário pode nunca ter sido implantado. Antes de prosseguir,
confirme que há backup do banco e evite iniciar outro deploy em paralelo.

Na VPS, na raiz do clone:

```bash
cd /opt/oiai/app
git status --short
SHA_ANTERIOR=cole_o_sha_completo_aqui
git show --no-patch --oneline "$SHA_ANTERIOR"
bash scripts/rollback-production.sh "$SHA_ANTERIOR"
```

O script aceita um SHA de commit ancestral do checkout atual, recusa alterações
locais em arquivos rastreados, faz checkout destacado do alvo, recompila e sobe
a stack, verifica o SHA de cada container e exige HTTP 200 em
`http://127.0.0.1:8001/api/health`. Não é necessário editar o código nem fazer
`git reset` ou `push`. O branch `main` local permanece intacto; o checkout fica
destacado até o próximo deploy ou até `git switch main`. Arquivos `.env` e
volumes persistentes, incluindo o banco, permanecem no lugar.

O rollback **não reverte migrações nem dados do MySQL**. Verifique antes se o
commit antigo funciona com o esquema atual. Se o health check falhar, o script
retorna erro e mantém o checkout no SHA alvo para diagnóstico; ele não tenta
reverter automaticamente o banco ou publicar outra versão. Para voltar à
versão mais recente após resolver o problema:

```bash
git switch main
git pull --ff-only origin main
bash scripts/deploy-production.sh "$(git rev-parse HEAD)"
```

A composição de produção não inicia Mailhog, phpMyAdmin, Vite, `artisan serve` ou
bind mounts do código-fonte. MySQL, Meilisearch e PHP-FPM ficam acessíveis apenas
na rede interna do Compose; não há portas públicas de infraestrutura.
