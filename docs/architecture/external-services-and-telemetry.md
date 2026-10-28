# Inventário de serviços externos e contrato de telemetria

## Escopo e decisão

Este documento inventaria as integrações externas encontradas no API do Óiaí e define o contrato que deverá ser usado nas próximas tarefas de instrumentação. A análise cobre `apps/api/app`, jobs, services, clients, configurações e filas.

Não havia uma integração ativa com Sentry, Bugsnag, OpenTelemetry ou outro APM no código/configuração inicial. A ferramenta escolhida para rastreamento central é **Sentry**, usando `sentry/sentry-laravel` e o mecanismo de exceções do Laravel. O logging estruturado local continua sendo a fonte operacional de baixo nível; o Sentry recebe exceções não tratadas e falhas externas relevantes, com agrupamento por fingerprint e sem PII.

## Inventário

| Provedor/serviço | Operação e ponto de chamada | Timeout/retry atual | Impacto da falha |
|---|---|---|---|
| **OSCBR** (`gtin.rscsistemas.com.br`) | Autenticação OAuth em `Services/Product/Providers/OscbrProductDataProvider::authenticate`; consulta de produto por EAN em `getProductData`; imagem pode ser baixada depois pelo `Helpers/ImageFromUrl` usando o header retornado | HTTP timeout de 20 s em autenticação e consulta; sem retry HTTP explícito. Jobs regulam volume por batch/recorrência/limite diário (`5`/`5 min`/`50` por padrão). Falha não fatal: retorna vazio e permite nova tentativa; 404 é definitivo | Produto não é enriquecido/validado; imagem pode não ser salva. O fluxo principal de cadastro continua, mas a qualidade do catálogo e da busca fica degradada |
| **Cosmos** (`api.cosmos.bluesoft.com.br`) | Consulta de dados de produto por GTIN em `CosmosProductDataProvider::getProductData` | HTTP timeout de 20 s; sem retry HTTP explícito. Job regula `7`/`180 min`/`45` por padrão. Falha não fatal; 404 definitivo | Mesmo impacto da OSCBR; usado como provedor de enriquecimento/suplementação conforme o status do produto |
| **SEFAZ/portais NFC-e — Pernambuco** (`nfce.sefaz.pe.gov.br`) | Consulta de QR Code/XML/HTML em `PernambucoNFCeProvider`; usado por `NFCeScraperService` e `ProcessInvoiceJob` | Guzzle `connect_timeout=15 s`, `timeout=20 s`, TLS verification atualmente desabilitado; sem retry explícito. Cache por hash do QR Code em `NFCeScraperService` | Importação da nota falha ou fica pendente; produtos/preços/empresa não são extraídos |
| **SEFAZ/portais NFC-e — Santa Catarina** (`sat.sef.sc.gov.br`) | Consulta pública em `SantaCatarinaNFCeProvider` | Mesmo client Guzzle: 15/20 s, sem retry explícito, TLS verification desabilitado | Igual ao fluxo NFC-e; apenas notas desse estado são afetadas |
| **SEFAZ/portais NFC-e — São Paulo** (`nfce.fazenda.sp.gov.br`) | Consulta pública em `SaoPauloNFCeProvider` | Mesmo client Guzzle: 15/20 s, sem retry explícito, TLS verification desabilitado | Igual ao fluxo NFC-e; apenas notas desse estado são afetadas |
| **Nominatim/OpenStreetMap** | Geocodificação de endereço em `Services/Geolocation/GeolocationService::search` | Sem timeout, retry ou limite explícito no código; `User-Agent` inclui nome/versão do app e `APP_MAIL`. Falha é capturada e retorna status `error` | Empresa/endereço fica sem coordenadas; recursos de proximidade podem não funcionar. Não bloqueia necessariamente a gravação |
| **Meilisearch** | Busca em `Services/Product/MeilisearchProductSearch`; health/metrics em `HealthController` e `SearchInfrastructureMonitor`; reindexação nos comandos `ReindexProducts`/Scout | Health usa timeout de 2 s; métricas usam timeout de 3 s; o cliente Meilisearch usa configuração Scout. Indexação é enfileirada por padrão (`SCOUT_QUEUE=true`). Falhas de indexação são monitoradas, não devem bloquear gravações | Busca pode ficar indisponível/desatualizada, enquanto o MySQL permanece fonte de verdade |
| **SMTP/Postmark/SES/Mailgun (configurável)** | Notificações de verificação/reset de senha via Laravel Mail; credenciais/transportes vêm de `MAIL_*`, `POSTMARK_TOKEN`, `AWS_*` ou configuração equivalente | Política depende do mailer/transport configurado; não há timeout/retry específico de provedor neste código | Usuário não recebe verificação ou reset de senha; autenticação continua gravada, mas pode ficar inutilizável |

### Dependências internas que iniciam os fluxos

- `ProductDataSearchJob` chama `ProductDataService`, registra tentativas por provedor em `ProductDataProviderAttempt` e controla disponibilidade/limites.
- `ProcessInvoiceJob` chama `NFCeScraperService` através das actions de invoice; falhas podem deixar a NFC-e pendente.
- `ImageFromUrl` é uma segunda chamada externa indireta para imagens retornadas por OSCBR/Cosmos. O URL e o corpo de erro devem ser tratados como dados não confiáveis.
- Scout/Meilisearch é assíncrono por padrão; o caminho de escrita não deve depender da disponibilidade do índice.

## Contrato comum de telemetria

Todo evento de chamada externa deve ser um registro estruturado com o evento `external_call` e, no mínimo:

```json
{
  "event": "external_call",
  "provider": "oscbr",
  "operation": "product.lookup",
  "environment": "production",
  "result": "success|not_found|failure|timeout|skipped",
  "http_status": 200,
  "duration_ms": 842,
  "failure_type": null,
  "correlation_id": "01J..."
}
```

Campos adicionais permitidos, desde que sanitizados:

- `component`: classe/job/command que iniciou a chamada;
- `attempt`: número da tentativa local;
- `retryable`: booleano;
- `resource_type`: `product`, `invoice`, `geocode`, `search`, `email`;
- `state`: UF quando a integração for NFC-e;
- `http_method` e `host`: somente método e host allowlisted, nunca URL completa com query/token;
- `queue` e `job`: quando a chamada ocorrer em worker;
- `response_keys`: nomes de chaves retornadas, sem valores;
- `error_class`: classe da exceção, sem mensagem completa quando ela puder conter dados.

### Resultados e falhas

| Campo | Valores recomendados |
|---|---|
| `result` | `success`, `not_found`, `failure`, `timeout`, `skipped`, `rate_limited`, `unauthorized` |
| `failure_type` | `network`, `timeout`, `dns`, `tls`, `http_4xx`, `http_5xx`, `auth`, `rate_limit`, `invalid_payload`, `dependency_unavailable`, `configuration`, `unknown` |
| `severity` | `debug` para sucesso detalhado; `info` para `not_found`/`skipped`; `warning` para falha recuperável, timeout, rate limit e payload inválido; `error` para falha definitiva que interrompe o fluxo; `critical` somente quando a indisponibilidade comprometer o serviço inteiro |

`http_status` deve ser inteiro ou `null` quando não houve resposta. `duration_ms` deve ser medido do início da tentativa até a resposta/exceção. `correlation_id` deve ser propagado do request HTTP ou job; se não existir, gerar UUID/ULID no início do fluxo e reutilizá-lo em todas as tentativas.

## Sanitização e privacidade

Nunca registrar por padrão:

- tokens Bearer, Basic Auth, API keys, senhas, cookies, cabeçalho `Authorization` ou `X-Cosmos-Token`;
- e-mail, nome, telefone, endereço completo, CPF/CNPJ, ID de usuário ou outros dados pessoais;
- EAN/GTIN, chave de acesso, QR Code ou URL completa de NFC-e quando puderem identificar uma compra;
- corpos de request/response, XML, HTML, imagens ou payloads brutos;
- mensagens de exceção sem filtragem, porque podem incorporar URL, headers ou resposta do provedor.

Permitido em modo controlado:

- IDs internos anonimizados ou hash HMAC com chave somente no ambiente;
- EAN/chave de NFC-e truncado ou hash, se necessário para correlação operacional;
- `response_keys`, tamanho do payload e códigos/status;
- host allowlisted, método, operação e classe da exceção.

Antes de instrumentar, substituir os logs atuais que incluem `url`, `image_url`, `error` bruto, `invoice_data` e `body` por campos sanitizados. Em particular, `ProcessInvoiceJob` hoje registra `invoice_data` completo e `ImageFromUrl` limita, mas ainda registra, o corpo de erro; ambos violam o contrato proposto.

## Pontos de instrumentação para as próximas tarefas

1. O `ExternalCallTelemetry` registra o evento no logger Laravel e, quando há `SENTRY_LARAVEL_DSN` fora de testes, envia falhas relevantes ao Sentry.
2. Instrumentar primeiro os limites de rede: OSCBR, Cosmos, os três providers NFC-e, Nominatim e `ImageFromUrl`.
3. Instrumentar Meilisearch separando `search`, `health`, `metrics` e `index`; manter gravação no MySQL independente.
4. Instrumentar mail apenas no boundary do Laravel Mail, registrando provedor/resultado/duração, nunca conteúdo da mensagem.
5. Propagar `correlation_id` de requests HTTP para jobs e de jobs para cada chamada externa; preservar o ID entre retries.
6. Definir dashboards/alertas por `provider`, `operation`, `result`, `failure_type`, p95 de `duration_ms` e taxa de `retryable`.
7. Adicionar testes unitários do recorder e testes de integração com `Http::fake()`/mock do Guzzle verificando sanitização, timeout, status e correlação.

## Configuração operacional

- Não adicionar credenciais, URLs secretas ou tokens em código, fixtures, logs ou documentação de deploy.
- Manter segredos em `.env`/secret manager: `OSCBR_API_LOGIN`, `OSCBR_API_PASSWORD`, `COSMOS_API_TOKEN`, `MEILISEARCH_KEY`, credenciais de e-mail e eventual chave HMAC de correlação.
- Adicionar flags de controle por ambiente, por exemplo `EXTERNAL_TELEMETRY_ENABLED` e `EXTERNAL_TELEMETRY_SAMPLE_RATE`, com produção habilitada e desenvolvimento ajustável.
- O Sentry usa `SENTRY_LARAVEL_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE`, `SENTRY_SAMPLE_RATE` e `SENTRY_TRACES_SAMPLE_RATE` somente via ambiente/secret manager. `SENTRY_SEND_DEFAULT_PII=false` é obrigatório.
- Alertas recomendados no projeto Sentry: `external-call` com `provider=oscbr` e `result` em `http_error`, `timeout`, `connection_error` ou `invalid_payload`; separar por ambiente e usar a fingerprint do evento para agrupar repetições.

## Dashboard e métricas no Sentry

O reporter publica métricas Sentry com somente labels de baixa cardinalidade:

- `external.calls`: contador de chamadas por `environment`, `provider`, `operation`, `result` e `failure_type`;
- `external.duration_ms`: distribuição de duração com os mesmos atributos;
- `external.timeouts`: contador de `timeout` e `connection_error`.

Não são usados `correlation_id`, usuário, EAN, URL, request ID ou qualquer valor por chamada como dimensão de métrica. A correlação permanece disponível apenas no evento de erro/log estruturado.

### Visão inicial recomendada

Criar no projeto Sentry um dashboard **External integrations** com widgets filtráveis por `environment` e `provider`:

1. volume: `external.calls` por hora, agrupado por `provider` e `operation`;
2. sucesso/erro: `external.calls` filtrando `result=success` versus demais resultados;
3. latência: p50/p95/p99 de `external.duration_ms`;
4. indisponibilidade: `external.timeouts` e `result in (timeout, connection_error)`;
5. OSCBR: filtro fixo `provider=oscbr`, com operações `auth.token` e `product.lookup`.

### Limiares iniciais de alerta

Os limiares abaixo são pontos de partida para produção e devem ser ajustados após uma semana de baseline. Todos usam janela de 15 minutos e exigem pelo menos 20 chamadas na janela, evitando ruído em baixo volume:

| Alerta | Condição inicial | Severidade |
|---|---|---|
| Taxa de erro externa | `(http_error + timeout + connection_error + invalid_payload) / calls >= 10%` e `calls >= 20` | warning |
| OSCBR indisponível | `timeout + connection_error >= 5` em 15 min | error |
| Latência OSCBR | p95 de `external.duration_ms >= 10s` para `provider=oscbr` | warning |
| Falha crítica persistente | taxa de erro `>= 50%` por 30 min e `calls >= 20` | critical |

Os valores devem ser configuráveis na regra do Sentry, não hard-coded na aplicação. Em ambientes diferentes de produção, começar com alertas desabilitados ou com janela/limiar mais altos.

### Acesso e operação

O dashboard deve ser criado no projeto Sentry da equipe, compartilhado com o grupo responsável pela API e vinculado às regras de alerta. O uso diário é: selecionar `environment=production`, filtrar `provider`, comparar volume/taxa de erro e abrir o evento agrupado para consultar `correlation_id`, operação e classe da exceção. O código não contém DSN nem permissões de acesso ao projeto.

## Validação ponta a ponta e triagem

### Configuração por ambiente

- **Desenvolvimento:** deixe `SENTRY_LARAVEL_DSN` vazio; métricas e eventos não são enviados. Use `Log::spy()`/`Http::fake()` nos testes.
- **Homologação:** use um projeto ou DSN separado, `SENTRY_ENVIRONMENT=staging`, `SENTRY_SEND_DEFAULT_PII=false` e uma taxa de tracing baixa. Nunca reutilize o DSN de produção se alertas estiverem ativos.
- **Produção:** configure o DSN no secret manager/container, `SENTRY_ENVIRONMENT=production`, `SENTRY_RELEASE` com o SHA do deploy e mantenha `SENTRY_SEND_DEFAULT_PII=false`.

Após alterar variáveis, limpe o cache de configuração (`php artisan config:clear` ou recrie a imagem) e valide que nenhum DSN aparece em `git diff`, logs ou respostas HTTP.

### Cenários reproduzíveis

`tests/Unit/Services/ExternalCallTelemetryTest.php` cobre, sem rede externa:

1. autenticação e consulta OSCBR com sucesso;
2. resposta HTTP 503;
3. falha de conexão;
4. payload inválido através do caminho de normalização;
5. reporter Sentry configurado, sem permitir que falha de observabilidade escape para o fluxo.

Para executar no container/API:

```bash
docker compose exec -T api php artisan test tests/Unit/Services/ExternalCallTelemetryTest.php
```

O teste deve confirmar eventos `external_call` com `provider=oscbr`, operação, duração e correlação. Também deve falhar se o contexto contiver tokens, senhas ou e-mails sentinela. Não use credenciais ou URLs reais no `Http::fake()`.

### Investigação de um alerta

1. No Sentry, filtre `environment=production`, `provider=oscbr` e o período do alerta.
2. Identifique se o agrupamento é `auth.token` ou `product.lookup` e observe `result`, `failure_type`, status HTTP e p95 de duração.
3. Copie o `correlation_id` do evento e procure o mesmo valor no log estruturado `external_call` e no job/request correspondente.
4. Diferencie `timeout`/`connection_error` (rede, DNS, indisponibilidade ou limite) de `http_error` (resposta do provedor) e `invalid_payload` (contrato alterado).
5. Verifique se a fila está acumulando tentativas e se o limite/recorrência da OSCBR está provocando rate limit.
6. Faça uma chamada sintética apenas em homologação. Não repita chamadas reais em produção para “testar” o incidente.
7. Se a OSCBR estiver indisponível, mantenha o fluxo de produto e deixe o job reprocessar conforme a política existente. Se houver payload inválido persistente, suspenda temporariamente o provider e abra incidente com o exemplo sanitizado.
8. Após recuperação, confirme queda de `external.timeouts`, retorno do p95 e sucesso de `auth.token`/`product.lookup` no dashboard.

### Segurança da validação

Os testes usam `test-login`, `test-password`, `secret-token` e e-mail sintético apenas como sentinelas. Esses valores nunca devem aparecer nos eventos. O DSN de homologação/produção é segredo de ambiente; testes não inicializam envio externo porque `app()->environment('testing')` bloqueia Sentry.

