# Sprint 12 : Pilotage projet — avancement, RAF & atterrissage charge (EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 12 |
| Préparé le | 2026-09-06 |
| Début | 2027-01-21 *(provisoire)* |
| Fin | 2027-02-03 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~20 points (1 dev ; vélocité récente ~22 hors S10, sécurité 10 %) |
| Base git | `main` (après clôture S11, PR #62) |
| EPIC | EPIC-002 (Projets & delivery) — reprise après la finance (EPIC-005 bouclé) |
| Statut | ✅ **CLÔTURÉ (2026-09-06) — 16/16 pts Must + dette Should, goal atteint 100 %** |

## Sprint Goal (proposé — à valider en Planning P1)

> « Le **chef de projet pilote l'atterrissage** de ses projets : il saisit l'**avancement physique**
> et le **reste-à-faire (RAF)** — distincts de la consommation valorisée (INV-4) — et le système calcule
> l'**atterrissage en charge** en continu, déclenchant une **alerte de dérive dès qu'un dépassement
> > 10 % est projeté avant 50 % de consommation** (OBJ-2, pain point n°1). »

**Positionnement** : EPIC-005 (finance/rentabilité) est bouclé. La dérive **côté marge/montant** est déjà
livrée (US-072 budget montant + dérive, US-018 seuil tenant). Le gap restant d'EPIC-002 est **côté
charge/delivery** : avancement physique, RAF, atterrissage charge et détection précoce de dérive. On
réutilise l'infra de seuil/dérive existante (`MarginDriftThreshold`, providers) plutôt que d'en créer une seconde (ARC-6).

## ✅ Prérequis levés (affinage fait le 2026-09-06)

1. ✅ **EPIC-002 réconcilié** avec `sprint-status.yaml` (titres/numéros/sprints).
2. ✅ **Stories rédigées Ready** : `US-035-avancement-raf.md`, `US-036-atterrissage-derive-charge.md` (INVEST + 3C + Gherkin SMART + estimation).
3. ✅ **Décomposées** en tâches (`tasks/`) + board.
4. ⏳ `/gate:validate-sprint 012` puis exécution `/project:run-sprint 012 --auto`.

## Sprint Backlog candidat (à affiner — non engagé)

Reconstruit sur le **gap réel** (source de vérité `sprint-status.yaml`), pas sur le doc EPIC périmé.

| Priorité | ID | Titre (gap réel) | Est. prov. | Notes |
|----------|-----|------------------|-----------|-------|
| 🔴 Must | US-035 | Avancement physique & RAF par phase | ~8 | Fondation INV-4 : avancement / RAF / consommation = 3 champs distincts. Non livré. |
| 🔴 Must | US-036 | Atterrissage charge + alerte dérive avant 50 % conso | ~8 | Cœur MMF (EF-PRJ-14/15, OBJ-2). Réutilise l'infra seuil/dérive (US-072/US-018) côté charge. |
| 🟡 Should | DETTE | Harmoniser libellés « CA reconnu » → « Revenu retenu » (`/finance`, `/valorisation`) | ~2 | Action 1 rétro S11 (cohérence post-US-076). |
| 🟡 Should | T-R01 | Correctif 1er clic onglet « Suivi budgétaire » (Stimulus `tabs`) | ~1 | Reconduit S10→S11. |
| 🟡 Should | OPS | `MAILER_DSN` staging + reset e2e | ~1-2 | Reconduit S8→S11. |
| 🟢 Could | US-033 | Budget charge & montant (surfacer le suivi charge) | ~3 | `budget_cents` déjà en base ; à exposer si capacité. |
| 🟢 Could | US-032 | Projets internes | ~3 | Si capacité. |

**Total Must (~16) + Should dette (~4) ≈ 20 pts** → tient dans la capacité. Could en réserve.

## Definition of Done (rappel projet)

- [ ] Revue de clôture approuvée (`symfony-reviewer` par lot)
- [ ] Tests (couverture ≥ 80 % **gardée en CI**), `make ci` vert (PHPStan max, Deptrac, gitleaks)
- [ ] **INV-4** respecté : avancement, RAF et consommation jamais confondus/déduits l'un de l'autre
- [ ] **INV-2/INV-3** : un avenant/révision de budget n'altère pas les imputations historiques
- [ ] Moteur de dérive **unique** (ARC-6) : la dérive charge réutilise l'infra seuil existante, pas un 2ᵉ moteur
- [ ] Gating **HAB-1** : le coût unitaire d'un collaborateur n'est jamais visible d'un chef de projet
- [ ] Migration + RLS si nouvelle entité ; recette navigateur sur seed enrichi tracée dans `.recette/`

## Dépendances

| Élément | Dépend de | Statut |
|---------|-----------|--------|
| US-035 (avancement/RAF) | Structure projet (phases/lots, US-030/031) | ✅ Livré (S6) |
| US-036 (dérive charge) | Consommation valorisée (US-060) + infra seuil (US-018/072) | ✅ Livré (S8/S9/S10) |
| Dette libellés | US-076 (source de revenu) | ✅ Livré (S11) |

## Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Chevauchement US-036 avec dérive finance déjà livrée | Moyenne | Moyen | Affinage : cadrer explicitement le périmètre **charge** vs **montant** (déjà fait) |
| Stories non Ready → scope flou | Élevée | Élevé | **Affinage obligatoire avant dev** (bloquant ci-dessus) |
| Doc EPIC-002 périmé induit en erreur | Moyenne | Moyen | Réconcilier EPIC-002 avec `sprint-status.yaml` en préambule |
| INV-4 mal modélisé (avancement déduit de la conso) | Moyenne | Élevé | 3 champs distincts en base + tests dédiés |

## Cérémonies

| Cérémonie | Moment |
|-----------|--------|
| Planning P1 (QUOI) + P2 (COMMENT) | Début de sprint, après affinage |
| Daily | Quotidien (`daily-notes/`) |
| Affinage | Continu (backlog S13) |
| Review + Rétro | Fin de sprint |

## Notes

- Solo dev : « Planning/Daily/Review/Rétro » = jalons d'auto-discipline (docs), pas de réunions.
- Convention run-sprint : 1 PR par story, TDD → `make ci` vert → migration+`schema:validate` si entité → PR → CI → merge squash → MAJ board/story/`sprint-status.yaml`.
