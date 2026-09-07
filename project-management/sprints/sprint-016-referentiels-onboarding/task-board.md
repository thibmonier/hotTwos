# Task Board - Sprint 016 : Référentiels & mise en route

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

### US-012 — Jours fériés & jours ouvrés (ordre 1)
| ID | Tâche | Est. |
|----|-------|------|
| T-012-01 | [DB] Entité Holiday + port | 2h |
| T-012-02 | [DB] Migration + RLS | 1.5h |
| T-012-03 | [INFRA] Doctrine repo + binding | 1.5h |
| T-012-04 | [BE] WorkingDaysCalculator (TDD) | 3h |
| T-012-05 | [BE] Refactor DRY (4 inline) | 3h |
| T-012-06 | [FE-WEB] Controller jours-feries | 2h |
| T-012-07 | [FE-WEB] Twig liste+form | 2h |
| T-012-08 | [TEST] Unit calculateur | 2h |
| T-012-09 | [TEST] Functional CRUD+occupation | 3h |
| T-012-10 | [DOC] PHPDoc+note | 0.5h |
| T-012-11 | [REV] Review+CI | 1.5h |

### US-013 — Compétences (ordre 2)
| ID | Tâche | Est. |
|----|-------|------|
| T-013-01 | [DB] Skill/Scale/Assignment + ports | 3h |
| T-013-02 | [DB] Migration + RLS | 1.5h |
| T-013-03 | [INFRA] Repos Doctrine + bindings | 2h |
| T-013-04 | [BE] Service (unicité, désactivation) | 3h |
| T-013-05 | [FE-WEB] Controller competences | 2h |
| T-013-06 | [FE-WEB] Twig | 2.5h |
| T-013-07 | [TEST] Unit | 1.5h |
| T-013-08 | [TEST] Functional | 2.5h |
| T-013-09 | [DOC]+[REV] | 1.5h |

### US-020 — Journal d'audit (ordre 3)
| ID | Tâche | Est. |
|----|-------|------|
| T-020-01 | [DB] ConfigAuditEntry + enum + port | 2.5h |
| T-020-02 | [DB] Migration + RLS | 1.5h |
| T-020-03 | [INFRA] Recorder append-only | 1.5h |
| T-020-04 | [BE] Décision gating HAB-6 | 1h |
| T-020-05 | [BE] Instrumentation (seuils, fériés, compétences) | 2.5h |
| T-020-06 | [FE-WEB] Page audit (lecture gated) | 2.5h |
| T-020-07 | [TEST] Unit+Functional | 3h |
| T-020-08 | [DOC]+[REV] | 1.5h |

### US-019 — Onboarding (ordre 4)
| ID | Tâche | Est. |
|----|-------|------|
| T-019-01 | [BE] InitializeTenantDefaults (idempotent) | 4h |
| T-019-02 | [OPS] Commande tenant:init | 2h |
| T-019-03 | [DB] Checklist (modèle/décision) | 2h |
| T-019-04 | [DB] Migration + RLS (si entité) | 1h |
| T-019-05 | [FE-WEB] Dashboard checklist | 3h |
| T-019-06 | [TEST] Unit | 2h |
| T-019-07 | [TEST] Functional | 3h |
| T-019-08 | [DOC]+[REV] | 1.5h |

### Transverses
| ID | Tâche | Est. |
|----|-------|------|
| T-TECH-01 | [OPS] Branche par story | 0.25h×4 |
| T-TECH-04 | [REV] Clôture S16 + make ci | 2h |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|

## 🚫 Bloqué
| ID | Raison |
|----|--------|

## Métriques (clôture)
- **Stories** : 4/4 livrées ✅ (US-012 #89, US-013 #90, US-020 #91, US-019 #92)
- **Points** : 16/16 livrés (capacité 22)
- **Tests** : 645 → **672** verts | `make ci` vert à chaque merge
- **Migrations** : `holiday`, `skill*` (3), `config_audit_entry` (+ RLS)
