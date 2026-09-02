# Task Board — Sprint 7 (Design system, EPIC-012)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Est. | Dépend de |
|----|-----|-------|------|-----------|
| T-061-01 | US-061 | tokens.css (couleurs/typo/espacements) | 3h | — |
| T-061-02 | US-061 | Bootstrap/Skote compilé (AssetMapper) + Poppins | 2h | — |
| T-061-03 | US-061 | Migrer app.css → tokens | 3h | T-061-01 |
| T-061-04 | US-061 | Composants tokenisés | 4h | T-061-01 |
| T-061-05 | US-061 | Page styleguide | 3h | T-061-04 |
| T-061-06 | US-061 | Contrastes axe-core + non-régression | 2h | T-061-05 |
| T-061-07 | US-061 | Doc + revue | 2h | T-061-06 |
| T-062-01 | US-062 | Finaliser maquettes écrans restants | 4h | — |
| T-062-02 | US-062 | Déclinaisons mobile + états | 3h | T-062-01 |
| T-062-03 | US-062 | Registre de validation | 1h | — |
| T-062-04 | US-062 | Revue a11y + validation PO (gate) | 2h | T-062-01/02 |
| T-063-01 | US-063 | Layout Skote (base.html.twig) | 3h | T-061-01 |
| T-063-02 | US-063 | Navigation filtrée RBAC | 2h | T-063-01 |
| T-063-03 | US-063 | Responsive + breakpoint 640/768 | 2h | T-063-01 |
| T-063-04 | US-063 | Bascule thème (Stimulus) | 2h | T-063-01 |
| T-063-05 | US-063 | Non-régression + recette | 2h | T-063-02/03/04 |
| T-063-06 | US-063 | Revue | 1h | T-063-05 |
| T-TECH-02 | dette | Budget assets (poids CSS) | 2h | T-061-02 |
| T-064-01 | US-064 | Reskin saisie (week/day) | 3h | T-061-04, T-063-01, T-062-04 |
| T-064-02 | US-064 | Reskin complétude (F-S5-4/F-S5-5) | 3h | idem |
| T-064-03 | US-064 | Reskin valorisation | 2h | idem |
| T-064-04 | US-064 | Reskin projets + validation | 3h | idem |
| T-064-05 | US-064 | Reskin absences + relances | 2h | idem |
| T-064-06 | US-064 | Reskin organisation + profils + admin | 3h | idem |
| T-064-07 | US-064 | Non-régression + états | 3h | T-064-01…06 |
| T-064-08 | US-064 | Revue | 2h | T-064-07 |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|

## ✅ Terminé
| ID | US | Tâche | Terminé |
|----|-----|-------|---------|
| T-TECH-01 | dette | sprintf→set_config (3 sites) — **déjà résolu** (param lié en place : TenantSessionConfigurator:48, TenantContextMiddleware:67, DoctrineAnalyticsProjector:48) ; aucun changement | 2026-09-02 (vérifié) |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|
| T-064-* | US-064 | Attend la **validation PO des maquettes** (T-062-04) | Gate humain avant reskin |

## Métriques
- **Tâches** : 27 total · 0 terminées (0 %)
- **Heures** : ~67h estimées · 0h consommées
- **Prérequis** : merge PR #12 sur `main`
