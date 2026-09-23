# Visão geral da arquitetura

Oiaí é um monorepo composto por uma API Laravel e uma aplicação Web React/TypeScript.

```text
Web SPA ── HTTP/JSON + Sanctum ──> API Laravel ──> MySQL
                                      ├─ Meilisearch
                                      ├─ Queue/Scheduler
                                      └─ provedores de NFC-e e APIs externas
```

## Responsabilidades

- **API**: autenticação, autorização, regras de negócio, persistência, processamento de NFC-e, busca e jobs;
- **Web**: interface do usuário, navegação, formulários e consumo dos contratos HTTP;
- **MySQL**: fonte de verdade transacional;
- **Meilisearch**: índice derivado para busca textual, fuzzy, prefixos e ranking;
- **Queue/Scheduler**: processamento assíncrono e tarefas periódicas.

O índice de busca pode ser apagado e reconstruído a partir do MySQL. Ele não substitui o banco transacional.

## Documentos relacionados

- [Arquitetura da API](api.md);
- [Arquitetura do Web](frontend.md);
- [Decisões arquiteturais](decisions/).
