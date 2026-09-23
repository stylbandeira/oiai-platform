# Oiaí — Pesquisa colaborativa de preços

## Overview

Oiaí é uma plataforma colaborativa para pesquisa de preços. Usuários podem processar NFC-e, consultar produtos, criar listas de compras e encontrar opções considerando preço e distância.

O monorepo contém uma API Laravel e uma aplicação Web React/TypeScript, versionadas e publicadas independentemente.

## Architecture

```text
React/TypeScript Web (apps/web) ── HTTP/JSON + Sanctum ──> Laravel API (apps/api)
                                                              ├─ MySQL
                                                              ├─ Meilisearch
                                                              └─ Jobs/Scheduler e serviços NFC-e
```

## Monorepo Structure

```text
apps/api/                  Backend Laravel
apps/web/                  Frontend React/Vite
docs/openapi.yaml          Contrato OpenAPI
docs/CONTRIBUTING.md       Commits e contribuição
docs/VERSIONING.md         Versões e tags
.github/workflows/         CI, gate e releases
docker-compose.yml         Ambiente local
```

## Tech Stack

- PHP/Laravel, Sanctum, Scout e Meilisearch;
- MySQL;
- React, TypeScript, Vite e Tailwind CSS;
- Docker Compose;
- PHPUnit, Pint, PHPStan e ESLint;
- OpenAPI, `openapi-typescript`, GitHub Actions e Release Please.

## Requirements

Docker, Docker Compose e Git. Para execução fora dos containers: PHP 8.2+, Composer 2 e Node.js 20+.

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

`ci-api.yml` valida o Backend e `ci-web.yml` valida o Web. Os filtros por caminho evitam pipelines desnecessários. Alterações em `docker/**`, `docker-compose.yml`, `docs/openapi.yaml` ou workflows podem afetar ambas as aplicações. O `ci-gate.yml` detecta os componentes necessários e publica o check global `CI Gate / CI Gate result`, recomendado como obrigatório na proteção da branch.

## Versioning

API e Web usam Semantic Versioning independentemente:

- API: [apps/api/VERSION](apps/api/VERSION);
- Web: [apps/web/package.json](apps/web/package.json);
- CHANGELOG da API: [apps/api/CHANGELOG.md](apps/api/CHANGELOG.md);
- CHANGELOG do Web: [apps/web/CHANGELOG.md](apps/web/CHANGELOG.md);
- guia: [docs/VERSIONING.md](docs/VERSIONING.md).

Tags: `api-v1.0.0` e `web-v1.0.0`.

## Releases

O [Release Please](.github/workflows/release.yml) interpreta Conventional Commits, cria PRs de release separados, atualiza versões e changelogs, cria tags e publica GitHub Releases.

GitHub Releases é o registro oficial das versões publicadas. Cada release identifica explicitamente o componente (`API` ou `Web`), aponta para a tag correspondente e contém notas agrupadas em Features, Bug Fixes, Performance, Documentation e demais categorias configuradas.

## Documentation

- [OpenAPI/Swagger](docs/openapi.yaml) · [Swagger Editor](https://editor.swagger.io/?url=https://raw.githubusercontent.com/stylbandeira/oiai-platform/main/docs/openapi.yaml);
- [Contributing e Conventional Commits](CONTRIBUTING.md);
- [Versionamento](docs/VERSIONING.md);
- [CI API](.github/workflows/ci-api.yml) · [CI Web](.github/workflows/ci-web.yml) · [CI Gate](.github/workflows/ci-gate.yml);
- [GitHub Releases](https://github.com/stylbandeira/oiai-platform/releases).
- [Guias de migração e breaking changes](docs/releases/README.md).

Os [releases da API](https://github.com/stylbandeira/oiai-platform/releases?q=api-) e os [releases do Web](https://github.com/stylbandeira/oiai-platform/releases?q=web-) são publicados separadamente. O Release Please atualiza os CHANGELOGs junto com os PRs de release.

## Contributing

Siga o [guia de contribuição](docs/CONTRIBUTING.md), execute os testes da aplicação alterada e regenere os tipos TypeScript quando o OpenAPI mudar.

## License

Consulte `LICENSE` quando disponível.
