# Sprint 16 : Référentiels de paramétrage & mise en route du tenant (EPIC-001)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 16 |
| Début | 2027-03-18 *(provisoire)* |
| Fin | 2027-03-31 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev ; vélocité de référence 22) |
| Base git | `main` (après clôture S15, PR #85) |
| EPIC | EPIC-001 (Référentiels & paramétrage) |
| Statut | 🟡 En préparation — 4 US à affiner en Ready (DoR + Gherkin) avant dev |

## Sprint Goal

> « **Compléter les référentiels de paramétrage** (calendrier/jours ouvrés & fériés, compétences),
> **rendre un tenant opérationnel en moins de 15 minutes** (onboarding avec valeurs par défaut) et
> **tracer les changements de paramétrage** — pour une adoption autonome sans intervention infra. »

**Positionnement** : après le bouclage d'EPIC-002 (S15), le focus repasse sur EPIC-001. Ce sprint livre 4
des 5 capacités restantes (reste US-017 circuits de validation → S17) et vise la **MMF EF-REF-29**
(productif < 15 min, OBJ-7 adoption ≥ 85 %).

## Sprint Backlog

| Priorité | ID | Titre | Points | EF-REF | DoR |
|----------|-----|-------|--------|--------|-----|
| 🔴 Must | US-012 | Calendriers, jours ouvrés & fériés | 5 | 6/7 | ⏳ à affiner |
| 🔴 Must | US-013 | Référentiel de compétences & niveaux | 3 | 10/11 | ⏳ à affiner |
| 🔴 Must | US-019 | Onboarding tenant (< 15 min, defaults) — **MMF** | 5 | 29 | ⏳ à affiner |
| 🟡 Should | US-020 | Journal d'audit du paramétrage | 3 | 33 | ⏳ à affiner |

**Engagé : 16 pts** (sous la capacité 22 — marge pour aléas d'affinage/première incursion EPIC-001).
**Réserve si capacité** : US-017 Statuts & circuits de validation (8) — à démarrer seulement après affinage.

## Ordre d'exécution (dépendances)
1. **US-012** (calendrier/jours ouvrés) — fondateur : alimente les jours ouvrés (occupation/capacité déjà en place).
2. **US-013** (compétences) — référentiel indépendant, quick win.
3. **US-020** (audit paramétrage) — transverse, capte les écritures de référentiel.
4. **US-019** (onboarding) — **en dernier** : câble les valeurs par défaut (statuts, calendrier, profils) une fois les briques disponibles.

## Definition of Ready (à satisfaire avant `/sprint:dev`)
- [ ] Description claire (En tant que / Je veux / Afin de)
- [ ] Critères d'acceptance Gherkin (≥ 1 nominal + 2 alternatifs + 2 erreurs)
- [ ] Estimation confirmée (points)
- [ ] Dépendances identifiées ; pas de bloqueur technique
- [ ] Impact multi-tenant/RLS et gating (HAB) explicités

## Definition of Done (rappel projet)
- [ ] Revue de clôture ; `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %
- [ ] Migration + RLS pour toute nouvelle entité ; UI ne dépend jamais de l'Infra (Deptrac)
- [ ] Tests TDD (unit + fonctionnels) ; nouvelles tables ajoutées aux SchemaTool des tests concernés
- [ ] 1 PR / story, TDD, merge squash après CI verte

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Calendrier fériés : intégration avec l'occupation/complétude existante (jours ouvrés) | Moyenne | Élevé | Réutiliser le calcul de jours ouvrés existant ; tests sur absences/occupation impactées |
| Onboarding : dépend de défauts encore partiels (statuts, calendrier) | Moyenne | Moyen | US-019 en dernier ; défauts limités aux briques livrées (calendrier S16, statuts projet existants) |
| US-012/013/019/020 non affinées (Backlog) | Élevée | Moyen | Affinage DoR + Gherkin en préambule (gate) avant dev |

## Cérémonies (solo dev = jalons documentaires)
| Cérémonie | Jalon |
|-----------|-------|
| Planning P1 (QUOI) | Ce document (Sprint Goal + backlog validés PO) |
| Planning P2 (COMMENT) | `/project:decompose-tasks 016` |
| Daily | `daily-notes/` |
| Review / Rétro | `sprint-review.md` / `sprint-retro.md` en clôture |

## Notes
Convention run-sprint : 1 PR/story, TDD, `make ci` vert, merge squash. Prochaine action :
**affinage des 4 US en Ready** (DoR + Gherkin) puis `/project:decompose-tasks 016`.
Actions rétro S15 reportées à traiter : check « source du seuil » au planning (fait pour ce sprint),
`.gitignore compose.override` (✅ fait). Dette reconduite : MAILER staging, finding R-01.
