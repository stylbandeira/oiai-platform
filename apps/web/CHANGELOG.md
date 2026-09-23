# Changelog — Web

Todas as alterações relevantes do frontend são registradas neste arquivo.

## [Unreleased]

### Added

- Pipeline independente de CI para lint, TypeScript, testes e build.
- Geração automática de tipos a partir do contrato OpenAPI.
- Tipagem de produtos, empresas, usuários, tabelas e paginação.

### Changed

- Login e registro passaram a consumir diretamente o usuário retornado pela API.
- Componentes administrativos passaram a usar tipos específicos em vez de `any`.

### Fixed

- Tratamento de erros Axios com fallback para mensagens locais.
- Tipagem de câmera, QR Code, notificações e tabelas responsivas.

## [1.0.0] - 2026-09-23

### Added

- Primeira versão versionada do frontend React/Vite.
- Telas de autenticação, dashboards, produtos e listas de compras.
- Execução independente via Docker.
