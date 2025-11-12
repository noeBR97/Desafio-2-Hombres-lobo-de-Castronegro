#!/usr/bin/env bash
set -euo pipefail

MSG_FILE="$1"
MAX=200
violations=()

# Permite el commit si el mensaje contiene "instal" (install/instalar/instalación…)
if grep -qi "instal" "$MSG_FILE"; then
  exit 0
fi

exts='\.(php|js|jsx|ts|tsx|css|scss|sass|vue|html)$'

mapfile -t files < <(
  git diff --cached --name-only --diff-filter=ACM \
  | grep -E "$exts" \
  | grep -Ev '^(node_modules|vendor|dist|build|coverage|public/build)/' \
  || true
)

[ ${#files[@]} -eq 0 ] && exit 0

count_loc() {
  git show ":$1" 2>/dev/null | awk '
    BEGIN { loc=0 }
    {
      line=$0
      sub(/^[[:space:]]+/, "", line)
      if (line ~ /^[[:space:]]*$/) next
      if (line ~ /^\/\//) next
      if (line ~ /^#/) next
      if (line ~ /^\*/) next
      if (line ~ /^\/\*/) next
      if (line ~ /\*\/$/) next
      loc++
    }
    END { print loc }
  '
}

for f in "${files[@]}"; do
  loc=$(count_loc "$f" || echo 0)
  if [ "$loc" -gt "$MAX" ]; then
    violations+=("$f ($loc LOC)")
  fi
done

if [ ${#violations[@]} -gt 0 ]; then
  echo ""
  echo "Commit bloqueado: archivos con más de $MAX líneas de código:"
  for v in "${violations[@]}"; do
    echo "  - $v"
  done
  echo ""
  echo "Para forzar, incluye \"instal\" en el mensaje (p. ej.: \"chore: instal deps\")."
  echo ""
  exit 1
fi

exit 0
