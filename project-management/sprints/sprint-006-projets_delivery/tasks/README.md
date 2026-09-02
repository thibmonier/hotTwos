# Tâches — Sprint 6 : Projets & delivery

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-030 | Création de projet et cycle de vie | 5 | 8 | ~16h | 🔲 |
| US-031 | Structure en lots et jalons | 5 | 8 | ~16h | 🔲 |
| US-037 | Affectation et restriction d'imputation | 5 | 8 | ~16h | 🔲 |
| US-034 | Engagements externes | 3 | 6 | ~10h | 🔲 |
| US-038 | Clôture opérationnelle | 3 | 7 | ~11h | 🔲 |

**Total : ~37 tâches | ~69h** (21 points).

## Répartition par type (indicative)

| Type | Rôle |
|------|------|
| [DB] | Entités agrégat + migrations **RLS** (project enrichi, lots, jalons, affectations, engagements, réouverture) |
| [BE] | Use cases `final readonly` (création/statut/affectation/clôture), gates d'imputation, ports + adapters Doctrine |
| [FE-WEB] | **Conception UX/UI préalable** puis écrans Twig + Stimulus (création/liste projet, structure, équipe, engagements, clôture) |
| [TEST] | Unit (domaine/moteurs) + fonctionnel (API/écran) + intrusion **RLS** (+ via consume si handler async) |
| [DOC][REV] | Doc module `docs/modules/project.md` + revues `security-auditor` / `symfony-reviewer` / `accessibility-expert` |

## Fichiers

- [US-030 — Création & cycle de vie](./US-030-tasks.md)
- [US-031 — Lots & jalons](./US-031-tasks.md)
- [US-037 — Affectation & restriction d'imputation](./US-037-tasks.md)
- [US-034 — Engagements externes](./US-034-tasks.md)
- [US-038 — Clôture opérationnelle](./US-038-tasks.md)
- [Tâches techniques transverses](./technical-tasks.md)

## Conventions

- **ID** : `T-<US>-<NN>` · **Taille** : 0,5h – 8h · **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué
- **Vertical slice** : Domaine + Application + Infrastructure + UI + Tests par US.
- **Ordre** : US-030 (racine) → US-031 / US-037 → US-034 → US-038.
