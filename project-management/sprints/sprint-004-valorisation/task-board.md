# Task Board — Sprint 4 (Valorisation automatique du temps validé)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | Élément | Tâche | Est. |
|----|---------|-------|------|
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
| T-TECH-01 | TECH-4 | Messenger installé (8.1.*) ; transport Doctrine async + failed + retry borné ; middleware d'isolation tenant par message (ARC-47/RSQ-15) ; table `messenger_messages` ; 4 tests | `6983078` |
| T-010-01 | US-010 | VO `EffectivePeriod` (semi-ouvert `[from,to)`, `contains`/`overlaps`/`equals`, pur) — 13 tests | `da7cff8` |
| T-010-02 | US-010 | `OrgUnit` + `OrgLevelConfig` + migration + **RLS** ; `schema:validate` OK — 9 tests | `da7cff8` |
| T-010-03 | US-010 | `OrgMembership` (historisé, VO reconstruit) + migration + RLS + `OrgMembershipRepository` (port+Doctrine) — 5 tests (unit+intégration) | `da7cff8` |
| T-010-04 | US-010 | Cas d'usage `ConfigureOrgHierarchy` (cycle CA-6, désactivation RG-REF-1) + `AttachCollaborator` (non-chevauchement) ; permission `MANAGE_ORGANIZATION` ; `OrgUnitRepository` — 13 tests | `ba69328` |
| T-010-05 | US-010 | API Platform (unités + rattachements + timeline) ; DELETE=désactivation ; 403/422 via listeners — 5 tests fonctionnels | `b658956` |
| T-010-06 | US-010 | Écran `/organisation` (arbre, création/désactivation/rattachement) Twig/Turbo/Stimulus accessible — 2 tests | `2329815` |
| T-010-07 | US-010 | Test RLS d'intrusion tables org + fix policy texte robuste (+ cycle/403/422 déjà couverts) — 1 test | `f5c8131` |
| T-010-08 | US-010 | Doc module + revues sécurité/Symfony ; durcissement (existence collaborateur, validation UUID, borne nom, ensureCan item provider) — `UserRepository` | `9deaf1b` |
| T-011-01/02/03 | US-011 | `Profile` + `ProfileRate` (centimes, historisé) + `RateResolver` (ARC-6) + migrations RLS ; ports/adapters — 14 tests | `6fddb12` |
| T-011-04 | US-011 | `DefineProfileRate` (CA-5 chevauchement, CA-6 ≤0, CA-3 rétroactif via Clock) + `LoadedCostCalculator` (CA-2) ; permission `MANAGE_PRICING` — 13 tests | `ff2efb1` |
| T-011-05 | US-011 | API profils + tarifs + historique (403/422, DELETE=désactivation) ; `ManageProfiles` — 10 tests | `d73060e` |
| T-011-06 | US-011 | Écran `/profils` + timeline (ligne en vigueur CA-4) Twig/Stimulus — 2 tests | `184558e` |
| T-011-07 | US-011 | Test RLS d'intrusion tables tarification (+ moteur/règles déjà couverts) — 1 test | `e39c49c` |
| T-011-08 | US-011 | Doc module + revues sécurité/Symfony ; durcissement (traçage lecture coût HAB-6, plafond montants, date stricte, profil désactivé, rétroactif du jour) — 3 tests | _à committer_ |

## 🚫 Bloqué
| ID | Élément | Raison | Action |
|----|---------|--------|--------|

## Métriques
- **Tâches** : 26 engagées · 17 terminées (65 %) · +2 en réserve (T-TECH-03/04)
- **Heures** : 84h estimées (engagé) · ~55h consommées (T-TECH-01 + US-010 + US-011)
- **US-010 : ✅ TERMINÉE** · **US-011 : ✅ TERMINÉE** (8/8, revues passées)
- **Reste : US-060** (T-060-01→08) + T-TECH-02. US-060 consomme `RateResolver` + Messenger (livrés).
- **Bonus hors sprint** : dockerisation complète de l'outillage (Makefile, hook pre-commit, phpunit) — aucune commande sur l'hôte
- **Points** : 21 engagés (US-010 : 5 · US-011 : 8 · US-060 : 8)
- **Critère de sortie phare** : **temps validé → marge projet** avec tarif temporel **figé** (snapshot `INV-2/INV-3`), non-divergence verte (`ARC-113`).
- **Chemin critique** : T-TECH-01 → T-010-01 → T-011-03 (`RateResolver`) → T-060-02 (snapshot) → T-060-04 (fait réel) → T-060-07 (non-divergence).
