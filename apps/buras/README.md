# Buras

API independente de observabilidade do Óiaí. O MVP recebe eventos estruturados da API Laravel, guarda-os em SQLite, avalia uma camada passiva de integração e executa uma sonda HTTP ativa opcional. O painel e a API administrativa mostram volume, falhas, timeouts, latência, checagens e alertas.

## Arquitetura

```text
API/queue Laravel -> job de envio -> POST /v1/events -> SQLite (volume)
                                            -> fila SQLite -> checagem passiva -> alerta
agendador Buras -> fila SQLite -> sonda GET ativa -> resultado
SQLite -> /v1/summary, /v1/events, /v1/checks, /v1/alerts, /dashboard
```

O envio ao Buras é opcional para o Óiaí. Se o Buras cair, o job de entrega termina sem afetar o fluxo de negócio; o evento estruturado permanece no log local Laravel. A fila de checagens do Buras é persistente. Nesta versão há um único worker dentro do processo Buras e um único arquivo SQLite local; a solução não compartilha o arquivo entre hosts. Para múltiplas réplicas ou escrita simultânea intensa, planeje migração para um banco servidor.

## Execução local

Defina no `.env` da raiz `BURAS_INGEST_TOKEN` e `BURAS_ADMIN_TOKEN` com **valores distintos de pelo menos 32 caracteres**. Gere cada um com `openssl rand -hex 32`. O Compose passa o token de ingestão também à API e à fila Laravel.

```bash
docker compose --profile buras up -d --build
```

Acesse `http://127.0.0.1:8002/dashboard`. O navegador pedirá usuário `buras` e senha `BURAS_ADMIN_TOKEN`. O painel só é publicado em loopback; para acesso remoto use proxy HTTPS com controle de acesso. A sonda local verifica `http://app:8001/api/health` a cada 5 minutos.

Para rodar sem Docker:

```bash
cd apps/buras
BURAS_INGEST_TOKEN=... BURAS_ADMIN_TOKEN=... BURAS_DB_PATH=./data/buras.sqlite go run ./cmd/buras
```

## Configuração

| Variável | Padrão | Uso |
| --- | --- | --- |
| `BURAS_INGEST_TOKEN` | obrigatório | Bearer token do emissor; nunca versione |
| `BURAS_ADMIN_TOKEN` | obrigatório | Senha Basic do painel e Bearer das consultas; distinta da ingestão |
| `BURAS_DB_PATH` | `/data/buras.sqlite` | Arquivo SQLite em volume persistente |
| `BURAS_LISTEN_ADDR` | `:8085` | Porta HTTP interna |
| `BURAS_PASSIVE_ENABLED` | `true` | Enfileira checagens de eventos recebidos |
| `BURAS_ACTIVE_ENABLED` | `false` | Agenda a sonda HTTP |
| `BURAS_PROBE_URL` | vazio | URL GET sem credenciais/query, exigida se a sonda estiver ativa |
| `BURAS_PROBE_ENVIRONMENT` | `production` | Ambiente da sonda |
| `BURAS_PROBE_INTERVAL` | `5m` | Mínimo de 1 minuto |
| `BURAS_PROBE_TIMEOUT` | `3s` | Máximo de 10 segundos |
| `BURAS_ALERT_FAILURES` | `5` | Falhas consecutivas para alertar, mínimo 2 |
| `BURAS_ALERT_WINDOW` | `1h` | Janela das falhas consecutivas |
| `BURAS_ALERT_COOLDOWN` | `1h` | Intervalo mínimo entre notificações da mesma integração |
| `BURAS_ALERT_WEBHOOK_URL` | vazio | Endpoint HTTPS opcional para POST de alerta sanitizado |
| `BURAS_RETENTION_DAYS` | `30` | Retenção de eventos e checagens |

A produção fica **desativada por padrão**. Depois de configurar os dois tokens no `.env` da raiz da VPS, defina `COMPOSE_PROFILES=buras` e faça o deploy. O Compose repassa o token à API, ao worker Laravel e ao Buras. `BURAS_ACTIVE_ENABLED` deve continuar falso até existir uma URL de saúde segura. Uma sonda não deve realizar consultas reais de produto nem consumir cota da OSCBR sem um endpoint específico para isso.

## Contrato e acesso

O contrato HTTP está em [openapi.yaml](openapi.yaml). `POST /v1/events` aceita apenas os campos do esquema e rejeita campos extras, como `token`, `body`, `payload` ou `url`. `event_id` e `correlation_id` devem ser ULID ou UUID. Provedor, operação e tipo de falha só admitem identificadores curtos; não envie dados pessoais. O corpo tem limite de 4 KiB. Envios repetidos com o mesmo `event_id` são idempotentes.

O painel usa autenticação HTTP Basic (`buras` + token administrativo). As consultas JSON também aceitam Bearer administrativo. `/healthz` é público somente na rede interna/porta de loopback. Não encaminhe a porta de ingestão diretamente para a Internet.

Filtros em `/v1/summary`, `/v1/events`, `/v1/checks` e `/dashboard`: `environment`, `provider`. `/v1/events` aceita também `correlation_id`. `/v1/summary` e `/dashboard` aceitam `period` entre `1h` e `720h` (padrão `24h`). O painel mostra até 100 eventos e 50 checagens recentes.

## Alertas e triagem

O primeiro alerta dispara após `BURAS_ALERT_FAILURES` falhas consecutivas da mesma combinação ambiente/provedor/operação dentro de `BURAS_ALERT_WINDOW`. `not_found` e `skipped` não contam como falhas. Um sucesso resolve o alerta. O painel mostra o estado mesmo sem webhook; com webhook configurado, o Buras envia somente ambiente, provedor, operação, quantidade de falhas e estado. Webhook falho não altera chamadas de negócio e é tentado no próximo evento elegível. Não há alerta de recuperação por webhook nesta versão.

Para investigar: filtre o painel por `production` e `oscbr`, confira `product.lookup` e `auth.token`, abra `/v1/events?correlation_id=...` e procure o mesmo ID no log Laravel. A sonda ativa mostra a saúde do endpoint configurado, não valida o contrato da OSCBR. Se o próprio host cair, o Buras no mesmo host também para; use monitor externo para detectar indisponibilidade total.

## Dados e segurança

O produtor Laravel faz allowlist antes da fila; a API do Buras rejeita campos desconhecidos. Não são armazenados request/response, EAN, URL, tokens, credenciais, mensagens de exceção ou stack traces. Exceções carregam apenas a classe e o identificador de correlação. Backups devem copiar o volume com o processo parado ou usar a API de backup do SQLite; copiar só o arquivo `.sqlite` enquanto o WAL está ativo pode perder dados. Retenção automática remove eventos e resultados antigos uma vez por dia. Monitore espaço em disco e faça backup fora da VPS.

## Testes e releases

```bash
cd apps/buras
go mod download
go mod verify
test -z "$(gofmt -l .)"
go vet ./...
go test -race ./...
go build ./cmd/buras
```

O `CI Gate` executa `Buras CI` em alterações relevantes. O Release Please mantém `VERSION`, `CHANGELOG.md`, tags `buras-vX.Y.Z` e GitHub Releases próprios. Use commits como `feat(buras): add probe check`. Veja [versionamento](../../docs/VERSIONING.md) e [deploy](../../docs/DEPLOYMENT.md).
