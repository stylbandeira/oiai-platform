# Changelog — API

Todas as alterações relevantes da API são registradas neste arquivo.

## [1.1.0](https://github.com/stylbandeira/oiai-platform/compare/api-v1.0.0...api-v1.1.0) (2026-09-24)


### Features

* add fuzzysearch to search ([2cc1b0d](https://github.com/stylbandeira/oiai-platform/commit/2cc1b0dd8eef56b27f1b7c016110d28930319bb6))
* adjust .env-testing ([4b90786](https://github.com/stylbandeira/oiai-platform/commit/4b9078604233bf7f53804f794f6d6a4af08531d1))
* alter allowed origins ([6d4363e](https://github.com/stylbandeira/oiai-platform/commit/6d4363ed390025bfc97ece1627a22c5ebbcbb1f8))
* creates CI and add PHPStan ([ee594a7](https://github.com/stylbandeira/oiai-platform/commit/ee594a795ba353f8b8fa672ce5004ec3edf1f22e))
* creates CI and add PHPStan ([b01e9aa](https://github.com/stylbandeira/oiai-platform/commit/b01e9aa6eb63a0022d02dbf59c7ba8085faa926e))
* install Meilisearch, configure and create Product Reindex command ([937265a](https://github.com/stylbandeira/oiai-platform/commit/937265ab52d3510023ff578a9854915a6ccbbc55))


### Refactoring

* ?? ([3475812](https://github.com/stylbandeira/oiai-platform/commit/3475812336305cc4967f172f475aaa25dd47dde9))
* adjusts CI ([89a71e3](https://github.com/stylbandeira/oiai-platform/commit/89a71e36ad49c661f4a04ffb1ff45b3757964833))
* Adjusts user resource ([10aa249](https://github.com/stylbandeira/oiai-platform/commit/10aa2490b3b246125f4517ae3218a032d9038f5c))
* adjusts userType and User type; and turns admin registration forbidden ([107d2e2](https://github.com/stylbandeira/oiai-platform/commit/107d2e26a268037ee195aea5767100ff676cabdc))
* adjusts with PHPStan ([2b1a63c](https://github.com/stylbandeira/oiai-platform/commit/2b1a63c6a3605756b7fa8adeffe4ba1a73033f52))
* pint corrections ([cf85923](https://github.com/stylbandeira/oiai-platform/commit/cf85923bd6420f3efaaca176d9ebe1886e2f9928))
* returns user with notifications ([518947b](https://github.com/stylbandeira/oiai-platform/commit/518947b6114357261b803fef91c5e0cae24acb17))


### Documentation

* versioning ([c9ea36f](https://github.com/stylbandeira/oiai-platform/commit/c9ea36f13800cbbcf6d89eb03ca60277905c282b))
* versioning ([e7ab782](https://github.com/stylbandeira/oiai-platform/commit/e7ab7826f3e20de7ae6233c7782ab91ae736e759))


### Tests

* testing general CI ([5f3d8c2](https://github.com/stylbandeira/oiai-platform/commit/5f3d8c2e8b0ec7943e0c0e504e527c6cc37439ce))
* testing general CI ([1527da4](https://github.com/stylbandeira/oiai-platform/commit/1527da48804d7675197705753e7e084be6a9b026))


### Maintenance

* add larastan ([e73bb2c](https://github.com/stylbandeira/oiai-platform/commit/e73bb2c4b79810a90e12abf9b0e7299b7022885e))
* att dependencies ([8d01ed2](https://github.com/stylbandeira/oiai-platform/commit/8d01ed2853062055374e4de13b1932219181e822))
* connect monorepo migration to main history ([d1eb7af](https://github.com/stylbandeira/oiai-platform/commit/d1eb7af6204d972a2236acb6ae2eb6452cf7703a))
* correct with LaravelStan ([fc62d7e](https://github.com/stylbandeira/oiai-platform/commit/fc62d7e5964194d2adb62993345ffc6cd4bc7c0c))
* create changelog ([71e7966](https://github.com/stylbandeira/oiai-platform/commit/71e7966729eda9c8f3a7f80d798157d45aa85ac2))
* create changelog ([aed0ee9](https://github.com/stylbandeira/oiai-platform/commit/aed0ee9490837a8b43321973911fe01707f486e9))
* create frontend ci ([8a771f3](https://github.com/stylbandeira/oiai-platform/commit/8a771f31fa34092b29bdf461df0cac7edbb95467))
* final adjusts to migrate ([4099add](https://github.com/stylbandeira/oiai-platform/commit/4099add09349faddf857e14e8cd9a5232bd52df9))
* final adjusts to migrate ([68bb433](https://github.com/stylbandeira/oiai-platform/commit/68bb433d77215f476a1ae6b0f30a8d965d2a2bfc))
* migrate applications to monorepo ([5fda7c7](https://github.com/stylbandeira/oiai-platform/commit/5fda7c78bbf8bbb3a16b510b9b31eab9675611c6))
* pint corrections ([1a7b214](https://github.com/stylbandeira/oiai-platform/commit/1a7b2143a5ba554f442a5df77e972304656c123a))
* remove legacy frontend ([469936b](https://github.com/stylbandeira/oiai-platform/commit/469936b95b75304fd7dad8f753b4691e0d26d2d2))

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
