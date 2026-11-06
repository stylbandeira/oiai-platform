# Inventário de serviços externos e contrato de telemetria

## Escopo e decisão

Este documento inventaria as integrações externas encontradas no API do Óiaí e define o contrato que deverá ser usado nas próximas tarefas de instrumentação. A análise cobre `apps/api/app`, jobs, services, clients, configurações e filas.

A solução de rastreamento central é o [Buras](../../apps/buras/README.md), definida no [ADR 0005](decisions/0005-buras-observability.md). O logger Laravel continua registrando os eventos estruturados; o Buras recebe uma cópia sanitizada pela fila e guarda eventos, resultados de checagens e alertas em SQLite.

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

## Pontos de instrumentação e evolução

1. O `ExternalCallTelemetry` registra o evento no logger Laravel e envia os campos permitidos ao `BurasReporter`, que despacha `SendBurasEvent` pela fila.
2. OSCBR já usa esse caminho em autenticação e consulta de produto. Instrumentar os demais limites de rede: Cosmos, os três providers NFC-e, Nominatim, `ImageFromUrl` e operações de Meilisearch.
3. Instrumentar mail no limite do Laravel Mail, registrando provedor/resultado/duração, nunca conteúdo da mensagem.
4. Propagar `correlation_id` entre requests, jobs, retries e chamadas externas.
5. Ampliar as checagens e alertas no Buras por `provider`, `operation`, `result`, `failure_type`, p95 de `duration_ms` e taxa de erro.

## Configuração operacional

- Segredos ficam apenas no ambiente ou gerenciador de segredos: `OSCBR_API_LOGIN`, `OSCBR_API_PASSWORD`, `COSMOS_API_TOKEN`, `MEILISEARCH_KEY`, credenciais de e-mail e os tokens do Buras.
- Para ativar o Buras em produção, defina `BURAS_INGEST_TOKEN` e `BURAS_ADMIN_TOKEN` distintos, com pelo menos 32 caracteres, e `COMPOSE_PROFILES=buras` no `.env` da raiz da VPS. O Compose fornece o token de ingestão à API e ao worker Laravel.
- O painel em `http://127.0.0.1:8002/dashboard` usa usuário `buras` e a senha `BURAS_ADMIN_TOKEN`. Para acesso remoto, configure proxy HTTPS e controle de acesso.
- `BURAS_ACTIVE_ENABLED` é falso em produção por padrão. Ative apenas com `BURAS_PROBE_URL` seguro, sem credenciais ou query string; a sonda não deve consumir cotas da OSCBR.
- O Buras guarda somente campos permitidos pelo contrato. Não usa IDs de usuário, request, EAN, URL ou correlação como dimensão agregada.

## Painel, alertas e triagem

O painel Buras e `GET /v1/summary` mostram contagem de chamadas, sucessos, erros, timeouts e latência p95 por ambiente, provedor e operação. Filtre por `environment`, `provider` e `period` (de `1h` a `720h`). `GET /v1/events` aceita também `correlation_id`, para localizar a chamada correspondente ao log Laravel. `GET /v1/checks` e `GET /v1/alerts` mostram checagens e incidentes.

O alerta inicial é de falhas consecutivas da mesma combinação ambiente/provedor/operação dentro de uma janela: `BURAS_ALERT_FAILURES=5`, `BURAS_ALERT_WINDOW=1h`, `BURAS_ALERT_COOLDOWN=1h`. O limiar pode ser ajustado por ambiente no Compose. `not_found` e `skipped` não contam como falhas; um sucesso resolve o alerta. Um webhook HTTPS opcional pode ser definido em `BURAS_ALERT_WEBHOOK_URL`. Sem webhook, os alertas continuam visíveis no painel. A sonda ativa é independente dos eventos recebidos.

Para investigar uma falha:

1. Abra o painel, selecione o ambiente e filtre `provider=oscbr`.
2. Confira `auth.token` e `product.lookup`, o resultado, `failure_type`, status HTTP e p95.
3. Use `/v1/events?correlation_id=...` e procure o mesmo ID no log `external_call` e no job/request.
4. Diferencie `timeout`/`connection_error`, `http_error` e `invalid_payload`; verifique fila e limites de chamadas.
5. Após a recuperação, confirme um evento `success`, o alerta resolvido e melhora das contagens e latência no painel.

## Validação sem serviços externos

`tests/Unit/Services/ExternalCallTelemetryTest.php` e `tests/Unit/Services/BurasReporterTest.php` usam `Http::fake()` e `Queue::fake()` para validar sucesso, HTTP 503, falha de conexão, timeout, payload inválido, correlação e sanitização. O teste do job de envio cobre resposta de sucesso e indisponibilidade do destino sem interromper o fluxo. Os testes do Buras usam servidor HTTP e reporter locais; não enviam alertas externos.

Execute os testes da API no container:

```bash
docker compose exec -T api php artisan test tests/Unit/Services/ExternalCallTelemetryTest.php tests/Unit/Services/BurasReporterTest.php
```

Os valores sintéticos `test-login`, `test-password`, `secret-token` e e-mail de teste servem apenas como sentinelas de sanitização; nunca devem aparecer no evento emitido. O Buras não recebe corpos brutos nem mensagens de exceção. Para detalhes de operação, segurança, backup e testes do serviço, veja o [README do Buras](../../apps/buras/README.md).
