# ADR 0005 — Buras para observabilidade das integrações

## Status

Accepted

## Context

O Óiaí já registra eventos estruturados de chamadas externas. A equipe quer consultar saúde, latência, falhas, correlação e alertas em um serviço próprio. A instalação deve ser leve, separada da API de negócio e versionada no monorepo.

## Decision

Criar `apps/buras`, serviço Go com API HTTP independente, SQLite em volume persistente e painel HTML servido pela mesma API. A API Laravel envia eventos allowlisted via job da fila existente. O Buras persiste cada evento, enfileira checagens passivas em SQLite e agenda uma sonda HTTP ativa opcional. Ambas produzem resultados consultáveis. O primeiro alerta usa falhas consecutivas e cooldown por ambiente, provedor e operação. O serviço é ativado por perfil Compose após configurar tokens de ambiente. CI e Release Please são independentes para `buras`.

## Alternatives considered

- serviço hospedado de rastreamento: menor esforço operacional, mas introduz dependência externa e cotas;
- plataforma auto hospedada de rastreamento: funcionalidade ampla, com infraestrutura maior que o escopo atual;
- segundo Laravel e MySQL: reaproveita a stack, mas compartilha falhas com a API e exige mais processos;
- Go com PostgreSQL: facilita réplicas, mas adiciona um serviço de dados antes de haver volume que o justifique.

## Consequences

O MVP usa um container e um volume, com baixo custo operacional. SQLite permite leitores simultâneos com um escritor em WAL no mesmo host; não é base para réplicas de escrita entre hosts. A API Buras pode evoluir para banco servidor e workers separados preservando o contrato HTTP e os eventos. O Buras no mesmo host da aplicação não detecta queda total da VPS; um monitor externo continua necessário. O MVP guarda classes de exceção, sem stack traces.
