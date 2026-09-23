# ADR 0002 — Autenticação com Sanctum

## Status

Accepted

## Context

O Web é uma SPA independente e precisa autenticar chamadas à API sem compartilhar implementação de sessão do servidor.

## Decision

Usar tokens Bearer emitidos pelo Laravel Sanctum. Login, registro e `/user` retornam o mesmo contrato de usuário baseado em `UserResource`.

## Alternatives considered

- sessões Laravel compartilhadas;
- OAuth2 completo;
- JWT implementado manualmente.

## Consequences

O Web permanece desacoplado da sessão do Laravel e a API controla revogação e autorização. Clientes devem tratar tokens com segurança e acompanhar mudanças no contrato OpenAPI.
