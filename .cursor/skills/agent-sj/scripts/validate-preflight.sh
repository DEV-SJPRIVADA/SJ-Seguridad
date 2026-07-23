#!/usr/bin/env bash
# Valida que exista FEAT-XXX en TASKS.md y su run log antes de implementar.
set -euo pipefail

FEAT_ID="${1:-}"
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"

if [[ -z "$FEAT_ID" ]]; then
  echo "Uso: validate-preflight.sh FEAT-XXX" >&2
  exit 1
fi

TASKS="$ROOT/docs/TASKS.md"
RUN_LOG="$ROOT/docs/runs/${FEAT_ID}-run-log.md"
errors=0

if [[ ! -f "$TASKS" ]]; then
  echo "ERROR: no existe docs/TASKS.md" >&2
  errors=1
elif ! grep -q "$FEAT_ID" "$TASKS"; then
  echo "ERROR: $FEAT_ID no aparece en docs/TASKS.md" >&2
  errors=1
fi

if [[ ! -f "$RUN_LOG" ]]; then
  echo "ERROR: no existe docs/runs/${FEAT_ID}-run-log.md" >&2
  errors=1
fi

if [[ "$errors" -ne 0 ]]; then
  echo "Preflight AgentSj: FALLO — crear FEAT y run log antes de implementar." >&2
  exit 1
fi

echo "Preflight AgentSj: OK ($FEAT_ID)"
