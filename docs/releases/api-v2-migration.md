# API v2 — Guia de migração

> Documento de referência para consumidores que migrarem para a versão 2 da API.

## Motivo

A resposta de autenticação foi padronizada para usar o mesmo `UserResource` retornado pelo endpoint `/api/user`. O usuário deixa de ser retornado como Model cru e passa a seguir o contrato público documentado no OpenAPI.

## Alterações

Os endpoints afetados são:

- `POST /api/login`;
- `POST /api/register`;
- `GET /api/user`.

As respostas de login e registro continuam contendo o token, mas o campo `user` passa a utilizar o contrato do `UserResource`.

Registro:

```json
{
  "user": { "id": 1, "name": "...", "type": "client" },
  "access_token": "...",
  "token_type": "Bearer"
}
```

Login:

```json
{
  "user": { "id": 1, "name": "...", "type": "client" },
  "token": "..."
}
```

## Ação necessária

1. Não reconstrua o usuário no cliente a partir de campos individuais.
2. Use diretamente `response.user`.
3. Atualize clientes que esperavam o Model cru ou campos que não fazem parte do tipo do usuário correspondente.
4. Consulte o contrato atualizado em [`docs/openapi.yaml`](../openapi.yaml).

## Registro de administradores

O registro público aceita apenas `client` e `company`. Administradores devem ser criados por fluxo administrativo autorizado.

## Compatibilidade

Clientes que utilizam apenas `access_token`, `token` e os campos comuns do usuário não precisam de alterações adicionais. A versão anterior deve ser mantida durante a janela de migração definida na GitHub Release.
