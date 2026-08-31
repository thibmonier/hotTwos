# Sprint 3 : Première saisie de temps valorisable

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 3 |
| Début | 2026-09-15 |
| Fin | 2026-09-26 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~23 points (vélocité observée S1=29, S2=20 → moy. ~24) |
| Base git | `main` (Sprints 1 & 2 mergés) |

## Sprint Goal

> « Un collaborateur saisit sa semaine de temps en moins de 2 minutes, et son chef de projet la valide par lot. »

**C'est la première valeur métier visible** du produit et l'engagement `RSQ-17` (garde-fou anti-effet-tunnel). Le critère d'adoption `RSQ-1` — la saisie n'est pas perçue comme une corvée — est au cœur du sprint (US-051, bloquant). La valorisation automatique (chiffrage) suivra au Sprint 4, une fois les taux (US-011) posés.

## Definition of Done (rappel)

- [ ] Tests unitaires + intégration verts, couverture ≥ 80 % sur le code touché
- [ ] `make ci` vert (PHPStan max, Deptrac 0, cs-fixer, Rector, gitleaks)
- [ ] Isolation multi-tenant respectée (filtre ORM ; RLS runtime si activée)
- [ ] Habilitation vérifiée côté serveur (ARC-19/ARC-106) — un collaborateur ne saisit que pour lui, un chef de projet ne valide que ses projets
- [ ] Documentation mise à jour ; déployable en production (staging à jour)

## Sprint Backlog

| ID | Titre | Points | Persona | Statut |
|----|-------|--------|---------|--------|
| US-050 | Saisie hebdomadaire (+ Projet minimal) | 5 | P1 Camille | ✅ Done |
| US-051 | Semaine ≤ 2 min (bloquant RSQ-1) | 8 | P1 Camille | 🔄 cœur livré (reste duplication + E2E chrono) |
| US-055 | Validation par lot | 5 | P2 Marc | ✅ Done |
| TECH-3 | Activer la RLS en production (DBT-RUN-2) + smoke de déploiement automatisé | 5 | Équipe technique | 🔄 code + smoke livrés ; bascule ops (mot de passe + DATABASE_URL) en attente |

**Total engagé : 23 points** (dans la vélocité cible 20–40).

## Objectifs de sortie (critères d'acceptation du sprint)

1. **Saisie** : un collaborateur impute son temps sur des projets (hebdo/quotidien), avec duplication de semaine/jour et commentaire ; une journée type sur 2 projets se saisit en ≤ 30 s (US-050).
2. **Adoption (bloquant)** : une semaine nominale (3–5 projets + absences) se saisit en **≤ 2 minutes**, mesuré sur des parcours représentatifs (US-051, `RSQ-1`).
3. **Validation** : un chef de projet valide/refuse par lot (motif obligatoire au refus) la semaine de son équipe en < 5 min (US-055).
4. **Isolation prod** : la RLS est **active en production** (bascule `DATABASE_URL` sur `hotones_app`) et vérifiée par intrusion ; un smoke de déploiement automatisé garde les endpoints critiques (TECH-3).

## Cadrage / périmètre

- **Référentiel Projet minimal** : US-050 introduit une entité `Project` dégénérée (tenant, code, nom, statut actif) suffisante pour imputer. La structure riche (lots, jalons, budgets — US-030/031/033) et l'affectation/restriction d'imputation (US-037) sont **reportées**.
- **Valorisation reportée** : le chiffrage automatique (US-060) dépend des taux (US-011) — **Sprint 4**. Ce sprint livre la saisie et la validation, pas encore la valeur en euros.
- **Absences** : US-051 inclut les absences dans la saisie ; la gestion complète des compteurs (US-054) est reportée — au Sprint 3, une saisie d'absence simple suffit au critère ≤ 2 min.

## Dépendances

| Élément | Dépend de | Statut |
|---------|-----------|--------|
| US-050 | US-001 (multi-tenant), US-003 (RBAC) | ✅ livrés |
| US-051 | US-050 (saisie de base) | séquentiel (même sprint) |
| US-055 | US-050 (données à valider), US-003 (habilitation chef de projet) | séquentiel |
| TECH-3 | Rôle `hotones_app` (créé au Sprint 2) | ✅ prêt |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Critère ≤ 2 min non atteint (`RSQ-1`) | Moyenne | **Fort** | UX de saisie soignée (duplication, raccourcis, pré-remplissage minimal) ; mesure réelle sur parcours ; critère de sortie bloquant |
| RLS prod casse des requêtes légitimes | Moyenne | Fort | Bascule progressive, tests nominaux + intrusion, rollback `DATABASE_URL` préparé |
| Référentiel Projet minimal insuffisant pour saisir | Faible | Moyen | Périmètre explicite ; enrichissement au Sprint 4 sans rework (OCP) |
| Habilitation de validation mal cadrée (chef voit trop) | Faible | Fort | `Authorizer` (US-003) + périmètre « ses projets » vérifié côté serveur (ARC-106) |

## Cérémonies

| Cérémonie | Cadence |
|-----------|---------|
| Sprint Planning P1/P2 | Début de sprint |
| Daily Scrum | Quotidien (`daily-notes/`) |
| Affinage (Sprint 4 : valorisation + taux) | Mi-sprint |
| Sprint Review (démo saisie ≤ 2 min) | Fin de sprint |
| Rétrospective | Fin de sprint |

## Notes

- Ce sprint **honore `RSQ-17`** : première valeur d'usage réelle livrée. Ne pas décaler.
- Actions de rétro Sprint 2 : TECH-3 porte l'activation RLS prod + le smoke ; l'IaC Railway (`.railway/railway.ts`) reste une action ouverte, à intégrer si le temps le permet.
