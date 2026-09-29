# Changelog — API

Todas as alterações relevantes da API são registradas neste arquivo.

## [2.0.0](https://github.com/stylbandeira/oiai-platform/compare/api-v1.0.0...api-v2.0.0) (2026-09-29)


### ⚠ BREAKING CHANGES

* **api:** monitor jobs, reindexing searching logs. prevent repeated reindexations.
* **api:** create searching improvement measure
* **api:** create new product renormalization
* **api:** create shopping list requirements, convertion using base unity, product combination contract, system now compare prices by unity quantity
* **api:** create product normalized decisions
* **api:** update asyncronically product indexes after creation and updation

### Features

* add fuzzysearch to search ([2cc1b0d](https://github.com/stylbandeira/oiai-platform/commit/2cc1b0dd8eef56b27f1b7c016110d28930319bb6))
* adjust .env-testing ([4b90786](https://github.com/stylbandeira/oiai-platform/commit/4b9078604233bf7f53804f794f6d6a4af08531d1))
* alter allowed origins ([6d4363e](https://github.com/stylbandeira/oiai-platform/commit/6d4363ed390025bfc97ece1627a22c5ebbcbb1f8))
* **api,web:** restore changes from pull request 20 ([9da2f7e](https://github.com/stylbandeira/oiai-platform/commit/9da2f7e3be7b2cebdb0dd730c6f7c6077a80abd8))
* **api:** adjusts product normalization confidence ([4206b47](https://github.com/stylbandeira/oiai-platform/commit/4206b4764e34fb1a2b1dda5cd4ca0d21416b10d3))
* **api:** adjusts route and validate decision via name similarity ([40ea9b2](https://github.com/stylbandeira/oiai-platform/commit/40ea9b2cec100e2feca704c1cfa834949cb3b432))
* **api:** alter convertion factor to decimal ([24fc412](https://github.com/stylbandeira/oiai-platform/commit/24fc412b861539cabb0f0e34dc89e817f90ee91d))
* **api:** alter convertion factor type ([05d52bb](https://github.com/stylbandeira/oiai-platform/commit/05d52bb66f500c46803d6860dd5351a066fa64d4))
* **api:** alter unities and convertion factor type ([274b619](https://github.com/stylbandeira/oiai-platform/commit/274b619d4afc28e0bc960e9e6b6d3116f888444d))
* **api:** apply quantity and unity when validated on product normalization ([7b2a6ac](https://github.com/stylbandeira/oiai-platform/commit/7b2a6ac0ce3060a3b0f03b3e4ca2b4c2b5e972ab))
* **api:** create milograma unity ([85c4d66](https://github.com/stylbandeira/oiai-platform/commit/85c4d6661b94dde954e9e9dabe21bb2e4cfaa705))
* **api:** create new product renormalization ([694e9be](https://github.com/stylbandeira/oiai-platform/commit/694e9be31f1dfff1af017282cf530aed4b303aa3))
* **api:** create product normalized decisions ([dc0c18d](https://github.com/stylbandeira/oiai-platform/commit/dc0c18d62d92b972d6817bc6fe4f0ab1ceb08ae1))
* **api:** create product search service using Meilisearch with MySQL fallback ([73d7f30](https://github.com/stylbandeira/oiai-platform/commit/73d7f30134b5c4afffc7df3d3122853803a08cda))
* **api:** create product_types table, controller and models ([d508b70](https://github.com/stylbandeira/oiai-platform/commit/d508b70e9fad2ae353387decdda9711ab736661e))
* **api:** create searching improvement measure ([1744181](https://github.com/stylbandeira/oiai-platform/commit/1744181d2b703aaced4eb911e8006dd2afa8ea12))
* **api:** create shopping list requirements, convertion using base unity, product combination contract, system now compare prices by unity quantity ([c9f0247](https://github.com/stylbandeira/oiai-platform/commit/c9f0247f6102a5ffa06d17306572f17c187360ac))
* **api:** define product normalization decisions confidence ([512fe29](https://github.com/stylbandeira/oiai-platform/commit/512fe294a24826737bbf57f9c9d3355bd0bd12e8))
* **api:** monitor jobs, reindexing searching logs. prevent repeated reindexations. ([104ac59](https://github.com/stylbandeira/oiai-platform/commit/104ac590df105fbbd422460ff98eb57eff4aa134))
* **api:** name, unity and quantity validation can happen on a single product ([a49f4ab](https://github.com/stylbandeira/oiai-platform/commit/a49f4abec6a9d916ed6f298202ff8565d7e4eb96))
* **api:** return refreshed unity when store normalization decision ([4c682ea](https://github.com/stylbandeira/oiai-platform/commit/4c682eaad271af0670c783c75b4da3a2295dc9e3))
* **api:** store normalizated date on products ([6b5bc15](https://github.com/stylbandeira/oiai-platform/commit/6b5bc15720c02c3f5cc6481e3ac43db2ef847434))
* **api:** update asyncronically product indexes after creation and updation ([bfc4872](https://github.com/stylbandeira/oiai-platform/commit/bfc487228531314768e7f9ac6fd3a1def4709664))
* creates CI and add PHPStan ([ee594a7](https://github.com/stylbandeira/oiai-platform/commit/ee594a795ba353f8b8fa672ce5004ec3edf1f22e))
* creates CI and add PHPStan ([b01e9aa](https://github.com/stylbandeira/oiai-platform/commit/b01e9aa6eb63a0022d02dbf59c7ba8085faa926e))
* install Meilisearch, configure and create Product Reindex command ([937265a](https://github.com/stylbandeira/oiai-platform/commit/937265ab52d3510023ff578a9854915a6ccbbc55))


### Bug Fixes

* pint fixes ([fb27887](https://github.com/stylbandeira/oiai-platform/commit/fb2788703871380424e57e2465efabc80af46872))


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

* **api:** test mySql product search, search logging, product name normalizer and product search ranker . ([d76b043](https://github.com/stylbandeira/oiai-platform/commit/d76b04352bc12885071fa67de677a331c137c7e9))
* **api:** test repeated correction reaches confidence and is reused ([a51a1dc](https://github.com/stylbandeira/oiai-platform/commit/a51a1dc57c574f06a7ebac4671f4e0259c6f4afc))
* **api:** test that when unity and quantity is setted, is removed from normalized_name ([e76030e](https://github.com/stylbandeira/oiai-platform/commit/e76030ef757ea3cc52644c1e265ce7e9e70091a6))
* **api:** update migration for testing ([2274d6b](https://github.com/stylbandeira/oiai-platform/commit/2274d6bd67b9247474b9dcdf18348c7918efc6cf))
* **api:** update product normalization decision tests ([dcae532](https://github.com/stylbandeira/oiai-platform/commit/dcae532a32f89df5dc538953dca975794afa4631))
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
