# Task Board — Sprint 11 (Module de facturation, capstone EPIC-005)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

### US-014 — Client structuré (tranche minimale, Must, 5 pts) — **entrée**
| ID | Tâche | Estimation |
|----|-------|------------|
| T-014-01 | [DB] Entité `Client` (tenant, name, siren nullable) + port `ClientRepository` | 2h |
| T-014-02 | [DB] Migration `client` (RLS) + `project.client_id` nullable + migration | 2h |
| T-014-03 | [BE] Rattachement `Project → Client` (use case/lien) + gating | 1.5h |
| T-014-04 | [FE-WEB] CRUD minimal client (liste + création) + sélection à la création/édition projet | 3h |
| T-014-05 | [TEST] Entité, RLS cross-tenant, rattachement, gating | 2h |
| T-014-06 | [REV] Revue de clôture | 1h |

### US-075 — Émission manuelle de factures (Must, 8 pts)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-075-01 | [DB] Entité `Invoice` (tenant, client, project_ref, period, amount_cents, status, issued_at) + port | 3h |
| T-075-02 | [DB] Migration `invoice` (RLS, index) | 1h |
| T-075-03 | [BE] Use case `IssueInvoice` (période clôturée, montant>0, pré-rempli CA reconnu, figée, HAB-1/HAB-6) | 3h |
| T-075-04 | [BE] Lecture factures + total facturé par projet/période | 2h |
| T-075-05 | [FE-WEB] UI émission (form) + liste des factures | 3h |
| T-075-06 | [TEST] Nominal, refus (période/montant), gating 403, isolation | 3h |
| T-075-07 | [REV] Revue de clôture | 1h |

### US-076 — Facturé réel comme source de marge (Must, 5 pts)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-076-01 | [BE] Port `RevenueSource` (Domain) : facturé réel si présent, sinon CA reconnu | 2h |
| T-076-02 | [BE] Impl (factures US-075 + repli) + branchement `ComputeProjectMargins` via le port (moteur inchangé) | 3h |
| T-076-03 | [TEST] Marge sur facturé, repli CA reconnu, non-rétro, pas de double comptage | 2h |
| T-076-04 | [REV] Revue de clôture | 0.5h |

## 🔲 À Faire — Should
### US-077 — Export FEC sur facturé réel (5 pts)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-077-01 | [BE] `FecGenerator`/`ExportFec` alimentés par `RevenueSource` (US-076) | 2h |
| T-077-02 | [TEST] FEC sur facturé, repli, cohérence FEC/marge | 2h |
| T-077-03 | [REV] Revue de clôture | 0.5h |

### Could
| ID | Tâche | Estimation |
|----|-------|------------|
| T-R01 | [FE-WEB] Correctif onglet « Suivi budgétaire » (1er clic — contrôleur Stimulus `tabs`) | 1h |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|
| ADR-0022 | Cadrage facturation minimale + facturé réel > proxy | 2026-09-05 |

## Ordre d'exécution (phases)
1. **US-014** (client — prérequis facturation).
2. **US-075** (émission manuelle de factures, s'appuie sur Client).
3. **US-076** (facturé réel → marge, port « source de revenu »).
4. **US-077** (FEC sur facturé réel, Should) puis **T-R01** (Could) si capacité.

## Métriques
- **Tâches Must** : 17 (US-014 ×6, US-075 ×7, US-076 ×4) · **Should** : US-077 ×3 · **Could** : T-R01
- **Points engagés (Must)** : 18 (US-014 5 + US-075 8 + US-076 5) · US-077 (5) en Should
- **Heures Must** : ~29h · capacité ~22 pts (post-fêtes)
