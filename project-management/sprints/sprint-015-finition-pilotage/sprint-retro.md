# Rétrospective — Sprint 15 (Finition pilotage projet)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Format | Starfish ⭐ |
| Sprint | 15 — Finition pilotage projet (EPIC-002) |
| Points livrés | 10 / 10 |
| Contexte | Solo dev (cérémonies = jalons documentaires) |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du
> mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences, des
> ressources disponibles et de la situation. » — Norman Kerth

## Rappel du Sprint
- **Goal atteint ✅** : atterrissage historisé + courbe (US-079c #82), seuil de dérive charge par type + escalade (US-079b #83). **EPIC-002 bouclé à 100 %.**
- Métriques : 628 → **645 tests**, `make ci` vert à chaque merge, 2 migrations (+ RLS).

---

## ⭐ Observations (Starfish)

### 🟢 CONTINUER (ce qui fonctionne bien)
- **Contrainte de conception posée dès l'affinage + prouvée par test** : « `ComputeProjectMargins` inchangé » matérialisé par `ComputeProjectMarginsIsolationTest` (réflexion). La raison du report S14 est levée proprement.
- **Réutilisation de patterns existants** : `ChargeDriftThreshold` calqué sur `MarginDriftThreshold` (entité/provider/controller/template) → vélocité et cohérence.
- **Repli par défaut porté par le port du Domaine** (`ResolvedChargeDriftThreshold::default()`) : UI/App jamais liées à l'Infra (Deptrac vert).
- **TDD + `make ci` local comme gate** avant chaque merge : zéro régression.

### 🟡 COMMENCER (nouvelles idées)
- **Lancer `make cs-fix` + `rector-fix` avant le commit final** systématiquement (évite les cycles CS/Rector).
- **Nommer la source exacte d'un « seuil » (marge vs charge) dans le sprint-goal** — check d'affinage.

### 🔴 ARRÊTER (ce qui ne fonctionne pas)
- **`git add -A`** en présence d'overrides locaux : a mis en scène `compose.override.yaml` (contient `APP_SECRET`) → blocage gitleaks. Stager explicitement.

### ⬆️ PLUS DE
- **Tests-gardes d'invariance** (réflexion/architecture) pour verrouiller les décisions structurantes.
- **Anticipation du piège SchemaTool** : recenser les tables lues par la page dès qu'on ajoute un read/provider.

### ⬇️ MOINS DE
- **Allers-retours Rector/CS** (De Morgan, `instanceof` exclusif, imports) : chaque cycle CI coûte ~2 min.

---

## Thèmes & analyse

### Thème 1 — Hygiène des commits (secret staggé) · Votes ●●●●
**Problème** : un secret de dev a failli être commité via `git add -A`.
**5 Pourquoi** : add global → override local non ignoré → `compose.override.yaml` non gitignore → nécessaire pour `make up` (APP_SECRET) → jamais ajouté au `.gitignore`.
**Solution** : ajouter `compose.override.yaml` au `.gitignore` (Action 1).

### Thème 2 — Ambiguïté sémantique « seuil » · Votes ●●●
**Problème** : sprint-goal parlait de « seuil de marge » alors que EF-PRJ-15 vise le dépassement de **charge**. Levé en décomposition, mais risque d'implémentation erronée.
**Solution** : check d'affinage « source du seuil » (Action 3).

---

## 🎯 Actions Sprint 16

### Action 1 : Ignorer `compose.override.yaml`
| Attribut | Valeur |
|----------|--------|
| Description | Ajouter `compose.override.yaml` au `.gitignore` (secret de dev jamais staggé) |
| Deadline | S16 (5 min) |
| DoD | Le fichier n'apparaît plus dans `git status` ; gitleaks n'a plus à l'attraper |
| Priorité | Haute |
| Status | 🔵 À faire |

### Action 2 : Coloration du franchissement d'escalade sur la courbe (suite Review — arbitrage PO ✅)
| Attribut | Valeur |
|----------|--------|
| Description | Historiser `isEscalated` dans le snapshot et colorer (2e couleur) les points de la courbe qui franchissent le 2e seuil |
| Deadline | Follow-up immédiat S15 (petit PR) |
| DoD | Un point escaladé est visuellement distinct ; test fonctionnel couvrant l'affichage |
| Priorité | Moyenne |
| Status | 🔵 À faire (implémenté en suivi de cette rétro) |

### Action 3 : Check d'affinage « source du seuil/indicateur »
| Attribut | Valeur |
|----------|--------|
| Description | Au planning, nommer explicitement la source d'un indicateur ambigu (marge vs charge, brut vs retenu) dans le sprint-goal |
| Deadline | S16 (planning) |
| DoD | Le sprint-goal S16 nomme sans ambiguïté chaque indicateur clé |
| Priorité | Moyenne |
| Status | 🔵 À faire |

## Suivi actions précédentes

| Sprint | Action | Status |
|--------|--------|--------|
| S14 | Compléter les SchemaTool des tests atteints par un contrôleur enrichi | ✅ Fait (appliqué S15 : 2 tables ajoutées) |
| S10→ | MAILER staging à exécuter | ❌ Non fait (reconduit) |
| S12 | Finding R-01 (1er clic onglet Suivi budgétaire) | ❌ Non fait (reconduit) |

## Arbitrages PO (Sprint Review)
1. **Défaut du 2e seuil d'escalade = 25 %** → ✅ validé (conservé dans `ChargeDriftThresholdProvider::DEFAULT_ESCALATION_PERCENT`).
2. **Coloration escalade sur la courbe** → ✅ à faire (Action 2).
3. **S16 sur EPIC-001** (calendrier, compétences, statuts/circuits, onboarding) → ✅ confirmé.

## Vélocité
S8→S15 : 22 / 21 / 11 / 23 / 16 / 21 / 11 / **10**. Sprint court assumé (bouclage propre d'EPIC-002).

## ROTI
Solo dev — ROTI auto-évalué : **4/5** (objectif atteint, quelques cycles CS/Rector évitables).

## Prochaine étape
- **EPIC-002 : 100 %.** → `/workflow:start 016` sur EPIC-001.
