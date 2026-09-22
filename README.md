# Oiaí - Pesquisa de preços

O projeto busca criar uma plataforma colaborativa para pesquisa de preços, foi criado para
auxiliar compradores que, além precisarem otimizar seus gastos, possuem pouco tempo disponível para
pesquisar as melhores opções de compra.

## Principais funcionalidades:

- Leitura de QRCode/inserção de código de notas fiscais(NFCe) para cadastro de itens
- Criação de lista de compras
- Otimização de lista de compras por preço/distância

### Disponibilidade
Atualmente, a leitura de notas fiscais está disponível apenas para os estados:
- Pernambuco
- Rio de Janeiro
- São Paulo

## Tecnologias utilizadas

### Backend
- PHP
- Laravel
- MySQL

### Frontend
- React
- TypeScript
- Tailwind CSS

### Infraestrutura
- Docker
- Docker Compose

## Arquitetura

O projeto está estruturado como monorepo, subdividindo-se em dois projetos. A API em Laravel orientada a SOLID implementa serviços para capturar dados de notas fiscais através de scrapping e Jobs que chamam versões gratuitas de APIs externas para refinar os dados obtidos. O frontend, criado inicialmente utilizando lovable, utiliza Client-Server com frontend SPA.

### Escopo dos CIs

Os workflows da API e do Web usam filtros por caminho. Alterações somente em `apps/api/**` executam o CI da API; alterações somente em `apps/web/**` executam o CI do Web. Alterações em arquivos compartilhados executam ambos. Atualmente são considerados compartilhados:

- `docker/**`;
- `docker-compose.yml`;
- `docs/openapi.yaml`, que gera os tipos consumidos pelo frontend;
- os próprios workflows quando alterados.

O workflow `CI Gate` é o check global recomendado para proteção das branches principais. Ele detecta as aplicações impactadas, aguarda somente o CI da API e/ou do Web quando necessário e publica o resultado final em `CI Gate result`. Configure esse check como obrigatório nas regras de proteção da branch; não configure os checks individuais filtrados por caminho como obrigatórios.

## Fluxo principal da aplicação

1. Usuário realiza cadastro de uma nota fiscal
2. Usuário cria uma lista de compras
3. Usuário define a distância e a localização para a qual pretende otimizar a lista
4. Uma lista com os mercados/itens mais em conta, ordenado por distância é mostrada.

## Como executar

### Documentação da API

- [Especificação OpenAPI](docs/openapi.yaml)
- [Abrir no Swagger Editor](https://editor.swagger.io/?url=https://raw.githubusercontent.com/stylbandeira/oiai-platform/main/docs/openapi.yaml)

### Contribuição e commits

Os commits seguem o padrão Conventional Commits, com scopes como `api`, `web`, `infra` e `ci`. Consulte o [guia de contribuição](docs/CONTRIBUTING.md) para tipos permitidos, breaking changes e exemplos.

### Pré-requisitos
* Docker
* Docker Compose
* Git

### Instalação

```bash
git clone https://github.com/stylbandeira/oiai-platform
cd projeto
docker compose up -d --build
```
