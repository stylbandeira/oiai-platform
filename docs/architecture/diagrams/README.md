# Diagramas como código

## Convenções

- Um arquivo `.puml` por visão; nomes em `kebab-case` com o nível C4 no nome
  quando houver mais de uma visão (por exemplo, `system-context.puml` e
  `system-containers.puml`).
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
  com a fonte sempre que alterar um `.puml`.

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
  -tsvg docs/architecture/diagrams/system-containers.puml
```

O `GRAPHVIZ_DOT` explícito evita que uma configuração antiga aponte para outro
sistema (por exemplo `/opt/local/bin/dot`). `dot -V` mostra a versão instalada.

O comando escreve `system-containers.svg` ao lado do fonte. A renderização desta
base foi concluída com PlantUML 1.2025.2, C4-PlantUML 2.11.0, OpenJDK 11 e
Graphviz 2.43.0 em Ubuntu 20.04. Se `dot` estiver em um caminho diferente,
defina `GRAPHVIZ_DOT` para o caminho retornado por `command -v dot`, como no
comando acima.

## Fontes

- [`system-containers.puml`](system-containers.puml): visão C4 de containers.
- [`system-containers.svg`](system-containers.svg): saída PlantUML SVG versionada.
