# Versionamento das aplicações

API e Web possuem ciclos de release independentes. Uma release de uma aplicação não exige alteração de versão na outra.

## Versões atuais

- API: [`apps/api/VERSION`](../apps/api/VERSION)
- Web: [`apps/web/package.json`](../apps/web/package.json)

Ambas usam Semantic Versioning (`MAJOR.MINOR.PATCH`):

- `PATCH`: correção compatível, como `1.2.0` → `1.2.1`;
- `MINOR`: funcionalidade compatível, como `1.2.1` → `1.3.0`;
- `MAJOR`: alteração incompatível, como `1.3.0` → `2.0.0`.

## Tags

As tags devem identificar explicitamente a aplicação:

```text
api-v1.0.0
api-v1.1.0
api-v1.1.1

web-v1.0.0
web-v1.1.0
web-v2.0.0
```

Uma tag `api-v*` só deve ser criada quando houver release da API. Uma tag `web-v*` só deve ser criada quando houver release do Web.

## Releases automáticas

O workflow `Release Please` acompanha a branch `main` e usa os Conventional Commits para criar Pull Requests de release independentes. O estado atual fica em `.release-please-manifest.json`.

Depois do merge de um PR de release, a automação atualiza a versão do componente, o CHANGELOG, cria a tag (`api-vX.Y.Z` ou `web-vX.Y.Z`) e publica o GitHub Release correspondente.

## GitHub Releases como registro oficial

Cada GitHub Release deve corresponder a uma única tag de componente:

| Componente | Tag | Changelog |
| --- | --- | --- |
| API | `api-vX.Y.Z` | [`apps/api/CHANGELOG.md`](../apps/api/CHANGELOG.md) |
| Web | `web-vX.Y.Z` | [`apps/web/CHANGELOG.md`](../apps/web/CHANGELOG.md) |

As notas da release são geradas a partir dos Conventional Commits e organizadas por categoria. Assim, é possível rastrear a alteração desde o commit até a tag, a versão, o CHANGELOG e a release publicada.

## Relação com commits

Os scopes `api` e `web` dos Conventional Commits ajudam a identificar a aplicação afetada. O incremento da versão deve considerar somente os commits da aplicação que está sendo publicada.

Exemplos:

```text
feat(api): add product ranking       # incrementa a API
fix(web): correct mobile pagination  # incrementa o Web
```

Uma alteração compartilhada pode exigir duas releases, mas as versões continuam sendo calculadas e publicadas separadamente.
