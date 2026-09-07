# Sprint 15 : Finition pilotage projet — courbe d'atterrissage & seuil par type (EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 15 |
| Préparé le | 2026-09-07 |
| Début | 2027-03-04 *(provisoire)* |
| Fin | 2027-03-17 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~18 points (1 dev ; vélocité récente ~18) |
| Base git | `main` (après clôture S14, PR #79) |
| EPIC | EPIC-002 (Projets & delivery) — **bouclage** des raffinements pilotage |
| Statut | 🟢 **Prêt — US-079c/US-079b Ready (affinés dans US-079) ; US-079c re-cadrée capture légère** |

## Sprint Goal

> « **Boucler le pilotage projet (EPIC-002)** : l'atterrissage en charge est **historisé** et sa **courbe
> d'évolution** visible, et le **seuil de dérive** est **paramétrable par type de projet** (avec un 2e
> seuil d'escalade à la direction). »

**Positionnement** : dernières capacités *Should* d'EPIC-002 (EF-PRJ-15/16), reportées de S14. À l'issue,
EPIC-002 est complet à 100 %.

## Sprint Backlog

| Priorité | ID | Titre | Points | EF | DoR |
|----------|-----|-------|--------|-----|-----|
| 🔴 Must | US-079c | Courbe d'atterrissage historisée (**capture légère**) | ~5 | EF-PRJ-16 | ✅ Ready |
| 🟡 Should | US-079b | Seuil de dérive charge par type de projet + 2e seuil direction | ~5 | EF-PRJ-15 | ✅ Ready |

**Engagé : ~10 pts.** Sprint focalisé « finition EPIC-002 » (sous la capacité ~18 — le PO a priorisé le
bouclage propre plutôt que d'embarquer une story EPIC-001 non affinée). Réserve si capacité : entamer une
story EPIC-001 (onboarding EF-REF-29) après affinage.

## Décision de conception (rétro S14) — US-079c capture légère

L'historisation ne doit **pas** alourdir `ComputeProjectMargins` (handler cœur). Approche retenue :
**un listener/handler séparé** sur le même déclencheur (clôture de période) qui enregistre un
`ChargeLandingSnapshot` — `ComputeProjectMargins` reste inchangé. La courbe lit la série de snapshots.

## Definition of Done (rappel projet)

- [ ] Revue de clôture ; `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %
- [ ] **US-079c** : `ComputeProjectMargins` **non modifié** (capture via handler séparé) ; snapshot idempotent par période
- [ ] **US-079b** : seuil par **type de projet** remplace les constantes `ChargeLandingCalculator` (repli = constantes OBJ-2 par défaut) ; 2e seuil d'escalade direction ; gating HAB-1 préservé
- [ ] Migration + RLS pour toute nouvelle entité ; UI ne dépend jamais de l'Infra (Deptrac)

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Capture d'atterrissage recouplée au figeage de marge | Moyenne | Élevé | Handler **séparé** sur l'événement de clôture ; test prouvant que `ComputeProjectMargins` n'a pas changé de signature |
| Seuil par type : où stocker (par `ContractType` ? par tenant×type ?) | Moyenne | Moyen | Réutiliser le pattern `MarginDriftThreshold` (tenant) étendu par type ; repli constantes OBJ-2 |
| Snapshot dupliqué si re-clôture | Moyenne | Moyen | Idempotence par (tenant, projet, période) — remplacer, pas ajouter |

## Ordre d'exécution
1. **US-079c** (courbe, capture légère) — 2. **US-079b** (seuil par type).

## Notes
Solo dev : cérémonies = jalons docs. Convention run-sprint : 1 PR/story, TDD, `make ci` vert, merge squash.
À l'issue, EPIC-002 = 100 % ; le focus S16 repassera sur EPIC-001 (onboarding/calendrier/compétences).
