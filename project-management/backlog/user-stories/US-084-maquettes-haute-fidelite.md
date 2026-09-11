# US-084: Maquettes haute-fidélité des écrans prioritaires (design-canvas)

## Métadonnées
- **ID**: US-084
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 20
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P1 (Camille, saisie) en priorité ; managers (complétude)
- **Créé le**: 2026-09-11
- **Mis à jour**: 2026-09-11 (affinage S20)

## Traçabilité
- **Implémente**: EPIC-013 (C5 — Concevoir les maquettes haute-fidélité)
- **Dépend de**: US-082 (audit UX — recommandations priorisées), US-083 (mapping composants), US-081 (parcours) — S19/S20
- **Alimente**: le **dev reskin EPIC-003** (Sprint 21) — maquettes validées = prérequis du dev front (règle PO)
- **Gate**: validation PO tracée dans `design-canvas/VALIDATION.md`

## User Story

**En tant que** Product Owner,
**je veux** des maquettes haute-fidélité des écrans prioritaires du parcours de saisie collaborateur (EPIC-003), alignées sur le thème `tailsfadmin`, améliorant hotones et intégrant les recommandations de l'audit UX (US-082),
**afin de** figer visuellement et faire valider *quoi* construire avant tout dev front, garantissant que le reskin (Sprint 21) part d'une cible validée plutôt que d'une interprétation au fil de l'eau.

## Contexte (Conversation)
Consigne PO permanente (mémoire projet) : **la conception UX/UI — maquettes validées — précède tout dev
front, à chaque sprint.** Le Sprint 20 conçoit le parcours saisie EPIC-003 (P1, 80 % des users), qui sera
développé au Sprint 21. Cette story produit les maquettes cibles à partir du référentiel (US-080), des
parcours (US-081), du mapping composants `tailsfadmin` (US-083) et des recommandations de l'audit (US-082).

Écrans prioritaires (backlog reskin US-085, bloc Must EPIC-003) :
1. **DSH-COLLAB** — Dashboard collaborateur (point d'entrée P1 ; contrepartie visible : planning/feedback).
   ⚠️ **Conception ouverte** : ce dashboard riche **n'existe pas dans le produit** (typé *Build*). hotones offre
   seulement une **home collaborateur** = **référence faible** (`hotones-ref/home-collaborateur.png`). Sa maquette
   part du parcours P1 (US-081) et des composants tailsfadmin, en s'inspirant au plus léger de cette home.
   Écran le plus ouvert en conception.
2. **PG-TMP-01** — Saisie hebdomadaire (écran le plus critique ; objectif ≤ 2 min ; responsive). *Amélioration hotones + existant.*
3. **PG-TMP-02/03** — Saisie du jour (variante mobile / quotidienne). *Amélioration hotones + existant.*
4. **PG-ABS-01** — Mes absences (libre-service congés). *Amélioration hotones + existant.*
5. **PG-CPL-01** — Complétude (vue manager). *Amélioration hotones + existant.*

> Deux régimes de conception : **DSH-COLLAB = création** (guidée par le parcours, l'audit transverse et le
> design system) ; **les 4 autres = reskin amélioré** (guidés par l'audit US-082 + patterns hotones retenus).

Décisions d'affinage (S20) — arrêtées :
- **Outil** : maquettes via *claude-design* / design-canvas (`design-canvas/*.dc.html`), un artboard par écran,
  sur les composants et tokens du bundle `tailsfadmin` (Card, Table, Form, Calendar, ProgressBar, StatCard…).
- **Gaps composants** : si un écran mobilise un composant manquant (G1 `Ui:StatCard`, G4 `Layout:PageHeader`),
  la maquette le matérialise et **note le gap** pour le lot bundle du Sprint 21 (pas de dev bundle ici).
- **Amélioration hotones** : pour les 4 écrans existants, intégrer les patterns retenus de l'audit US-082 ;
  ne pas reproduire ses faiblesses. **DSH-COLLAB est conçu sans base hotones** (aucune capture). Les captures
  fournies pouvant être partielles/mal nommées, une maquette dont l'écran hotones manque est conçue depuis
  l'existant + le parcours (le PO peut refaire des captures si un écran comparable est jugé nécessaire).
- **Accessibilité by design** : contrastes AA, cibles ≥ 44 px, focus visible, ordre de tabulation, labels,
  états (vide/chargement/erreur), pas d'action critique au survol.
- **Validation** : gate `design-canvas/VALIDATION.md` — chaque écran passe To Review → Validé PO.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : maquettes des écrans prioritaires EPIC-003
```gherkin
GIVEN la liste des écrans prioritaires (dashboard collaborateur, saisie hebdo, saisie jour, absences, complétude)
WHEN le concepteur produit les maquettes design-canvas
THEN chaque écran prioritaire dispose d'une maquette haute-fidélité (un artboard)
  AND chaque maquette n'utilise que des composants et tokens du bundle tailsfadmin
  AND tout composant manquant est matérialisé et signalé comme gap (sans le développer)
```

### CA-2 (Nominal) : maquettes validées PO (gate)
```gherkin
GIVEN les maquettes des écrans prioritaires
WHEN le PO les revoit
THEN chaque écran est tracé dans `design-canvas/VALIDATION.md` avec un statut (To Review / Validé / À revoir)
  AND la story n'est Done que lorsque 100 % des écrans prioritaires sont « Validé PO »
```

### CA-3 (Alternatif) : intégration des recommandations d'audit
```gherkin
GIVEN les recommandations priorisées de l'audit UX (US-082)
WHEN une recommandation « bloquant » ou « majeur » concerne un écran maquetté existant
THEN la maquette applique la recommandation ou justifie explicitement son écart
  AND la traçabilité recommandation → choix de maquette est consultable
```

### CA-3bis (Alternatif) : dashboard collaborateur conçu sans base hotones
```gherkin
GIVEN le dashboard collaborateur (DSH-COLLAB), sans dashboard existant produit et avec une home hotones faible
WHEN sa maquette est produite
THEN elle est dérivée du parcours P1 (US-081) et des objectifs de contrepartie (planning, feedback, complétude perso)
  AND elle ne traite la home collaborateur hotones que comme inspiration faible (pas de reprise fidèle)
  AND elle s'appuie sur les composants du bundle (StatCard/G1, PageHeader/G4, Card…), gaps notés
```

### CA-4 (Alternatif) : états et responsive couverts
```gherkin
GIVEN l'écran de saisie hebdomadaire (le plus critique)
WHEN sa maquette est produite
THEN elle couvre les états vide / rempli / erreur de validation
  AND elle décline une variante lisible sur largeur mobile (~400 px)
```

### CA-5 (Erreur) : maquette hors design system
```gherkin
GIVEN une maquette introduisant un style ou un composant hors bundle tailsfadmin (couleur ad hoc, layout custom)
WHEN le PO valide
THEN il refuse la maquette
  AND demande le remplacement par un composant/token du bundle ou l'ouverture explicite d'un gap
```

### CA-6 (Erreur) : écran prioritaire non maquetté
```gherkin
GIVEN un écran prioritaire du parcours saisie sans maquette validée
WHEN le sprint approche de sa clôture
THEN la story reste non-Done
  AND l'écran manquant est reporté explicitement (pas de dev reskin S21 sur un écran non validé)
```

## Notes sur les livrables
- **Livrables** : `project-management/architecture/design-canvas/*.dc.html` (un artboard par écran prioritaire) + `design-canvas/VALIDATION.md` (gate PO).
- **Périmètre** : 5 écrans prioritaires EPIC-003 (dashboard collaborateur, saisie hebdo, saisie jour, absences, complétude).
- **Composants** : bundle `tailsfadmin` uniquement ; gaps G1/G4 matérialisés + notés (dev au S21).
- **Accessibilité** : WCAG 2.2 AA intégrée dès la maquette (contrastes, cibles, focus, états, clavier).
- **Outillage** : agents `ui-designer`, `ux-ergonome`, `accessibility-expert` orchestrés par `uiux-orchestrator` ; skill `design`.
- **Hors périmètre** : dev front (Sprint 21) ; dev des composants bundle manquants ; écrans hors parcours saisie P1.
- **Validation** : gate `design-canvas/VALIDATION.md` + revue PO en PR.

## Definition of Ready
- [x] Description INVEST (livrable de conception borné ; persona P1/manager ; estimé 8 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] **Périmètre écrans arrêté** : 5 écrans prioritaires EPIC-003 (bloc Must US-085)
- [x] **Outil et format arrêtés** : design-canvas, un artboard par écran, composants tailsfadmin
- [x] **Gate de validation défini** : `design-canvas/VALIDATION.md` (To Review → Validé PO)
- [x] **Dépendances** : US-080/081/083 livrées ; US-082 (audit) livrée en amont dans le même sprint
- [x] Validation INVEST : Independent ✓ (dépend d'US-082 séquencée en amont) / Negotiable ✓ (nombre d'états adaptable) / Valuable ✓ (prérequis du dev reskin, adoption P1) / Estimable ✓ (8 pts — 5 écrans HF + états + gate) / Sized ✓ (= 8 pts, à surveiller) / Testable ✓ (gate PO 100 % écrans)
- **DoR : ✅ LEVÉE — prête pour `/sprint:dev`** (après US-082)

## Definition of Done
- [x] Un artboard design-canvas par écran prioritaire EPIC-003 (5 écrans → 7 artboards avec états + mobile)
- [x] Maquettes 100 % sur composants/tokens tailsfadmin ; gaps G1/G4 matérialisés + notés
- [x] Recommandations d'audit « bloquant/majeur » appliquées ou écart justifié (traçabilité dans `lot2-saisie/VALIDATION.md`)
- [x] Écran de saisie hebdo : états vide/rempli/erreur + variante mobile
- [ ] `design-canvas/lot2-saisie/VALIDATION.md` : 100 % des écrans prioritaires « Validé PO » (revue PO en attente)
- [x] Accessibilité WCAG 2.2 AA intégrée dès la conception (cibles 44px, focus, statut texte+icône+couleur, e-mails)
- [ ] Branche versionnée, PR mergée sur la branche de sprint 20

> **Statut** : 7 artboards produits (`design-canvas/lot2-saisie/`), canevas publié pour revue, gate `VALIDATION.md` prêt.
> Statut passé de 🟢 Ready → 🟡 To Review. Reste : validation PO écran par écran (gate).
> **Artboards** : DSH-COLLAB, saisie hebdo (rempli + états + mobile), saisie du jour, absences, complétude.
