# Contributing to Oiaí

Obrigado por contribuir. Este documento descreve o fluxo esperado para alterações no monorepo.

Para uma visão ponta a ponta, consulte o [fluxo de engenharia](docs/ENGINEERING_WORKFLOW.md).

## Branch naming

Crie branches a partir de `main` usando um prefixo que indique o trabalho:

```text
feat/api-meilisearch
fix/web-login-error
refactor/api-product-service
docs/update-architecture
ci/add-quality-gate
```

Use nomes curtos, em kebab-case, e mantenha uma branch por alteração coerente.

## Commit convention

Use Conventional Commits:

```text
<type>(<scope>): <description>
```

Scopes principais: `api`, `web`, `infra`, `ci` e `docs`. Tipos permitidos incluem `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`, `build` e `perf`.

Breaking changes usam `!` ou um rodapé `BREAKING CHANGE:`:

```text
feat(api)!: change authentication response format
```

Veja exemplos adicionais em [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md).

## Pull Request workflow

1. Atualize sua branch com a base mais recente.
2. Faça a alteração e adicione os testes necessários.
3. Execute as verificações locais.
4. Abra um Pull Request direcionado a `main`.
5. Descreva o problema, a solução, os impactos e como validar.
6. Aguarde a revisão e todos os checks obrigatórios.
7. Resolva os comentários e faça o merge somente após aprovação.

Não misture alterações não relacionadas no mesmo Pull Request. Alterações de contrato da API devem atualizar `docs/openapi.yaml` e os tipos gerados do Web.

## Local checks

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
npm run generate:api
npm run check:api
npm run lint
npm run typecheck
npm test --if-present
npm run build
```

## CI behavior

O CI é separado por aplicação:

- alterações em `apps/api/**` executam o CI da API;
- alterações em `apps/web/**` executam o CI Web;
- `docker/**`, `docker-compose.yml`, `docs/openapi.yaml` e workflows podem executar ambos;
- `CI Gate / CI Gate result` é o check global recomendado para proteção da branch.

Uma falha em lint, análise estática, testes, tipagem ou build impede o gate de passar.

## Versioning and releases

API e Web usam Semantic Versioning independente. As tags são:

```text
api-vX.Y.Z
web-vX.Y.Z
```

O Release Please cria PRs de release separados, atualiza versões e CHANGELOGs, cria tags e publica GitHub Releases após o merge.

Consulte:

- [Versionamento](docs/VERSIONING.md);
- [CHANGELOG da API](apps/api/CHANGELOG.md);
- [CHANGELOG do Web](apps/web/CHANGELOG.md);
- [Guias de migração](docs/releases/README.md).

## Definition of done

Antes de solicitar revisão, confirme que:

- o código está formatado;
- os testes relevantes passam;
- não há erros de lint ou análise estática;
- contratos OpenAPI e tipos gerados estão atualizados;
- a documentação foi atualizada quando necessário;
- a mensagem dos commits segue a convenção.
