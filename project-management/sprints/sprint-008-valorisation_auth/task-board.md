# Task Board — Sprint 8 (Valorisation & authentification web)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Estimation |
|----|-----|-------|------------|
| T-060-09 | US-060 | [OPS] Follow-up perf (revue T-060-08) : index `time_entry(project_id)` + coalescence du rebuild analytique (1 rebuild/lot aujourd'hui) — **différé**, non bloquant | 2h |
| T-068-11 | US-068 | [OPS] `NotCompromisedPassword` (reset + Mon compte) — **différé** : dépendance HTTP externe runtime (HaveIBeenPwned) à cadrer (cache, fail-open, mock en test) + `symfony/validator` | 2h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|

## ✅ Terminé
| ID | US | Tâche | Terminé |
|----|-----|-------|---------|
| T-070-01 | US-070 | [OPS] Dockerfile : `tailwind:build` avant `asset-map:compile` (build de zéro OK) | 2026-09-03 |
| T-070-02 | US-070 | [FE-WEB] `/validation` : durée en heures (« 4h00 ») + test | 2026-09-03 |
| T-060-01 | US-060 | [BE] Use case `AssignProfile` (affectation profil↔collaborateur) + 7 tests | 2026-09-03 |
| T-070-03 | US-070 | [DB] Seed profils/tarifs/affectation + valorisation + `make db-reset` | 2026-09-03 |
| T-067-01 | US-067 | [DB] Migration `first_name`/`last_name` (nullable) | 2026-09-03 |
| T-067-02 | US-067 | [BE] `User.displayName()` + `rename()` + fallback email (5 tests) | 2026-09-03 |
| T-067-03 | US-067 | [BE] `findDisplayNamesByIds` (repo + Doctrine + InMemory) | 2026-09-03 |
| T-067-05 | US-067 | [FE-WEB] Email → nom d'affichage (base, complétude) + seed noms | 2026-09-03 |
| T-067-06 | US-067 | [TEST] Affichage « Prénom Nom » (complétude) | 2026-09-03 |
| T-068-02 | US-068 | [BE] 2 pare-feux (api 401 / web form_login) + `LoginController` (login/logout) | 2026-09-04 |
| T-068-03 | US-068 | [FE-WEB] `security/login.html.twig` + bouton déconnexion topbar | 2026-09-04 |
| T-067-04 | US-067 | [FE-WEB] Écran « Mon compte » (profil nom/prénom) | 2026-09-04 |
| T-068-04 | US-068 | [BE/FE-WEB] Changement de mot de passe (Argon2id, CSRF) + 8 tests `AuthWebTest` | 2026-09-04 |
| T-060-02 | US-060 | [FE-WEB] Écran d'affectation profil↔collaborateur (POST-Redirect-Get + CSRF, `MANAGE_PRICING`) + 4 tests `ProfileAssignmentPageTest` | 2026-09-04 |
| T-060-06 | US-060 | [BE] Projection `fact_project_revenue` post-validation (message async `AnalyticsRebuildRequested` → `ProjectAnalyticsHandler` → rebuild ; couvre aussi la re-valorisation CA-4) + 3 tests | 2026-09-04 |
| T-060-04 | US-060 | [BE+FE-WEB] Ventilation par projet (join `time_entry_valuation ↔ time_entry`, DTO `ProjectValuationLine`, table dashboard + gating coût) + 2 tests | 2026-09-04 |
| T-060-03 | US-060 | [BE] Taux d'occupation (`OccupationReport` : jours valorisés / (ouvrés − absences), mois de la dernière prestation valorisée) + 4 tests | 2026-09-04 |
| T-060-05 | US-060 | [FE-WEB] Dashboard : table occupation par collaborateur (barre + %) + test fonctionnel | 2026-09-04 |
| T-060-07 | US-060 | [TEST] Couverture d'ensemble : affectation (`ProfileAssignmentPageTest`), occupation (`OccupationReportTest`+`OccupationDashboardTest`), par-projet (`ProjectValuationBreakdownTest`), SLA ≤ 15 min (`ValuationThroughputTest`) | 2026-09-04 |
| T-060-08 | US-060 | [REV] Revue de clôture `symfony-reviewer` (28/30) : sécurité/DDD/SOLID OK ; simplif `OccupationReport` appliquée ; perf → follow-up T-060-09 | 2026-09-04 |
| T-068-01 | US-068 | [OPS] `symfony/mailer` + `symfonycasts/reset-password-bundle` + config (mailer.yaml, reset_password.yaml : lifetime/throttle 1h) | 2026-09-04 |
| T-068-05 | US-068 | [DB] Entité + repo `ResetPasswordRequest` (hexagonal, sans ServiceEntityRepository) + migration `reset_password_request` (hors RLS) | 2026-09-04 |
| T-068-06 | US-068 | [BE] `PasswordResetController` (demande→email→reset, anti-énumération, CSRF, token session, Argon2id) + `findByEmail` inter-tenant + port/adapter mailer | 2026-09-04 |
| T-068-07 | US-068 | [FE-WEB] Templates forgot/check-email/reset + email HTML + lien depuis le login | 2026-09-04 |
| T-068-08 | US-068 | [TEST] `ResetPasswordWebTest` : parcours complet, anti-énumération (email inconnu), jeton invalide, borne haute mot de passe (4 cas) | 2026-09-04 |
| T-068-09 | US-068 | [REV] Revue sécurité `security-auditor` : **sain, aucun bloquant** ; corrigés : e-mail async (anti-énumération timing), borne haute + garde format ; différés → T-068-10/11 | 2026-09-04 |
| T-068-10 | US-068 | [OPS] Durcissement auth : rate limiting `login_throttling` (2 pare-feux) + limiteur IP `/mot-de-passe-oublie` (symfony/rate-limiter) + test régression invalidation des sessions au changement de mot de passe | 2026-09-04 |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|

## Métriques
- **Tâches** : 26 initiales · 26 terminées (T-068-09 revue sécurité en cours) ; +1 follow-up différé T-060-09
- **Heures** : 63h estimées · ~60h consommées
- **US-060 tranche 2 CLÔTURÉE** : affectation (T-060-02), ventilation par projet (T-060-04), projection `fact_project_revenue` (T-060-06), occupation (T-060-03/05), tests d'ensemble (T-060-07), revue (T-060-08). Reste follow-up perf **différé** T-060-09.
- **F2 levé** : /valorisation démontrable (CA 3 600 €, 5/5 imputations valorisées sur le seed) ; dashboard enrichi (par-projet + occupation).
- **US-068 CLÔTURÉE** : login/logout web, « Mon compte » (profil + mot de passe), **mot de passe oublié** (mailer + reset-password-bundle, anti-énumération, token par compte multi-tenant, Argon2id). Revue sécurité T-068-09 en cours.
- **Points** : 22 engagés (US-060 largement pré-implémentée → risque réduit)
