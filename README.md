# Oiaí — Pesquisa colaborativa de preços

## Overview

Oiaí é uma plataforma colaborativa para pesquisa de preços. Usuários podem processar NFC-e, consultar produtos, criar listas de compras e encontrar opções considerando preço e distância.

O monorepo contém uma API Laravel, uma aplicação Web React/TypeScript e o Buras (observabilidade Go/SQLite), versionados e publicados independentemente.

## Architecture

```text
React/TypeScript Web (apps/web) ── HTTP/JSON + Sanctum ──> Laravel API (apps/api)
                                                              ├─ MySQL
                                                              ├─ Meilisearch
                                                              ├─ Jobs/Scheduler e serviços NFC-e
                                                              └─ Buras (eventos sanitizados → SQLite)
```

## Monorepo Structure

```text
apps/api/                  Backend Laravel
apps/web/                  Frontend React/Vite
apps/buras/                Observabilidade Go/SQLite
docs/openapi.yaml          Contrato OpenAPI
docs/CONTRIBUTING.md       Commits e contribuição
docs/VERSIONING.md         Versões e tags
docs/DEPLOYMENT.md         Deploy, VPS, persistência e rollback
.github/workflows/         CI, gate e releases
docker-compose.yml         Ambiente local
```

## Tech Stack

- PHP/Laravel, Sanctum, Scout e Meilisearch;
- Go e SQLite (Buras);
- MySQL;
- React, TypeScript, Vite e Tailwind CSS;
- Docker Compose;
- PHPUnit, Pint, PHPStan, Go test/vet e ESLint;
- OpenAPI, `openapi-typescript`, GitHub Actions e Release Please.

## Requirements

Docker, Docker Compose e Git. Para execução fora dos containers: PHP 8.2+, Composer 2, Node.js 20+ e Go 1.27+.

## Getting Started

### Environment

A API usa seu próprio arquivo de ambiente; não é necessário `.env` na raiz:

```bash
cp apps/api/.env.example apps/api/.env
```

Configure, na API, o Meilisearch com `MEILISEARCH_HOST=http://meilisearch:7700`.

### Running with Docker

```bash
docker compose up -d --build
```

- Web: http://localhost:3000
- API: http://localhost:8001
- Meilisearch: http://localhost:7700
- phpMyAdmin: http://localhost:8080
- MailHog: http://localhost:8025
- Buras (perfil opcional): http://127.0.0.1:8002/dashboard

```bash
docker compose logs -f app
docker compose logs -f oiai-front
```

### API e Web fora do Docker

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
cd apps/web && npm ci && npm run generate:api && npm run dev
```

## Testing

### API

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec app vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M
```

### Buras

```bash
cd apps/buras
go vet ./...
go test -race ./...
go build ./cmd/buras
```

### Web

```bash
cd apps/web
npm ci
npm run lint
npm run typecheck
npm test --if-present
npm run build
npm run check:api
```

## Continuous Integration

`ci-api.yml` valida o Backend, `ci-web.yml` valida o Web, `ci-buras.yml` valida o Buras e o CI de arquitetura renderiza todos os diagramas e verifica os SVGs versionados. Os filtros por caminho evitam pipelines desnecessários. Alterações em `docker/**`, `docker-compose.yml`, `docker-compose.prod.yml`, `docs/openapi.yaml` ou workflows podem afetar mais de uma aplicação. O `ci-gate.yml` detecta os componentes necessários e publica o check global `CI Gate / CI Gate result`, recomendado como obrigatório na proteção da branch.

## Environments

| Branch | Ambiente | Deploy |
| --- | --- | --- |
| `feature/*`, `fix/*`, etc. | Desenvolvimento local / revisão | Não; PR para `develop` executa CI |
| `develop` | Integração | Não; CI e Release Please podem executar |
| `main` | Produção (VPS) | Automático após push, se o CI Gate passar |

O deploy de produção ocorre no merge/push em `main` apenas quando as alterações
estão dentro dos caminhos monitorados pelo workflow. Pull requests, inclusive
PRs automáticos do Release Please, não fazem deploy. Consulte
[Deploy e operação em produção](docs/DEPLOYMENT.md) para configuração da VPS e
detalhes do pipeline.

## Versioning

API, Web e Buras usam Semantic Versioning independentemente:

- API: [apps/api/VERSION](apps/api/VERSION);
- Web: [apps/web/package.json](apps/web/package.json);
- Buras: [apps/buras/VERSION](apps/buras/VERSION);
- CHANGELOG da API: [apps/api/CHANGELOG.md](apps/api/CHANGELOG.md);
- CHANGELOG do Web: [apps/web/CHANGELOG.md](apps/web/CHANGELOG.md);
- CHANGELOG do Buras: [apps/buras/CHANGELOG.md](apps/buras/CHANGELOG.md);
- guia: [docs/VERSIONING.md](docs/VERSIONING.md).

Tags: `api-vX.Y.Z`, `web-vX.Y.Z` e `buras-vX.Y.Z`.

## Releases

O [Release Please](.github/workflows/release.yml) interpreta Conventional Commits, cria PRs de release separados, atualiza versões e changelogs, cria tags e publica GitHub Releases.

GitHub Releases é o registro oficial das versões publicadas. Cada release identifica explicitamente o componente (`API`, `Web` ou `Buras`), aponta para a tag correspondente e contém notas agrupadas em Features, Bug Fixes, Performance, Documentation e demais categorias configuradas.

## Documentation

- [OpenAPI/Swagger](docs/openapi.yaml) · [Swagger Editor](https://editor.swagger.io/?url=https://raw.githubusercontent.com/stylbandeira/oiai-platform/main/docs/openapi.yaml);
- [Contributing e Conventional Commits](CONTRIBUTING.md);
- [Versionamento](docs/VERSIONING.md);
- [Deploy e operação em produção](docs/DEPLOYMENT.md);
- [Checklist de validação de produção](docs/PRODUCTION_SECURITY_CHECKLIST.md);
- [CI API](.github/workflows/ci-api.yml) · [CI Web](.github/workflows/ci-web.yml) · [CI Buras](.github/workflows/ci-buras.yml) · [CI Gate](.github/workflows/ci-gate.yml);
- [GitHub Releases](https://github.com/stylbandeira/oiai-platform/releases).
- [Guias de migração e breaking changes](docs/releases/README.md).
- [Índice de arquitetura, diagramas C4, fluxo de negócio e ADRs](docs/architecture/overview.md).
- Os fontes `.puml` são a fonte de verdade dos diagramas; gere os SVGs e revise-os visualmente conforme o [fluxo de diagramas](docs/architecture/diagrams/README.md).
- [Observabilidade e telemetria das integrações](docs/architecture/external-services-and-telemetry.md);
- [Buras: uso, contrato e operação](apps/buras/README.md) · [OpenAPI do Buras](apps/buras/openapi.yaml) · [ADR](docs/architecture/decisions/0005-buras-observability.md).
- [Fluxo de engenharia](docs/ENGINEERING_WORKFLOW.md).

Os [releases da API](https://github.com/stylbandeira/oiai-platform/releases?q=api-), [do Web](https://github.com/stylbandeira/oiai-platform/releases?q=web-) e [do Buras](https://github.com/stylbandeira/oiai-platform/releases?q=buras-) são publicados separadamente. O Release Please atualiza os CHANGELOGs junto com os PRs de release.

## Contributing

Siga o [guia de contribuição](docs/CONTRIBUTING.md), execute os testes da aplicação alterada e regenere os tipos TypeScript quando o OpenAPI mudar.

## License

Consulte `LICENSE` quando disponível.
