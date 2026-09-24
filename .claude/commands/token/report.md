---
description: Rapport de coût en tokens (par sprint, par PR/branche, ou global)
argument-hint: [--sprint N | --pr <branche> | --all]
---

# Rapport de coût en tokens

Agrège le coût en tokens des sessions Claude Code à partir des transcripts JSONL
(`~/.claude/projects/<slug>/*.jsonl`) et l'attribue par **branche → US → sprint**
via `.bmad/sprint-status.yaml`.

## Mécanisme

Chaque message assistant d'un transcript porte `gitBranch`, `timestamp` et un bloc
`usage` (input / output / cache écrit / cache lu / thinking). Le script
`.claude/scripts/token-report.py` :

1. balaie tous les transcripts du projet (slug dérivé automatiquement du repo) ;
2. agrège l'usage par `gitBranch` ;
3. résout chaque branche vers une US (`…us-NNN…`) puis vers un sprint via
   `.bmad/sprint-status.yaml` ; à défaut, une branche `…sprint-NN…` (planning,
   `docs/cloture-sprint-NN`) est rattachée directement au sprint ;
4. convertit en coût USD **indicatif** selon `.bmad/token-pricing.yaml`
   (`show_usd: false` pour masquer).

## Exécution

Toujours via Docker n'est pas requis (script Python pur, stdlib uniquement) :

```bash
# Rapport d'un sprint (pour la review / la rétro)
python3 .claude/scripts/token-report.py --sprint $ARGUMENTS

# Coût de la branche courante (pour la description de PR au push)
python3 .claude/scripts/token-report.py --pr $(git rev-parse --abbrev-ref HEAD)

# Diagnostic global (top 40 branches, dont le résidu sur main)
python3 .claude/scripts/token-report.py --all

# Coût des issues CORRIGÉES (branches fix/*), filtrable par sprint
python3 .claude/scripts/token-report.py --fixes --sprint $ARGUMENTS

# Coût sur une FENÊTRE temporelle (issue remontée = run recette/review, fix = run qa:fix)
# Accepte un id de session QA (REC-YYYYMMDD-HHMMSS) ou un ISO ; FIN optionnel (défaut : maintenant)
python3 .claude/scripts/token-report.py --window REC-20260130-143022
python3 .claude/scripts/token-report.py --window 2026-01-30T14:30:00Z 2026-01-30T16:00:00Z
```

Sans argument, le script rapporte la **branche git courante**.

## Issues remontées / corrigées (itération 3)

- **Corrigées** : `--fixes` agrège les branches `fix/*` `hotfix/*` `bugfix/*`
  (une branche peut couvrir plusieurs findings → attribution grossière assumée).
- **Remontées** : `--window <id-session-recette>` chiffre le run de `/qa:recette`
  ou de `/code-review` qui a produit les findings (l'id `REC-…` encode l'heure de
  début ; fin par défaut = maintenant). Idem pour le coût d'un run `/qa:fix`.

## Sortie

Un tableau markdown prêt à coller dans `sprint-review.md`, `sprint-retro.md` ou
un corps de PR. Le total sprint ventile par US ; le travail fait directement sur
`main` (exploration, clôture) apparaît en résidu via `--all` et n'est pas rattaché
à une US.

## Limites (à communiquer honnêtement)

- L'attribution repose sur la discipline « branche d'abord » : le travail non
  branché tombe dans le résidu `main`.
- Le snapshot d'une PR au push n'inclut pas encore les échanges de clôture de la
  session en cours.
- Le coût USD est notionnel sous abonnement — privilégier les volumes de tokens.
