#!/usr/bin/env bash
# PostToolUse (matcher Bash) — commente une PR fraîchement créée avec son coût en tokens.
#
# Opt-in : à brancher dans .claude/settings.local.json
#          (voir .claude/settings.local.json.example).
# Non bloquant : sort TOUJOURS 0 pour ne jamais interrompre l'outil appelant.
# Idempotent : ne re-commente pas une PR déjà annotée (marqueur HTML).
#
# Reçoit l'input de l'outil en JSON sur stdin (cf. règle 11-security : lire via jq).
set -uo pipefail

INPUT="$(cat)"
CMD="$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty' 2>/dev/null)"

# Ne réagir qu'à la création d'une PR. `gh pr comment` (ci-dessous) ne matche pas
# ce motif → aucune récursion possible.
printf '%s' "$CMD" | grep -qE '\bgh\b.+\bpr\b.+\bcreate\b' || exit 0

DIR="${CLAUDE_PROJECT_DIR:-$(printf '%s' "$INPUT" | jq -r '.cwd // empty' 2>/dev/null)}"
DIR="${DIR:-$PWD}"
cd "$DIR" 2>/dev/null || exit 0

SCRIPT="$DIR/.claude/scripts/token-report.py"
[ -f "$SCRIPT" ] || exit 0
command -v gh >/dev/null 2>&1 || exit 0

BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null)"
[ -n "$BRANCH" ] || exit 0

URL="$(gh pr view --json url -q .url 2>/dev/null)"
[ -n "$URL" ] || exit 0

MARK="<!-- token-cost:${BRANCH} -->"
# Idempotence : ne pas re-poster si un commentaire de coût existe déjà pour cette branche.
if gh pr view "$URL" --json comments -q '.comments[].body' 2>/dev/null | grep -qF "$MARK"; then
  exit 0
fi

BLOCK="$(python3 "$SCRIPT" --pr "$BRANCH" 2>/dev/null)"
[ -n "$BLOCK" ] || exit 0
# Ne rien poster si aucune donnée de tokens pour la branche.
printf '%s' "$BLOCK" | grep -q 'Aucune activité' && exit 0

gh pr comment "$URL" --body "$MARK
$BLOCK" >/dev/null 2>&1

exit 0
