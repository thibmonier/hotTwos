# Tâches techniques — Sprint 25 (chantiers rétro S24, hors points)

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Origine | Statut |
|----|------|-------|------------|---------|--------|
| T-TECH-01 | [OPS] | Nettoyage du bruit du working tree (`.gitignore`) | 1h | rétro S24 ACTION-4 | 🔲 |
| T-TECH-02 | [OPS] | Suivi de la stabilité CodeQL `Analyze` | 0.5h | suivi S23/S24 | 🔲 |
| T-TECH-03 | [OPS] | Process : statut d'US → `done` au merge | 0.5h | rétro S24 ACTION-1 | 🔲 |

**Total estimé** : ~2h

---

## Détail des tâches

### T-TECH-01 : Nettoyage du bruit working tree (ACTION-4)
- **Type** : [OPS] · **Estimation** : 1h · **Deadline** : J2

**Description** : les fichiers `.idea/`, `RESUME-*` et le churn d'images `tailadmin-ref/` traînent dans `git status` depuis plusieurs sprints.

**Critères** :
- [ ] Décision par fichier : commiter les refs de conception **utiles** (`tailadmin-ref/*.png` récentes) ; ajouter le reste au `.gitignore` (`.idea/`, `RESUME-*.md`)
- [ ] `git status` propre hors travail en cours

### T-TECH-02 : Suivi CodeQL `Analyze`
- **Type** : [OPS] · **Estimation** : 0.5h

**Description** : reconfirmer la stabilité du job `Analyze` (default-setup, non-requis) sur les PRs du sprint.

**Critères** :
- [ ] Job vert sur les PRs S25 ; sinon basculer en advanced-setup avec `continue-on-error` documenté

### T-TECH-03 : Statut d'US → `done` au merge (ACTION-1)
- **Type** : [OPS] · **Estimation** : 0.5h

**Description** : appliquer systématiquement la bascule de statut dans `.bmad/sprint-status.yaml` **au merge** de chaque PR d'US (pas en clôture), pour garder la source de vérité fiable en cours de sprint.

**Critères** :
- [ ] En fin de sprint, 0 US mergée encore marquée `ready`/`in-progress`
