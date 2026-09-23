# ADR 0003 — Meilisearch como índice derivado

## Status

Accepted

## Context

A busca de produtos precisa oferecer texto completo, fuzzy search, prefixos e ranking, sem substituir as garantias transacionais do MySQL.

## Decision

Usar Laravel Scout com Meilisearch como índice derivado. O MySQL continua sendo a fonte de verdade e o índice pode ser reconstruído por importação controlada.

## Alternatives considered

- manter apenas consultas `LIKE`;
- usar o full-text nativo do MySQL;
- substituir o MySQL por um mecanismo de busca.

## Consequences

As consultas são mais adequadas para busca textual, mas o sistema precisa lidar com sincronização, disponibilidade do Meilisearch e reindexação.
