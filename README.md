# Proposta de Criação de MVP do app Óiaí

O Óiaí tem a intensão de se tornar um aplicativo para pesquisa de preços iterativo de grupo de pessoas; com intuito de facilitar e tornar mais baratas o ato de comprar insumos para pessoas comuns.

## O que deve ser feito:

O aplicativo deve permitir o registro e login de usuários de dois tipos: admin e usuário comum. Um usuário sem login não pode utilizar o app.

O aplicativo deve permitir que um usuário pesquise, em sua região ou numa região escolhida que pode variar entre 1 e 50km² os valores dos itens que ele selecionou em uma lista prévia. Os valores destes itens devem poder ser inseridos tanto automaticamente usando o site e a API do SEFAZ, fazendo leitura do código de uma nota fiscal ou do QRCode da mesma. Cada usuário e item adicionado devem possuir uma combinação de confiança que leva em conta a data de atualização deste item bem como a assertividade dos valores.

Ao finalizar a lista de compras, os itens não-marcados terão sua assertividade reduzida e, caso o usuário insira uma nova nota fiscal, os itens devem atualizar seus valores e assertividade.

O usuário deve poder analisar um item da lista para marcar o mesmo como valor errado, inserindo um valor novo; caso isso aconteça e outro usuário em menos de 24hrs insira uma nota fiscal com o valor anteriormente informado, este usuário que marcou o valor como errado deve ter sua confiança reduzida.

O app deve permitir dois tipos de lista: uma lista por estabelecimento e uma lista otimizada, que calculará os valores dos itens mais baratos e onde comprá-los, criando uma rota para o usuário fazer a feira em diversos estabelecimentos (a quantidade de estabelecimentos máxima deve ser 10 por padrão, mas o usuário deve ter a possibilidade de escolher outro valor a partir de 2) . Na tela onde são mostrados os estabelecimentos por onde o usuário terá que visitar, deve ser possível levar qualquer um deles para a lixeira, ao que os itens daquele estabelecimento serão redistribuídos para os estabelecimentos mais baratos dentro daqueles que sobraram.

A cor dos estabelecimentos devem ir de vermelho para verde de acordo com a quantidade de confiabilidade dos valores deles.

### Escala de Confiabilidade:

A escala de compatibilidade deve ser baseada em alguns fatores:

- Cliente
    - Assertividade dos preços das últimas 24 horas
    - Cadastro de notas fiscais
    - Inclusão e assertividade de até 90% nos valores de preços sem notas fiscais
    - Inclusão de novos estabelecimentos
- Empresa
    - Assertividade dos preços das últimas 24 horas
    - Quantidade de notas fiscais cadastradas nos últimos dias
    - Assertividade das notas fiscais cadastradas
    - Assertividade dos clientes que cadastram (notas ou preços)

### Database

https://drawsql.app/teams/styl-1/diagrams/oiai



## Ações e Instalações
npm install lucide-react
