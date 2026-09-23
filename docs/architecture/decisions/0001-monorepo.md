# ADR 0001 — Organização em monorepo

## Status

Accepted

## Context

API e Web evoluem de forma independente, mas compartilham contratos, documentação, infraestrutura e workflows.

## Decision

Manter API e Web no mesmo repositório, em `apps/api` e `apps/web`, com CI e versionamento independentes.

## Alternatives considered

- repositórios separados;
- aplicação full-stack única.

## Consequences

O compartilhamento de contratos e infraestrutura fica simples, enquanto filtros de caminho e tags independentes evitam acoplamento de releases. O CI precisa detectar corretamente os componentes afetados.
