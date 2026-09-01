# Task Board — Sprint 5 (Complétude et clôture du cycle temps)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Est. |
|----|----|-------|------|
| T-TECH-03 | Tech | Hardening `sprintf`→`set_config` (3 sites) | 3h |
| T-057-01 | US-057 | Entités PeriodClosure/ReopeningRequest/AuditLog + RLS | 3h |
| T-057-02 | US-057 | `ClosePeriod` (verrou + journal + CA-3) | 4h |
| T-057-03 | US-057 | Raccord US-060 (vraie clôture → recompute 423) | 2h |
| T-057-04 | US-057 | Verrou modif 423 + trigger anti-UPDATE/DELETE | 3h |
| T-057-05 | US-057 | Réouverture formelle + reclôture auto 48h | 4h |
| T-057-06 | US-057 | `PeriodClosed` async → calculs aval | 2h |
| T-057-07 | US-057 | Écran `/administration/periodes` + historique | 4h |
| T-057-08 | US-057 | Tests 423/403 + RLS-via-consume + immuabilité | 4h |
| T-057-09 | US-057 | Doc + revues sécu/Symfony | 2h |
| T-054-01 | US-054 | Entités AbsenceType/AbsenceRequest + RLS (HAB-3) | 3h |
| T-054-02 | US-054 | `DeclareAbsence`/`DecideAbsence` + notifications | 4h |
| T-054-03 | US-054 | Compteurs acquis/pris/attente/projeté | 3h |
| T-054-04 | US-054 | Blocage imputation sur absence validée (422) | 2h |
| T-054-05 | US-054 | API absences + balance | 3h |
| T-054-06 | US-054 | Module `/absences` + widget compteurs | 4h |
| T-054-07 | US-054 | Tests RGPD (HAB-3) + 422 + RLS | 4h |
| T-054-08 | US-054 | Doc + revues (données de santé) | 2h |
| T-058-01 | US-058 | Service grille de complétude (4 états) | 4h |
| T-058-02 | US-058 | Périmètre RBAC (403 scope équipe) | 3h |
| T-058-03 | US-058 | API + export CSV anti-injection | 3h |
| T-058-04 | US-058 | Écran `/completude` (grille couleur) | 4h |
| T-058-05 | US-058 | Tests 403/vide/CSV + calcul taux | 3h |
| T-058-06 | US-058 | Doc + revue (perf) | 1h |
| T-056-01 | US-056 | Entités ReminderRule/ReminderLog/opt-out + RLS | 2h |
| T-056-02 | US-056 | Moteur relances borné (plancher, escalade, arrêt) | 4h |
| T-056-03 | US-056 | CLI cron + handler d'envoi async | 3h |
| T-056-04 | US-056 | API règles + opt-out individuel | 2h |
| T-056-05 | US-056 | Écran config + prévisualisation | 3h |
| T-056-06 | US-056 | Tests borne/annulation + RLS-via-consume | 3h |
| T-056-07 | US-056 | Doc + revue (opt-out RGPD) | 1h |
| T-052-01 | US-052 | Vue quotidienne mobile-first (44px, clavier num.) | 4h |
| T-052-02 | US-052 | Swipe jours + duplication (Turbo/Stimulus) | 3h |
| T-052-03 | US-052 | Offline localStorage + resync | 3h |
| T-052-04 | US-052 | Dégradation gracieuse 320px | 2h |
| T-052-05 | US-052 | Tests responsive (320/375/390) + a11y | 3h |
| T-052-06 | US-052 | Doc + revue a11y | 1h |
| T-059-01 | US-059 | Service synthèse activité (projet/type/occupation) | 3h |
| T-059-02 | US-059 | API scoped soi-même (403) + planning dégradé | 3h |
| T-059-03 | US-059 | Drawer « Ma synthèse » (1 clic, non perturbant) | 4h |
| T-059-04 | US-059 | Bottom-sheet mobile | 2h |
| T-059-05 | US-059 | Tests 403/vide/CA-5 | 3h |
| T-059-06 | US-059 | Doc + revue | 1h |
| T-TECH-04 | Tech | Fixtures démo EPIC-003 (🟢 optionnel) | 2h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|----|-------|----------|

## ✅ Terminé
| ID | US | Résultat | Commit |
|----|----|----------|--------|

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|----|--------|--------|

## Métriques
- **Tâches** : 44 total | 0 terminées (0 %)
- **Heures** : ~126h estimées | 0h consommées
- **Points** : 22 engagés
