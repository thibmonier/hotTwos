# Sprint 18 : Démarrage staffing (EPIC-004) + finitions Review & qualité

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 18 |
| Début | 2027-04-15 *(provisoire)* |
| Fin | 2027-04-28 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev ; vélocité de référence 22) |
| Base git | `main` (après clôture S17, PR #101) |
| EPIC | EPIC-004 (Planification & Staffing) — **démarrage** + finitions EPIC-001 |
| Statut | 🟡 En préparation — 4 items à affiner en Ready avant dev |

## Sprint Goal

> « **Amorcer le staffing** (EPIC-004) : un resource manager trouve les collaborateurs par **compétence
> et disponibilité** et visualise la **capacité vs charge ferme** — tout en **finalisant les suites de
> Review** (fériés mobiles, audit étendu) et en **tarissant la dette de test** (schéma mutualisé). »

**Positionnement** : premier incrément d'EPIC-004 **buildable maintenant** (capitalise sur les compétences
US-013 et la capacité US-021/022). La **charge probable** (pipeline pondéré, INV-5) dépend du CRM
(EPIC-006, non construit) → **reportée** ; on modélise ici la **charge ferme** uniquement.

## Sprint Backlog

| Priorité | ID | Titre | Points | EF / Origine | DoR |
|----------|-----|-------|--------|--------------|-----|
| 🔴 Must | US-040 | Recherche de staffing par compétence & disponibilité | 8 | EPIC-004 (module RES) | ⏳ à affiner |
| 🔴 Must | US-041 | Plan de charge : capacité vs charge ferme par collaborateur | 5 | EPIC-004 (OBJ-4) | ⏳ à affiner |
| 🟡 Should | US-023 | Raffinements Review : fériés mobiles (onboarding) + audit étendu (org/profils) | 5 | Review S16/S17 | ⏳ à affiner |
| 🟢 Tech | QUAL-3 | Schéma de test mutualisé (trait) — tarir la cascade SchemaTool | 2 | Rétro S16/S17 | ⏳ à affiner |

**Engagé : ~20 pts** (capacité ~22).

## Ordre d'exécution
1. **QUAL-3** (schéma mutualisé) — d'abord : facilite tous les tests suivants.
2. **US-023** (raffinements Review) — petites finitions, faible risque.
3. **US-041** (capacité vs charge ferme) — pose le modèle de charge ferme (INV-5, part ferme).
4. **US-040** (recherche staffing) — capitalise sur compétences + disponibilité.

## Definition of Ready (avant `/sprint:dev`)
- [ ] Description INVEST + Gherkin (≥ 1 nominal + 2 alternatifs + 2 erreurs)
- [ ] US-040 : réutilise `Skill`/`SkillAssignment` (US-013) + `WorkingDaysCalculator` (dispo) — pas de re-spécification
- [ ] US-041 : charge ferme dérivée des affectations/budgets existants (US-037/US-033) ; **charge probable reportée** (INV-5 : champ distinct anticipé)
- [ ] US-023 : fériés mobiles = calcul de Pâques (Ascension/Pentecôte dérivées) ; audit étendu = instrumenter org/profils via `ConfigAuditRecorder`
- [ ] QUAL-3 : trait `ProvisionsFullSchema` (liste centralisée des ClassMetadata) sans casser les tests existants
- [ ] Impact multi-tenant/RLS & gating (HAB-1 : coûts masqués en vues planification)

## Definition of Done (rappel)
- [ ] `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %
- [ ] Migration + RLS pour toute nouvelle entité ; UI ⇏ Infra (Deptrac) ; TDD ; 1 PR/story, squash

## Risques identifiés
| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Périmètre EPIC-004 vaste, dépendance CRM (charge probable) | Élevée | Moyen | Cadrer à la charge **ferme** + recherche ; probable reportée à EPIC-006 |
| US-040 recherche multi-critères (compétences + dispo) coûteuse | Moyenne | Moyen | S'appuyer sur les repos existants ; recherche simple (ET des critères), pas de moteur avancé |
| Cascade SchemaTool encore subie avant QUAL-3 | Moyenne | Faible | Faire QUAL-3 en premier |

## Cérémonies (solo dev = jalons documentaires)
Planning P1 = ce document (validé PO) · P2 = `/project:decompose-tasks 018` · Review/Rétro en clôture.

## Notes
Prochaine action : **affinage des 4 items en Ready** (créer US-040/US-041/US-023, QUAL-3) puis
`/project:decompose-tasks 018`. Dette reconduite : MAILER staging.
