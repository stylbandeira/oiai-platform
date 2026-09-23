# ADR 0004 — CI e releases independentes

## Status

Accepted

## Context

Alterações na API não devem consumir o CI do Web, e releases de um componente não devem exigir alteração de versão no outro.

## Decision

Usar workflows filtrados por caminho, um `CI Gate` global, versões Semantic Versioning independentes e Release Please em modo monorepo.

## Alternatives considered

- um único CI para todo o monorepo;
- versão única para API e Web;
- releases manuais.

## Consequences

O pipeline economiza recursos e torna a proteção da branch confiável, mas arquivos compartilhados precisam ser mantidos nos filtros de ambos os componentes.
