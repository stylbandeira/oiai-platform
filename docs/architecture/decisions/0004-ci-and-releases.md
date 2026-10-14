# ADR 0004 — CI e releases independentes

## Status

Accepted

## Context

Alterações na API não devem consumir o CI do Web, e releases de um componente não devem exigir alteração de versão no outro.

## Decision

Usar workflows filtrados por caminho, um `CI Gate` global, versões Semantic Versioning independentes e Release Please em modo monorepo, com PRs de versão direcionados a `develop`. A implantação de produção continua restrita a pushes em `main`, após promoção explícita por PR.

## Alternatives considered

- um único CI para todo o monorepo;
- versão única para API e Web;
- releases manuais.

## Consequences

O pipeline economiza recursos e torna a proteção da branch confiável, mas arquivos compartilhados precisam ser mantidos nos filtros de ambos os componentes. Tags e GitHub Releases são criadas quando os PRs de versão são integrados em `develop`; esses eventos não implantam produção. O PR `develop → main` é a fronteira de produção.
