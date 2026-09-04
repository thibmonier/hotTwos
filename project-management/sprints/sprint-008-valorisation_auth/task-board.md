# Task Board — Sprint 8 (Valorisation & authentification web)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Estimation |
|----|-----|-------|------------|
| T-060-02 | US-060 | [FE-WEB] Écran d'affectation profil↔collaborateur | 3h |
| T-060-03 | US-060 | [BE] Taux d'occupation | 4h |
| T-060-05 | US-060 | [FE-WEB] Dashboard : **occupation** (part par-projet livrée avec T-060-04) | 1h |
| T-060-06 | US-060 | [BE] Projection `fact_project_revenue` post-validation | 3h |
| T-060-07 | US-060 | [TEST] Affectation, occupation, par-projet, SLA ≤ 15 min | 3h |
| T-060-08 | US-060 | [REV] Revue de clôture | 1h |
| T-068-01 | US-068 | [OPS] Mailer + reset-password-bundle | 2h |
| T-068-05 | US-068 | [DB] Migration `reset_password_request` | 1h |
| T-068-06 | US-068 | [BE] `ResetPasswordController` + e-mail | 4h |
| T-068-07 | US-068 | [FE-WEB] `forgot`/`reset` templates | 2h |
| T-068-08 | US-068 | [TEST] Parcours auth web complet | 3h |
| T-068-09 | US-068 | [REV] Revue sécurité | 1h |

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
| T-060-04 | US-060 | [BE+FE-WEB] Ventilation par projet (join `time_entry_valuation ↔ time_entry`, DTO `ProjectValuationLine`, table dashboard + gating coût) + 2 tests | 2026-09-04 |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|

## Métriques
- **Tâches** : 26 total · 13 terminées (50%)
- **Heures** : 63h estimées · ~28h consommées · ~35h restantes
- **F2 levé** : /valorisation démontrable (CA 3 600 €, 5/5 imputations valorisées sur le seed)
- **US-068** : login/logout web (form_login, 2 pare-feux, redirection /login), « Mon compte » (profil + mot de passe). Reste : mot de passe oublié (mailer + reset-password-bundle).
- **Points** : 22 engagés (US-060 largement pré-implémentée → risque réduit)
