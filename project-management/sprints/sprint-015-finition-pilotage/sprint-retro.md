# Rétrospective — Sprint 15 (Finition pilotage projet)

**Directive Fondamentale** : chacun a fait de son mieux compte tenu de ce qu'il savait, de ses
compétences et des contraintes du moment. On cherche les améliorations de système, pas les coupables.

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Sprint | 15 — Finition pilotage projet (EPIC-002) |
| Points livrés | 10 / 10 |

## 🟢 Ce qui a bien marché
- **Décision « handler séparé » (US-079c)** payante : la contrainte « `ComputeProjectMargins` inchangé »,
  posée dès l'affinage et **matérialisée par un test-garde d'invariance**, a évité tout recouplage — la
  raison même du report en S14 est levée proprement.
- **Réutilisation du pattern `MarginDriftThreshold`** pour `ChargeDriftThreshold` : entité + provider +
  controller + template calqués, avec repli par constante sur le **port du Domaine** (Deptrac vert).
- **Piège SchemaTool anticipé** : ajout des tables (`charge_landing_snapshot`, `charge_drift_threshold`)
  aux schémas des tests fiche projet dès qu'un nouveau read/provider les interroge.

## 🔴 Ce qui a coincé
- **Ambiguïté « seuil de dérive »** au planning : le sprint-goal parlait de « seuil de marge » alors que
  le besoin (EF-PRJ-15) portait sur le **dépassement de charge** (constantes OBJ-2). Levée pendant la
  décomposition, mais aurait pu faire coder la mauvaise chose.
- **`git add -A` a mis en scène `compose.override.yaml`** (secret APP_SECRET) → blocage gitleaks au
  commit. Rappel : stager explicitement, jamais `-A` avec des overrides locaux non ignorés.
- **Filtres Twig `max`/`min`** confondus avec des fonctions (500 en test) — corrigé.
- **Rector/CS itératifs** : quelques allers-retours (De Morgan, `instanceof` exclusif, imports) qui
  coûtent un cycle CI chacun.

## 🎯 Actions d'amélioration (SMART)
1. **Ajouter `compose.override.yaml` au `.gitignore`** pour éliminer le risque de secret staggé — S16, 5 min.
2. **Pré-commit léger** : lancer `make cs-fix` + `rector-fix` avant le commit final pour éviter les
   cycles CS/Rector — habitude à systématiser.
3. **Au planning** : vérifier la source exacte d'un « seuil » (marge vs charge) et la nommer dans le
   sprint-goal — check d'affinage.

## Vélocité
S8→S15 : 22 / 21 / 11 / 23 / 16 / 21 / 11 / **10**. Sprint volontairement court (bouclage propre
d'EPIC-002 plutôt que d'embarquer une story EPIC-001 non affinée).

## Suivi
- **EPIC-002 : 100 %.** Le focus S16 repasse sur EPIC-001 (calendrier, compétences, statuts, onboarding).
- Dette reconduite : MAILER staging (à exécuter), finding R-01 (1er clic onglet Suivi budgétaire).
