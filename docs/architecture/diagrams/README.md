# Diagramas como código

## Convenções

- Um arquivo `.puml` por visão; nomes em `kebab-case` com o nível C4 no nome
  quando necessário (por exemplo, `context.puml` e `containers.puml`).
- Mantenha decisões e histórico nos ADRs em `../decisions/`; os diagramas
  representam o estado observado no código/configuração e apontam para dúvidas
  ou opções explicitamente condicionais.
- Use C4-PlantUML pela standard library embutida do PlantUML:
  `!include <C4/C4_Container>`. Não use includes HTTP nem a branch móvel `master`.
- Renderer fixado em PlantUML 1.2025.2 e C4-PlantUML 2.11.0, incluído na
  standard library dessa versão do PlantUML.
  Atualize ambos na mesma alteração e valide todos os diagramas. A versão C4
  incluída é registrada no [histórico oficial do PlantUML](https://plantuml.com/changes).
- Os SVGs são artefatos versionados para leitura no GitHub. Regenere-os junto
  com a fonte sempre que alterar um `.puml`; não edite o SVG manualmente.
- Os `.puml` são a fonte de verdade. A checagem automatizada renderiza todas as
  fontes, valida o XML SVG e falha se um artefato versionado não corresponder à
  renderização atual. Isso verifica sintaxe e sincronização, não fidelidade
  semântica ao código.

## Gerar SVG

Requisitos: Java 11+, Graphviz (`dot`) e `curl`. Não é necessário Docker, login
ou conta em serviço de diagramas; PlantUML e C4 executam localmente. A
dependência é um JAR fixado em 1.2025.2, com C4-PlantUML 2.11.0 incluído.
No Ubuntu, instale os pacotes com:

```bash
sudo apt install openjdk-11-jre graphviz
```

Baixe o JAR oficial uma vez:

```bash
mkdir -p ~/.local/share/plantuml
curl -fL https://github.com/plantuml/plantuml/releases/download/v1.2025.2/plantuml-1.2025.2.jar \
  -o ~/.local/share/plantuml/plantuml-1.2025.2.jar
```

Na raiz do repositório, gere os SVGs:

```bash
GRAPHVIZ_DOT="$(command -v dot)" java -Djava.awt.headless=true \
  -jar ~/.local/share/plantuml/plantuml-1.2025.2.jar \
  -tsvg docs/architecture/context.puml docs/architecture/containers.puml \
  docs/architecture/components-api.puml \
  docs/architecture/product-list-flow.puml
```

O `GRAPHVIZ_DOT` explícito evita que uma configuração antiga aponte para outro
sistema (por exemplo `/opt/local/bin/dot`). `dot -V` mostra a versão instalada.

O comando escreve `context.svg`, `containers.svg`, `components-api.svg` e
`product-list-flow.svg` ao lado dos fontes. A
renderização desta base foi concluída com PlantUML 1.2025.2, C4-PlantUML 2.11.0,
OpenJDK 11 e Graphviz 2.43.0 em Ubuntu 20.04. Se `dot` estiver em outro
caminho, defina `GRAPHVIZ_DOT` para o resultado de `command -v dot`.

Para verificar todas as fontes e confirmar que os SVGs versionados estão
atualizados, execute `scripts/check-architecture-diagrams.sh` na raiz. Na
revisão visual, abra cada SVG e confira fronteiras, participantes, direções e
rótulos das relações contra o código/configuração citados no
[índice da arquitetura](../overview.md). O sucesso do renderizador não garante
que o diagrama descreva corretamente o sistema.

Exemplo de decisão: ao adicionar um fallback de busca MySQL quando Meilisearch
falha, atualize `components-api.puml` (dependência e armazenamento do catálogo)
e `product-list-flow.puml` (ramo de fallback observável na busca). Uma mudança
interna de consulta que preserve essas responsabilidades, dependências e o
fluxo não exige alteração visual; justifique isso no PR.

## Fontes

- [`../context.puml`](../context.puml): visão C4 de contexto do sistema.
- [`../context.svg`](../context.svg): renderização SVG versionada.
- [`../containers.puml`](../containers.puml): visão C4 de contêineres executáveis e armazenamentos.
- [`../containers.svg`](../containers.svg): renderização SVG versionada.
- [`../components-api.puml`](../components-api.puml): componentes significativos dentro da API Laravel.
- [`../components-api.svg`](../components-api.svg): renderização SVG versionada.
- [`../product-list-flow.puml`](../product-list-flow.puml): product search and shopping list creation sequence.
- [`../product-list-flow.svg`](../product-list-flow.svg): versioned SVG rendering.

Para regenerar todas as visões, a partir da raiz do repositório:

```bash
GRAPHVIZ_DOT="$(command -v dot)" java -Djava.awt.headless=true \
  -jar ~/.local/share/plantuml/plantuml-1.2025.2.jar -tsvg \
  docs/architecture/context.puml \
  docs/architecture/containers.puml \
  docs/architecture/components-api.puml \
  docs/architecture/product-list-flow.puml
```
