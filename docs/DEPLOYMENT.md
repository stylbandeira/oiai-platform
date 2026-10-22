# Deploy e operação em produção

Este documento descreve o fluxo configurado neste repositório para publicar o
Oiaí na VPS, além dos serviços externos que precisam estar configurados. Os
comandos assumem que o clone está em `/opt/oiai/app`; ajuste esse caminho se a
VPS usar outro diretório.

## Branches, CI e releases

O fluxo de código é:

```text
feature/fix → PR para develop → CI → Release Please em develop
            → PR de promoção develop para main → CI → deploy na VPS
```

- `develop` é a branch de integração. Pull requests e pushes para ela executam
  o CI aplicável. O Release Please também acompanha `develop` e pode abrir PRs
  independentes de versão para API e Web.
- `main` representa produção. Abra um PR de promoção de `develop` para `main`;
  o CI valida o código antes do merge.
- O deploy automático acontece somente em `push` para `main`, depois do sucesso
  do job `CI Gate result`. Um PR de Release Please ou um push em `develop` não
  implanta na VPS.
- O CI detecta os caminhos alterados e executa os pipelines pertinentes: API
  (Composer, Pint, PHPStan e testes) e Web (npm ci, lint, typecheck, testes
  configurados e build). Alterações compartilhadas podem executar ambos.
- A publicação é uma atualização dos containers Docker existentes via
  `docker compose up -d --build`, seguida de migrações Laravel e health check.
  O workflow falha se o endpoint público de health check não retornar HTTP 200
  com todos os componentes saudáveis.

Consulte [VERSIONING.md](VERSIONING.md) para detalhes de versões e tags.

## GitHub Actions e secrets

O job de deploy no workflow `.github/workflows/ci-gate.yml` declara o GitHub
Environment `production`. Crie esse environment em **Repository → Settings →
Environments → New environment** e, idealmente, configure aprovação e
restrições da branch `main`. Adicione nele estes secrets (ou use repository
secrets, se preferir):

| Secret | Valor |
| --- | --- |
| `VPS_HOST` | IP ou hostname SSH da VPS |
| `VPS_PORT` | Porta SSH; opcional, o workflow usa `22` se omitido |
| `VPS_USER` | Usuário de deploy na VPS |
| `VPS_APP_PATH` | Caminho absoluto do clone, por exemplo `/opt/oiai/app` |
| `VPS_SSH_PRIVATE_KEY` | Chave privada autorizada para o usuário de deploy |
| `VPS_KNOWN_HOSTS` | Chave pública do servidor obtida e conferida por canal confiável |

O usuário SSH precisa acessar o clone e executar Docker Compose (normalmente
por pertencer ao grupo `docker`). O clone precisa ter `origin` configurado e
conseguir buscar `main` no GitHub. Não coloque chaves privadas, senhas ou
arquivos `.env` no Git. Secrets de release (`RELEASE_APP_CLIENT_ID` e
`RELEASE_APP_PRIVATE_KEY`) são documentados em [VERSIONING.md](VERSIONING.md);
eles não substituem os secrets de deploy.

## Preparar a VPS e Docker

Instale Docker Engine com o plugin Docker Compose, Git e Nginx no host. Clone o
repositório e prepare os arquivos de ambiente a partir dos exemplos:

```bash
git clone <URL-DO-REPOSITORIO> /opt/oiai/app
cd /opt/oiai/app
cp .env.production.example .env
cp apps/api/.env.production.example apps/api/.env.prod
chmod 600 .env apps/api/.env.prod
```

Edite ambos os arquivos e substitua todos os valores de exemplo. Use senhas
fortes e distintas para `DB_PASSWORD`, `DB_ROOT_PASSWORD` e
`MEILISEARCH_KEY`; configure `APP_KEY` e as credenciais reais de e-mail e
integrações. A configuração esperada inclui:

- no `.env` da raiz: credenciais do MySQL e Meilisearch, `API_ENV_FILE`,
  `WEB_PORT` e configuração de build do Web (`VITE_API_URL` etc.);
- em `apps/api/.env.prod`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`,
  `DB_HOST=db`, `MEILISEARCH_HOST=http://meilisearch:7700`, URLs e domínios
  corretos, fila e e-mail.

Não compartilhe os valores desses arquivos em logs ou chamados. A stack de
produção está em `docker-compose.prod.yml`: MySQL e Meilisearch não publicam
portas no host; API e Web escutam somente em `127.0.0.1:8001` e
`127.0.0.1:3000` (ou na porta definida por `WEB_PORT`). Os dados do MySQL,
Meilisearch e armazenamento Laravel ficam em volumes nomeados do Docker.

O primeiro deploy, a partir da raiz do clone, pode ser feito assim:

```bash
cd /opt/oiai/app
git switch main
git pull --ff-only origin main
bash scripts/deploy-production.sh "$(git rev-parse HEAD)"
```

O script exige o SHA do checkout, constrói e inicia os serviços, aplica
`php artisan migrate --force`, prepara o storage e otimiza o Laravel. Em cada
deploy os containers da aplicação recebem o SHA publicado como label. O
workflow também imprime o SHA solicitado e verifica o health check público.

## DNS, Nginx externo e SSL

DNS e TLS são configurados no provedor DNS e no Nginx do host da VPS; não são
criados pelo Docker Compose deste repositório. Configure os registros DNS dos
domínios do frontend e da API para apontar para o IP público da VPS. Os nomes
usados atualmente pelo projeto são `oiai.stylbandeira.tech` e
`api.oiai.stylbandeira.tech`; confirme os valores de `APP_URL`, `FRONTEND_URL`,
`SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` e `VITE_API_URL` antes de alterar
domínios.

No Nginx externo, encaminhe o domínio do frontend para `http://127.0.0.1:3000`
e o domínio da API para `http://127.0.0.1:8001`, preservando os cabeçalhos
`Host`, `X-Real-IP`, `X-Forwarded-For` e `X-Forwarded-Proto`. O Nginx interno do
frontend já encaminha `/api/` para a API quando `VITE_API_URL` está vazio; se o
frontend chamar o subdomínio de API diretamente, configure CORS para a origem
HTTPS exata do frontend.

O Nginx externo deve aceitar HTTP apenas para redirecionar para HTTPS e terminar
TLS com certificados válidos para os dois domínios:

```nginx
server {
    listen 80;
    server_name oiai.stylbandeira.tech api.oiai.stylbandeira.tech;
    return 301 https://$host$request_uri;
}
```

Mantenha blocos HTTPS separados para cada domínio, com `listen 443 ssl` e os
certificados correspondentes do Let's Encrypt, encaminhando para as portas
locais acima. Configure renovação automática com `certbot renew` via timer do
systemd e valide a configuração com `certbot renew --dry-run`.

Instale e renove certificados TLS no host (por exemplo, com Certbot/Let's
Encrypt, se esse for o método escolhido). Habilite redirecionamento HTTP para
HTTPS e valide a renovação automática. O workflow faz o teste de saúde em
`https://api.oiai.stylbandeira.tech/api/health`; portanto DNS, proxy e
certificado desse endereço precisam estar operacionais para o deploy passar.

## Health check e diagnóstico

O endpoint `GET /api/health` deve responder HTTP 200 com `status: ok` e checks
`application`, `database`, `queue` e `meilisearch` em `ok`. Na VPS:

```bash
cd /opt/oiai/app
docker compose --env-file .env -f docker-compose.prod.yml ps
docker compose --env-file .env -f docker-compose.prod.yml logs --tail=200 app api-web queue scheduler web
curl -i http://127.0.0.1:8001/api/health
```

O resultado de cada execução está em **GitHub → Actions → CI Gate**. Se não
houver execução, verifique se o push foi em `main` e se os arquivos alterados
estão cobertos pelos filtros `paths` do workflow. Se o job falhar, abra
`Deploy production to VPS` para ver a etapa: SSH/atualização, build/migração,
SHA dos containers ou health check externo.

Para identificar o commit da API que está rodando:

```bash
docker inspect --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}' \
  "$(docker compose --env-file .env -f docker-compose.prod.yml ps -q app)"
```

## Persistência e backups

Os volumes `dbdata_prod`, `meilisearch_data_prod` e `api_storage_prod` persistem
além da recriação dos containers. Isso evita perda em um rebuild, mas não
protege contra falha do disco/servidor, exclusão acidental ou corrupção. Nunca
use `docker compose down -v` em produção: `-v` remove os volumes.

**No estado atual, este repositório não agenda nem executa backup diário.** O
deploy também não cria backup automaticamente. Até que haja uma rotina
agendada, faça o dump antes de migrações ou rollback e mantenha cópias cifradas
fora da VPS. Um dump manual do banco pode ser feito na VPS:

```bash
cd /opt/oiai/app
set -o pipefail
umask 077
mkdir -p /var/backups/oiai
backup_file="/var/backups/oiai/mysql-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"
docker compose --env-file .env -f docker-compose.prod.yml exec -T db \
  sh -c 'exec mysqldump --single-transaction --routines --triggers -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$backup_file"
gzip -t "$backup_file"
```

Proteja `/var/backups/oiai` com permissões restritas e copie o arquivo para
armazenamento externo cifrado. Uma política diária deve definir retenção,
monitorar falhas e testar restauração periodicamente. O dump acima cobre o
MySQL, não os uploads do volume `api_storage_prod`; se houver arquivos enviados
por usuários, faça também backup consistente desse volume. O índice Meilisearch
é derivado dos dados e pode exigir reindexação; não o trate como substituto do
backup do MySQL.

Restaure somente em uma instância isolada ou após confirmar o alvo. Um exemplo
de importação no banco existente (substitua o arquivo conscientemente):

```bash
gzip -dc /var/backups/oiai/mysql-AAAAMMDDTHHMMSSZ.sql.gz \
  | docker compose --env-file .env -f docker-compose.prod.yml exec -T db \
      sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

Importar sobre um banco preenchido não é uma operação de merge segura. Para
validar a restauração, prefira um ambiente isolado com banco vazio; depois
verifique tabelas e contagens esperadas, execute health check e teste fluxos
essenciais. Registre data, origem do dump e resultado do teste.

## Rollback

Use um SHA completo de um deploy anterior conhecido como saudável. Na VPS, a
partir da raiz do clone:

```bash
cd /opt/oiai/app
git status --short
SHA_ANTERIOR=<sha-completo-conhecido>
git show --no-patch --oneline "$SHA_ANTERIOR"
bash scripts/rollback-production.sh "$SHA_ANTERIOR"
```

O script valida o SHA, faz checkout destacado, reconstrói os containers e
confere os labels e o health check local. Consulte [DOCKER_PRODUCTION.md](DOCKER_PRODUCTION.md)
para as limitações e como voltar ao `main` atual. Rollback de código não desfaz
migrações nem restaura dados. Se o problema envolver schema ou dados, avalie o
plano de restauração do backup em vez de presumir que voltar o código basta.
