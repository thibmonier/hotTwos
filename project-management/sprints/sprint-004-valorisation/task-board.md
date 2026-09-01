# Task Board — Sprint 4 (Valorisation automatique du temps validé)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | Élément | Tâche | Est. |
|----|---------|-------|------|
| T-010-04 | US-010 | Cas d'usage hiérarchie + cycle + désactivation | 4h |
| T-010-05 | US-010 | API DTO unités + rattachements | 3h |
| T-010-06 | US-010 | Écran hiérarchie + rattachements | 4h |
| T-010-07 | US-010 | Tests (VO, cycle, isolation, 403) | 3h |
| T-010-08 | US-010 | Doc + revue sécurité | 2h |
| T-011-01 | US-011 | `Profile` + mode de calcul + migration + RLS | 2h |
| T-011-02 | US-011 | `ProfileRate` historisé + migration + repository | 3h |
| T-011-03 | US-011 | Moteur `RateResolver` (tarif à une date, `ARC-6`) | 4h |
| T-011-04 | US-011 | Cas d'usage `DefineProfileRate` (chevauchement/négatif/chargé/rétroactif) | 5h |
| T-011-05 | US-011 | API DTO + vue historique tarifaire | 3h |
| T-011-06 | US-011 | Écran profil + timeline historique | 4h |
| T-011-07 | US-011 | Tests moteur + règles | 4h |
| T-011-08 | US-011 | Doc + revue | 2h |
| T-060-01 | US-060 | Message `TimeEntriesValidated` à la validation | 3h |
| T-060-02 | US-060 | Handler async valorisation + snapshot figé | 5h |
| T-060-03 | US-060 | Entité `TimeEntryValuation` + migration + RLS | 2h |
| T-060-04 | US-060 | `RevenueRecognized` réel → projecteur → fait | 3h |
| T-060-05 | US-060 | Taux manquant (CA-4) + période clôturée 423 (CA-5) | 4h |
| T-060-06 | US-060 | Dashboard financier (fraîcheur, progression, audit trail) | 4h |
| T-060-07 | US-060 | Tests snapshot + non-divergence + charge | 5h |
| T-060-08 | US-060 | Doc + revue sécurité | 2h |
| T-TECH-02 | TECH-4 | Étendre RLS aux tables métier + test d'intrusion | 3h |

## 🔄 En Cours
| ID | Élément | Tâche | Démarré |
|----|---------|-------|---------|

## 👀 En Review
| ID | Élément | Tâche | Reviewer |
|----|---------|-------|----------|

## ✅ Terminé
| ID | Élément | Résultat | Commit |
|----|---------|----------|--------|
| T-TECH-01 | TECH-4 | Messenger installé (8.1.*) ; transport Doctrine async + failed + retry borné ; middleware d'isolation tenant par message (ARC-47/RSQ-15) ; table `messenger_messages` ; 4 tests | _à committer_ |
| T-010-01 | US-010 | VO `EffectivePeriod` (semi-ouvert `[from,to)`, `contains`/`overlaps`/`equals`, pur) — 13 tests | _à committer_ |
| T-010-02 | US-010 | `OrgUnit` + `OrgLevelConfig` + migration + **RLS** ; `schema:validate` OK — 9 tests | _à committer_ |
| T-010-03 | US-010 | `OrgMembership` (historisé, VO reconstruit) + migration + RLS + `OrgMembershipRepository` (port+Doctrine) — 5 tests (unit+intégration) | _à committer_ |

## 🚫 Bloqué
| ID | Élément | Raison | Action |
|----|---------|--------|--------|

## Métriques
- **Tâches** : 26 engagées · 4 terminées (15 %) · +2 en réserve (T-TECH-03/04)
- **Heures** : 84h estimées (engagé) · ~10h consommées (T-TECH-01 + T-010-01/02/03)
- **Bonus hors sprint** : dockerisation complète de l'outillage (Makefile, hook pre-commit, phpunit) — aucune commande sur l'hôte
- **Points** : 21 engagés (US-010 : 5 · US-011 : 8 · US-060 : 8)
- **Critère de sortie phare** : **temps validé → marge projet** avec tarif temporel **figé** (snapshot `INV-2/INV-3`), non-divergence verte (`ARC-113`).
- **Chemin critique** : T-TECH-01 → T-010-01 → T-011-03 (`RateResolver`) → T-060-02 (snapshot) → T-060-04 (fait réel) → T-060-07 (non-divergence).
