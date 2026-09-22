# Contribuindo

## Conventional Commits

As mensagens de commit devem seguir o formato:

```text
<tipo>(<escopo>): <descrição no imperativo>
```

O escopo é opcional, mas deve ser usado quando identificar claramente a área alterada.

### Tipos permitidos

- `feat`: nova funcionalidade; gera incremento **MINOR**;
- `fix`: correção de comportamento; gera **PATCH**;
- `refactor`: alteração estrutural sem mudança de comportamento;
- `test`: criação ou ajuste de testes;
- `docs`: documentação;
- `chore`: manutenção geral;
- `ci`: workflows e automação de CI/CD;
- `build`: alterações de build ou dependências;
- `perf`: melhoria de desempenho.

Commits `refactor`, `test`, `docs`, `chore`, `ci`, `build` e `perf` normalmente não geram release por si só. A ferramenta de release deve considerar o tipo e o histórico do projeto.

### Scopes do monorepo

- `api`: Backend Laravel e contratos da API;
- `web`: Frontend React/TypeScript;
- `infra`: Docker, Compose e infraestrutura;
- `ci`: GitHub Actions e quality gates;
- `docs`: documentação geral.

### Breaking changes

Uma alteração incompatível deve usar `!` após o scope ou incluir um rodapé `BREAKING CHANGE:`. Breaking changes geram incremento **MAJOR**.

```text
feat(api)!: change authentication response format
```

ou:

```text
refactor(api): replace legacy authentication payload

BREAKING CHANGE: login now returns access_token instead of token.
```

### Exemplos do projeto

```text
feat(api): add Meilisearch product ranking
fix(api): prevent duplicate NFC-e processing
refactor(api): extract product data provider
test(api): cover exact EAN search
feat(web): add product search filters
fix(web): preserve authentication after registration
refactor(web): replace admin table any types
ci: add monorepo quality gate
build(infra): update frontend container
docs: document OpenAPI generation
```

Use descrições curtas, objetivas e no imperativo. Não inclua o número da issue como substituto do resumo; quando necessário, ele pode ser adicionado no corpo ou no rodapé do commit.
