# Visão geral da arquitetura

Oiaí é um monorepo composto por API Laravel, Web React/TypeScript e Buras Go/SQLite.

```text
Web SPA ── HTTP/JSON + Sanctum ──> API Laravel ──> MySQL
                                      ├─ Meilisearch
                                      ├─ Queue/Scheduler
                                      ├─ provedores de NFC-e e APIs externas
                                      └─ Buras API → SQLite (telemetria)
```

## Responsabilidades

- **API**: autenticação, autorização, regras de negócio, persistência, processamento de NFC-e, busca e jobs;
- **Web**: interface do usuário, navegação, formulários e consumo dos contratos HTTP;
- **MySQL**: fonte de verdade transacional;
- **Meilisearch**: índice derivado para busca textual, fuzzy, prefixos e ranking;
- **Queue/Scheduler**: processamento assíncrono e tarefas periódicas;
- **Buras**: eventos sanitizados, checagens, painel e alertas de integrações.

O índice de busca pode ser apagado e reconstruído a partir do MySQL. Ele não substitui o banco transacional.

## Documentos relacionados

- [Arquitetura da API](api.md);
- [Arquitetura do Web](frontend.md);
- [Decisões arquiteturais](decisions/);
- [Buras](../../apps/buras/README.md).
