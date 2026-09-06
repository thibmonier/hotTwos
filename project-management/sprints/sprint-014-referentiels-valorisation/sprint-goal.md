# Sprint 14 : Finition pilotage projet + référentiels de valorisation (EPIC-002 → EPIC-001)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 14 |
| Préparé le | 2026-09-06 |
| Début | 2027-02-18 *(provisoire)* |
| Fin | 2027-03-03 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~20 points (1 dev ; vélocité récente ~20 hors S10, sécurité 10 %) |
| Base git | `main` (après clôture S13, PR #73) |
| EPIC | EPIC-002 (finition) + EPIC-001 (référentiels de valorisation) |
| Statut | ✅ **CLÔTURÉ (2026-09-06) — US-015 + US-016 + US-079a (~11 pts) ; US-079c/b reportés S15** |

## Sprint Goal (proposé)

> « **Boucler le pilotage projet** (raffinements EPIC-002 : export, courbe d'atterrissage, seuil par type)
> et **enrichir les référentiels de valorisation** : taux de vente **multi-niveaux** (profil / client / projet
> avec règle de priorité) et **multi-devises** (EF-REF-19/22). »

**Positionnement** : US-079 achève EPIC-002 à 100 %. La tranche EPIC-001 retenue prolonge directement le
socle de valorisation (Pricing) sur lequel s'appuient EPIC-002/005 — la plus synergique avec le travail
finance/budget récent.

## ✅ Prérequis levés (affinage fait le 2026-09-06)

- ✅ **US-079** Ready (affinée #68) — à découper en export/courbe/seuil.
- ✅ **EPIC-001 réconcilié** (`EPIC-001-referentiels-parametrage.md` : table alignée + carte de couverture EF-REF).
- ✅ **US-015 & US-016 rédigées Ready** (INVEST + Gherkin).
- ⏳ `/project:decompose-tasks 014` puis `/gate:validate-sprint 014`.

## Sprint Backlog (candidat)

| Priorité | ID | Titre | Points | DoR |
|----------|-----|-------|--------|-----|
| 🔴 Must | US-079 | Raffinements pilotage (export/courbe/seuil, EF-PRJ-14/15/16) — **finit EPIC-002** | 8 | ✅ Ready (à découper) |
| 🔴 Must | US-015 | Taux de vente multi-niveaux profil/client/projet + priorité (EF-REF-19) | ~5 | ⏳ à affiner |
| 🟡 Should | US-016 | Devises & taux de change, devise de référence tenant (EF-REF-22) | ~3 | ⏳ à affiner |

**Engagé cible : ~16 pts.** Réserve EPIC-001 (S15) : compétences (EF-REF-10/11), calendrier fériés (EF-REF-6),
statuts/circuits paramétrables (EF-REF-24/25), onboarding (EF-REF-29).

## Definition of Done (rappel projet)

- [ ] Revue de clôture (`symfony-reviewer` par lot) ; `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %
- [ ] **INV-2** : taux historisés à date d'effet, valorisations passées inchangées (US-015)
- [ ] **ARC-6** : la règle de priorité (projet > client > profil) vit en un seul point ; réutilise le socle `Pricing`
- [ ] Devise de référence tenant + consolidation (US-016) sans casser les montants existants (migration douce)
- [ ] Migration + RLS pour toute nouvelle entité ; UI ne dépend jamais de l'Infra (Deptrac)

## Ordre d'exécution
1. **US-079** (Ready, finit EPIC-002) — peut démarrer tout de suite.
2. **Affinage EPIC-001** (réconciliation doc + Gherkin US-015/US-016).
3. **US-015** puis **US-016**.

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Doc EPIC-001 périmé induit en erreur | Élevée | Moyen | Réconcilier avec `sprint-status.yaml` en préambule (comme #68) |
| US-015 (taux multi-niveaux) plus gros que 5 pts | Moyenne | Moyen | La priorité (projet>client>profil) sur un port unique ; réutiliser `ProfileRate` ; découper si besoin |
| Devises : impact transverse sur les montants | Moyenne | Élevé | Devise de référence tenant + montants en centimes inchangés ; conversion à l'affichage/consolidation seulement |

## Notes
- Solo dev : cérémonies = jalons docs. Convention run-sprint : 1 PR/story, TDD, `make ci` vert, merge squash.
- US-079 est une story « parapluie » → la décomposer en export / courbe / seuil au planning.
