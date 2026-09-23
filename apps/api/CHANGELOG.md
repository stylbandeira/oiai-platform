# Changelog — API

Todas as alterações relevantes da API são registradas neste arquivo.

## [Unreleased]

### Added

- Automação de CI, quality gate e releases independentes.
- Busca de produtos integrada ao Scout/Meilisearch.
- Documentação OpenAPI da API.

### Changed

- Respostas de autenticação padronizadas com `UserResource`.

### Fixed

- Registro público impedido de criar usuários administradores.

## [1.0.0] - 2026-09-23

### Added

- Primeira versão versionada da API Laravel.
- Autenticação, processamento de NFC-e, produtos, empresas e listas de compras.
- Integração com MySQL e Meilisearch.

### Security

- Autenticação baseada em Laravel Sanctum.
