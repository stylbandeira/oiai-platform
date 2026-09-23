# Arquitetura da API

A API é uma aplicação Laravel organizada em Controllers, Actions, Services, Repositories, Policies, Resources e Jobs.

## Fluxo HTTP

```text
Route → Controller → Action/Service → Repository/Model → Resource/JSON
```

O MySQL permanece como fonte de verdade. O Scout mantém o índice do Meilisearch sincronizado para consultas de produtos. Dados externos de NFC-e são processados por providers e jobs, com falhas isoladas e notificáveis.

Autenticação usa tokens Laravel Sanctum. Respostas públicas da API são documentadas em [OpenAPI](../openapi.yaml).
