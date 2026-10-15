# Fluxo de engenharia

Este é o fluxo padrão para alterações no Oiaí:

```text
develop → feature/fix/refactor/chore → Pull Request → CI Gate → develop → promotion PR → main → production
```

## 1. Criar a branch

Parta sempre de `develop` e use um dos prefixos aprovados:

```bash
git switch develop
git pull
git switch -c feature/api-product-search
```

Branches de trabalho devem usar `feature/*`, `fix/*`, `refactor/*` ou `chore/*`.
Branches de feature e correção devem abrir Pull Request para `develop`.
Não desenvolva diretamente em `main`.

Consulte as regras completas em [CONTRIBUTING.md](../CONTRIBUTING.md).

## 2. Implementar e testar

Altere somente a aplicação e os arquivos compartilhados necessários. Adicione ou atualize testes. Se o contrato da API mudar:

```bash
cd apps/web
npm run generate:api
npm run check:api
```

Execute localmente os comandos descritos na seção [Local checks](../CONTRIBUTING.md#local-checks).

## 3. Commit

Use Conventional Commits com scope:

```text
feat(api): add product search ranking
fix(web): preserve login after registration
```

Breaking changes devem usar `!` e possuir migration guide quando exigirem ação:

```text
feat(api)!: change authentication response format
```

## 4. Pull Request

Abra o PR para `develop` descrevendo objetivo, alterações, impacto, testes executados e eventuais migration guides.

O `CI Gate` detecta os caminhos alterados:

- API alterada → executa API CI;
- Web alterado → executa Web CI;
- ambos alterados → executa ambos;
- arquivos compartilhados alterados → executa os pipelines afetados.

O check obrigatório recomendado é `CI Gate / CI Gate result`. CI e Release são responsabilidades separadas: o CI valida o código; o Release Please só atua depois que alterações chegam a `develop`.

## 5. Merge

O merge ocorre somente após revisão aprovada e sucesso do gate. Não é necessário alterar versões manualmente durante o desenvolvimento.

## 6. Versionamento e promoção

Depois que alterações forem integradas em `develop`, o Release Please analisa os commits e pode criar PRs de versão independentes para API e Web:

```text
feat(api) → API release PR → api-vX.Y.Z → API GitHub Release
feat(web) → Web release PR → web-vX.Y.Z → Web GitHub Release
```

Ao integrar cada PR de release em `develop`, a automação atualiza a versão, CHANGELOG, tag e GitHub Release. Esses PRs não fazem deploy. Depois de integrar os PRs de versão desejados, abra um PR de promoção de `develop` para `main`. O merge em `main` representa a publicação em produção e ativa o CD. Uma release de API não altera a versão do Web.

## 7. Rastreabilidade

Uma alteração pode ser acompanhada por:

```text
commit → PR para develop → CI Gate → merge em develop → PR de release/tag → PR develop → main → deploy
```

Consulte os [CHANGELOGs](../README.md#versioning), [guias de migração](releases/README.md) e [ADRs](architecture/overview.md) para entender a evolução e as decisões do sistema.
