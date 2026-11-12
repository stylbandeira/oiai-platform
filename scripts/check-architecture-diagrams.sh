#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
architecture_dir="$repo_root/docs/architecture"
plantuml_jar="${PLANTUML_JAR:-$HOME/.local/share/plantuml/plantuml-1.2025.2.jar}"
graphviz_dot="${GRAPHVIZ_DOT:-$(command -v dot || true)}"

if [[ ! -f "$plantuml_jar" ]]; then
  echo "PlantUML JAR not found: $plantuml_jar" >&2
  exit 1
fi
if [[ -z "$graphviz_dot" || ! -x "$graphviz_dot" ]]; then
  echo "Graphviz dot executable not found; install Graphviz or set GRAPHVIZ_DOT." >&2
  exit 1
fi

mapfile -t sources < <(find "$architecture_dir" -maxdepth 1 -type f -name '*.puml' -print | sort)
if [[ ${#sources[@]} -eq 0 ]]; then
  echo "No PlantUML sources found in $architecture_dir" >&2
  exit 1
fi

temporary_dir="$(mktemp -d)"
trap 'rm -rf "$temporary_dir"' EXIT

GRAPHVIZ_DOT="$graphviz_dot" java -Djava.awt.headless=true \
  -jar "$plantuml_jar" -failfast2 -tsvg -o "$temporary_dir" "${sources[@]}"

for source in "${sources[@]}"; do
  name="$(basename "$source" .puml)"
  rendered="$temporary_dir/$name.svg"
  committed="$architecture_dir/$name.svg"

  if [[ ! -f "$rendered" ]]; then
    echo "PlantUML did not render $source to $rendered. Ensure @startuml matches the source filename." >&2
    exit 1
  fi
  if [[ ! -f "$committed" ]]; then
    echo "Missing versioned SVG: $committed" >&2
    exit 1
  fi

  python3 - "$rendered" <<'PY'
import sys
import xml.etree.ElementTree as ET

path = sys.argv[1]
root = ET.parse(path).getroot()
if not root.tag.endswith("svg"):
    raise SystemExit(f"Rendered document is not SVG: {path}")
text = " ".join(root.itertext()).lower()
if "syntax error" in text or "error line" in text:
    raise SystemExit(f"PlantUML rendered an error diagram: {path}")
PY

  if ! cmp -s "$rendered" "$committed"; then
    echo "Stale SVG: $committed differs from its rendered source $source." >&2
    echo "Regenerate diagrams using docs/architecture/diagrams/README.md." >&2
    exit 1
  fi
done

for svg in "$architecture_dir"/*.svg; do
  [[ -e "$svg" ]] || continue
  source="$architecture_dir/$(basename "$svg" .svg).puml"
  if [[ ! -f "$source" ]]; then
    echo "Orphan SVG without a matching PlantUML source: $svg" >&2
    exit 1
  fi
done

echo "All architecture diagrams rendered successfully and match their versioned SVGs."
