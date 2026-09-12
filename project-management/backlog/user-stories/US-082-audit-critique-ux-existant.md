# US-082: Audit et critique UX de l'existant (inspiré hotones)

## Métadonnées
- **ID**: US-082
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 20
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: Tous (P1–P6), focus P1 (saisie)
- **Créé le**: 2026-09-11
- **Mis à jour**: 2026-09-11 (affinage S20)

## Traçabilité
- **Implémente**: EPIC-013 (C3 — Critiquer l'UX de l'existant)
- **Dépend de**: US-080 (référentiel pages), US-081 (parcours personas) — livrés S19
- **Alimente**: US-084 (maquettes haute-fidélité) — les recommandations priorisées cadrent les maquettes
- **S'appuie sur**: accès `hotones` (captures fournies par le PO — dépendance levée S20)

## User Story

**En tant que** concepteur UX / Product Owner,
**je veux** une analyse critique et outillée de l'ergonomie des écrans actuels du produit, au regard des heuristiques de Nielsen et de l'ergonomie cognitive, en prenant le produit historique **hotones** comme source d'inspiration (à améliorer, pas à reproduire),
**afin de** disposer de recommandations d'amélioration priorisées qui orientent les maquettes haute-fidélité (US-084) et évitent de figer dans le reskin les défauts UX de l'existant.

## Contexte (Conversation)
Les écrans actuels (walking skeleton + EPICs métiers) ont été conçus au fil de l'eau, sans passe
ergonomique d'ensemble. Le parcours de **saisie du temps (P1, 80 % des utilisateurs)** est le plus
critique pour l'adoption (OBJ-7, RSQ-1) : une saisie perçue comme longue ou confuse fait échouer toute
la chaîne de valeur (complétude → valorisation → pilotage).

Le PO fournit des **captures d'écran de hotones** (produit historique) comme référence d'inspiration.
L'audit ne vise pas à copier hotones mais à en tirer les bons patterns et à **corriger ses faiblesses**,
en s'appuyant sur la richesse de composants du bundle `tailsfadmin`.

Décisions d'affinage (S20) — arrêtées :
- **Périmètre d'audit = écrans EXISTANTS comparables** : saisie hebdomadaire, saisie du jour, mes absences,
  complétude — en profondeur ; balayage transverse plus léger des autres écrans.
- **Exclusion DSH-COLLAB** : le **dashboard collaborateur (riche) n'existe pas dans le produit actuel**
  (écran typé *Build* au backlog reskin) → **hors périmètre d'audit produit** (rien à auditer). hotones
  n'a qu'une **home collaborateur** que le PO qualifie de **référence faible** (pas un vrai dashboard). Il est
  donc **conçu en US-084** à partir du parcours P1 (US-081), en s'inspirant au plus léger de cette home.
- **Grille d'analyse** : 10 heuristiques de Nielsen + charge cognitive (nombre d'actions, clics, lisibilité)
  + accessibilité WCAG 2.2 AA (contrastes, cibles ≥ 44 px, focus, clavier, pas d'action critique au survol).
- **Ancrage findings antérieurs** : relier chaque recommandation aux findings recette existants quand pertinent
  (F-S5-4, F-S5-5, F1, R-01…) pour ne pas ré-auditer à vide.
- **Source captures existant** : écrans réels via `make up` + `app:demo:seed` (compte `marc@demo.test`) ;
  captures **hotones** fournies par le PO — à déposer dans `design-canvas/hotones-ref/`. Le PO signale que les
  captures existantes peuvent être **incomplètes ou mal nommées** : normalisation du nommage au dépôt, et
  **captures complémentaires à sa main** si un écran comparable manque.
- **Format** : `project-management/architecture/audit-ux-existant.md` — une fiche par écran audité
  (constat, heuristique enfreinte, sévérité, recommandation, référence hotones/finding).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : audit approfondi des écrans existants du parcours saisie P1
```gherkin
GIVEN les écrans EXISTANTS du parcours de saisie EPIC-003 (saisie hebdo, saisie du jour, absences, complétude)
WHEN le concepteur produit `audit-ux-existant.md`
THEN chacun de ces écrans porte une fiche d'audit
  AND chaque fiche liste au moins un constat relié à une heuristique de Nielsen ou à un critère WCAG 2.2 AA
  AND chaque constat porte une sévérité (bloquant / majeur / mineur) et une recommandation actionnable
  AND le dashboard collaborateur (inexistant dans hotones et dans le produit) est explicitement noté hors audit
```

### CA-2 (Nominal) : inspiration hotones comparée, pas copiée
```gherkin
GIVEN les captures hotones fournies par le PO
WHEN un écran équivalent existe dans hotones et dans le produit
THEN la fiche d'audit compare les deux approches
  AND elle indique explicitement les patterns hotones à reprendre ET ses faiblesses à ne pas reproduire
  AND si aucune capture hotones comparable n'existe pour un écran, la fiche le signale et audite l'existant seul
```

### CA-3 (Alternatif) : recommandations priorisées et exploitables par US-084
```gherkin
GIVEN l'ensemble des recommandations de l'audit
WHEN le PO prépare les maquettes (US-084)
THEN les recommandations sont priorisées (MoSCoW ou sévérité) et regroupées par écran
  AND chaque recommandation est suffisamment précise pour être traduite en choix de maquette
```

### CA-4 (Alternatif) : ancrage sur les findings recette antérieurs
```gherkin
GIVEN les findings recette existants (F-S5-4, F-S5-5, F1, R-01…)
WHEN un finding concerne un écran audité
THEN la fiche d'audit le référence explicitement
  AND elle indique si la recommandation le résout, le confirme ou l'écarte
```

### CA-5 (Erreur) : écran existant du parcours P1 non audité
```gherkin
GIVEN un écran EXISTANT du parcours saisie EPIC-003 (saisie hebdo/jour, absences, complétude) absent de l'audit
WHEN le PO effectue la revue de validation
THEN il retourne la story en révision
  AND la story ne peut pas être Done tant que les 4 écrans existants du parcours P1 ne sont pas couverts
```

### CA-6 (Erreur) : recommandation non actionnable
```gherkin
GIVEN une recommandation vague (ex. « améliorer l'ergonomie ») sans constat ni heuristique rattachée
WHEN le PO valide le livrable
THEN il refuse la validation
  AND demande de rattacher la recommandation à un constat, une heuristique/critère et une sévérité
```

## Notes sur les livrables
- **Livrable** : `project-management/architecture/audit-ux-existant.md`
- **Captures** : `project-management/architecture/design-canvas/hotones-ref/` (déposées par le PO) + captures produit générées en recette.
- **Grille** : 10 heuristiques de Nielsen + charge cognitive + WCAG 2.2 AA.
- **Outillage** : agents `ux-ergonome`, `accessibility-expert` ; recette navigateur (Claude in Chrome) sur données peuplées.
- **Hors périmètre** : refonte du code ; production des maquettes (US-084) ; audit exhaustif des écrans de paramétrage (balayage léger seulement).
- **Validation** : revue PO ; fichier versionné sur la branche de sprint 20.

## Definition of Ready
- [x] Description INVEST (livrable documentaire borné ; persona « Tous », focus P1 ; estimé 5 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Format du livrable défini (une fiche par écran : constat/heuristique/sévérité/reco/référence)
- [x] **Périmètre priorisé arrêté** : parcours saisie EPIC-003 en profondeur, reste en balayage
- [x] **Dépendance levée** : accès hotones fourni par le PO (arbitrage 2026-09-11)
- [x] **Condition d'entrée** : US-080/081 livrées (S19) ; captures hotones déposées avant démarrage
- [x] Validation INVEST : Independent ✓ (US-080/081 déjà livrées) / Negotiable ✓ (grille adaptable) / Valuable ✓ (évite de figer les défauts UX dans le reskin) / Estimable ✓ (5 pts) / Sized ✓ (≤ 8 pts) / Testable ✓ (couverture parcours P1 + reco actionnables vérifiables)
- **DoR : ✅ LEVÉE — prête pour `/sprint:dev`** (sous réserve du dépôt des captures hotones)

## Definition of Done
- [x] `project-management/architecture/audit-ux-existant.md` créé et commité
- [x] 4 écrans existants du parcours saisie EPIC-003 audités (saisie hebdo/jour, absences, complétude) ; DSH-COLLAB noté hors audit
- [x] Comparaison hotones documentée là où une capture comparable existe (patterns à reprendre / faiblesses à éviter)
- [x] Recommandations priorisées, actionnables, rattachées à une heuristique/critère et une sévérité
- [x] Findings recette antérieurs pertinents référencés (F-S5-4, F-S5-5, F1 ; R-01 noté hors périmètre P1)
- [x] Revue et validation PO formalisées (revue + merge PR #122, 2026-09-14)
- [x] Branche versionnée, PR #122 mergée

> **Statut : ✅ DONE** (2026-09-14) — audit validé PO, PR #122 mergée.
