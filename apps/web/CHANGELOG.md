# Changelog — Web

Todas as alterações relevantes do frontend são registradas neste arquivo.

## [1.0.1](https://github.com/stylbandeira/oiai-platform/compare/web-v1.0.0...web-v1.0.1) (2026-09-24)


### Refactoring

* adjusts CI ([18b03ae](https://github.com/stylbandeira/oiai-platform/commit/18b03ae95b5b3af65619512027767eee72148fa8))
* adjusts userType and User type; and turns admin registration forbidden ([107d2e2](https://github.com/stylbandeira/oiai-platform/commit/107d2e26a268037ee195aea5767100ff676cabdc))
* alter types ([fd55dad](https://github.com/stylbandeira/oiai-platform/commit/fd55dad55a7ad6fa9426f5c6b2eea234dc6521af))
* axios error fallback ([5e1d1b8](https://github.com/stylbandeira/oiai-platform/commit/5e1d1b8e3a667523e8833413ce05d35f3777f92a))
* checked instead of read ([f9073c0](https://github.com/stylbandeira/oiai-platform/commit/f9073c08fe2ac67f456d03dbd5a9eb3f1e538b3c))
* correct pagination ([3a890c8](https://github.com/stylbandeira/oiai-platform/commit/3a890c88c8aad74e19ff874ec421f774d6f1768c))
* correct unkown error catch ([c15ed24](https://github.com/stylbandeira/oiai-platform/commit/c15ed2474adb5795ff4ac4a1d033c1fae8a3a36e))
* create export fields type for Product CSV, ([cb44f95](https://github.com/stylbandeira/oiai-platform/commit/cb44f951b12efbc18bebd125c3b2248f2bc7469d))
* define badges states instead of generic strings ([b946a08](https://github.com/stylbandeira/oiai-platform/commit/b946a08b46bf206fa32fc32c280953f2d26272a9))
* define CompanySummary subtype ([954ee7b](https://github.com/stylbandeira/oiai-platform/commit/954ee7b2c73679d1b5ddb23a77fbfd2c387376a6))
* documents backend, automatize frontend contracts, change any to front contracts ([3df4a2a](https://github.com/stylbandeira/oiai-platform/commit/3df4a2ad54e95e6a9e5dff9940e2a3e96827e2ff))
* initial CI refactors ([444aa17](https://github.com/stylbandeira/oiai-platform/commit/444aa17d8be28eb04d5a0b1aadb4730fa6a6ddf5))
* remove any from ResponsiveTable, User, Company, etc ([6537a80](https://github.com/stylbandeira/oiai-platform/commit/6537a80fee472760ccb117e5e3be3eefb4294334))
* remove any's ([43b380a](https://github.com/stylbandeira/oiai-platform/commit/43b380aaaeb6afc4fc312fda2f811f603dbec2d1))
* set two different contracts for UserFormData ([2ec2045](https://github.com/stylbandeira/oiai-platform/commit/2ec20454597e1af8e4a5203de7e91d291050ac39))
* uses a full product (ShoppingListProduct) ([92f84bf](https://github.com/stylbandeira/oiai-platform/commit/92f84bfe6975f7679dc3432102b03ffa0795d305))


### Documentation

* versioning ([c9ea36f](https://github.com/stylbandeira/oiai-platform/commit/c9ea36f13800cbbcf6d89eb03ca60277905c282b))
* versioning ([e7ab782](https://github.com/stylbandeira/oiai-platform/commit/e7ab7826f3e20de7ae6233c7782ab91ae736e759))


### Maintenance

* connect monorepo migration to main history ([d1eb7af](https://github.com/stylbandeira/oiai-platform/commit/d1eb7af6204d972a2236acb6ae2eb6452cf7703a))
* create changelog ([71e7966](https://github.com/stylbandeira/oiai-platform/commit/71e7966729eda9c8f3a7f80d798157d45aa85ac2))
* create changelog ([aed0ee9](https://github.com/stylbandeira/oiai-platform/commit/aed0ee9490837a8b43321973911fe01707f486e9))
* create frontend ci ([8a771f3](https://github.com/stylbandeira/oiai-platform/commit/8a771f31fa34092b29bdf461df0cac7edbb95467))
* create frontend ci ([ad026bb](https://github.com/stylbandeira/oiai-platform/commit/ad026bbe22cb8e1bdb9a49cb51724c2b32041a1c))
* final adjusts to migrate ([4099add](https://github.com/stylbandeira/oiai-platform/commit/4099add09349faddf857e14e8cd9a5232bd52df9))
* final adjusts to migrate ([68bb433](https://github.com/stylbandeira/oiai-platform/commit/68bb433d77215f476a1ae6b0f30a8d965d2a2bfc))

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
