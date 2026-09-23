# Fluxo de engenharia

Este é o fluxo padrão para alterações no Oiaí:

```text
Branch → Conventional Commit → Pull Request → CI Gate → Merge → Release Please
```

## 1. Criar a branch

Parta de `main` e use um nome descritivo:

```bash
git switch main
git pull
git switch -c feat/api-product-search
```

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

Abra o PR para `main` descrevendo objetivo, alterações, impacto, testes executados e eventuais migration guides.

O `CI Gate` detecta os caminhos alterados:

- API alterada → executa API CI;
- Web alterado → executa Web CI;
- ambos alterados → executa ambos;
- arquivos compartilhados alterados → executa os pipelines afetados.

O check obrigatório recomendado é `CI Gate / CI Gate result`. CI e Release são responsabilidades separadas: o CI valida o código; o Release Please só atua após alterações chegarem à branch principal.

## 5. Merge

O merge ocorre somente após revisão aprovada e sucesso do gate. Não é necessário alterar versões manualmente durante o desenvolvimento.

## 6. Release

Após o merge em `main`, o Release Please analisa os commits e cria PRs independentes:

```text
feat(api) → API release PR → api-vX.Y.Z → API GitHub Release
feat(web) → Web release PR → web-vX.Y.Z → Web GitHub Release
```

Ao fazer merge do PR de release, a automação atualiza a versão, CHANGELOG, tag e GitHub Release. Uma release de API não altera a versão do Web.

## 7. Rastreabilidade

Uma alteração pode ser acompanhada por:

```text
commit → PR → CI Gate → merge → release PR → tag → CHANGELOG → GitHub Release
```

Consulte os [CHANGELOGs](../README.md#versioning), [guias de migração](releases/README.md) e [ADRs](architecture/overview.md) para entender a evolução e as decisões do sistema.
