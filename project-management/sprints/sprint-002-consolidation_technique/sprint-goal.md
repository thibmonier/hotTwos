# Sprint 2 : Consolidation technique du socle

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 2 |
| Début | 2026-09-01 |
| Fin | 2026-09-12 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~20 points |
| Base git | à brancher sur `main` **après merge de la PR #2** (Walking Skeleton) |

## Sprint Goal

> « Le socle technique est durci et reproductible : le schéma est versionné par migrations, l'isolation RLS est active au runtime, l'exécution worker est éprouvée et l'observabilité est opérationnelle. »

Ce sprint solidifie la charpente posée au Sprint 1 **avant** d'y greffer la valeur métier (saisie de temps), conformément à la décision PO. Il transforme les mécanismes prouvés sur sonde (RLS `FORCE`, durcissement analytique) en garanties actives en production, et solde la dette identifiée en rétrospective Sprint 1.

## Definition of Done (rappel)

- [ ] Tests unitaires + intégration verts, couverture ≥ 80 % sur le code touché
- [ ] `make ci` vert (PHPStan max, Deptrac 0, cs-fixer, Rector, gitleaks)
- [ ] Documentation mise à jour (ADR si décision structurante)
- [ ] Pas de dette technique ajoutée
- [ ] Déployable en production (staging à jour)

## Sprint Backlog

| ID | Titre | Points | Origine | Statut |
|----|-------|--------|---------|--------|
| TECH-1 | Migrations Doctrine + durcissement analytique versionné (RLS + trigger) | 5 | Rétro S1 · Action 1 | ✅ Done |
| TECH-2 | RLS active au runtime par requête (rôle applicatif dédié) — finition US-001 | 5 | Rétro S1 · Action 2 | ✅ Done |
| US-006 | Exécution FrankenPHP **worker** réelle + état inter-requêtes sûr (`ARC-47..50`) | 5 | Résiduel Sprint 0 (T-006-02) | ✅ Done |
| US-008 | Secrets hors dépôt/rotatifs + observabilité de base (P95, suivi d'erreurs) | 5 | Résiduel Sprint 0 | ✅ Done |

**Total engagé : 20 points** (dans la vélocité cible 20–40 ; marge volontaire, tâches d'infra à risque).

## Objectifs de sortie (critères d'acceptation du sprint)

1. **Schéma versionné** : le schéma se construit via migrations Doctrine (plus de SchemaTool en prod) ; le durcissement RLS + trigger analytique est une migration idempotente. CI applique les migrations.
2. **RLS runtime** : un rôle applicatif **non-superutilisateur** exécute les requêtes ; `app.current_tenant` est positionné par requête ; un test d'intrusion « RLS seule » (filtre ORM désactivé) est vert sur `ProtectedRecord` et les faits analytiques, en contexte requête réel.
3. **Worker éprouvé** : l'application tourne en mode worker FrankenPHP ; un test démontre l'absence de fuite d'état inter-requêtes (tenant, sécurité) entre deux requêtes servies par le même worker (`RSQ-15`, `ARC-47`).
4. **Observabilité** : métriques P95 et suivi d'erreurs opérationnels sur la staging ; secrets gérés hors dépôt et rotatifs sans redéploiement du code.

## Dépendances

| Élément | Dépend de | Statut |
|---------|-----------|--------|
| Tout le sprint | Merge PR #2 (Sprint 1) dans `main` | ⏳ En attente du feu vert PO |
| TECH-2 (RLS runtime) | TECH-1 (migrations pour créer le rôle + policies) | Séquentiel |
| US-006 (worker) | Socle Symfony/FrankenPHP (Sprint 0) | ✅ |
| US-008 (observabilité) | Staging Railway (Sprint 0) | ✅ en ligne |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| RLS runtime casse des requêtes légitimes (rôle mal doté) | Moyenne | Fort | Rôle applicatif à privilèges minimaux + tests d'intrusion ET tests nominaux ; bascule progressive |
| Migrations divergent du schéma SchemaTool actuel | Moyenne | Moyen | `doctrine:schema:validate` en CI ; diff revu avant merge |
| Fuite d'état worker non détectée | Faible | Fort | Test explicite deux-requêtes-même-worker ; `RSQ-15` critère de sortie |
| Secrets/observabilité liés au plan Railway | Faible | Moyen | Solution vault-compatible ; métriques via endpoint applicatif si besoin |

## Cérémonies

| Cérémonie | Cadence |
|-----------|---------|
| Sprint Planning P1 (Quoi) / P2 (Comment) | Début de sprint |
| Daily Scrum | Quotidien (`daily-notes/`) |
| Affinage (backlog Sprint 3 : saisie de temps) | Mi-sprint |
| Sprint Review | Fin de sprint |
| Rétrospective | Fin de sprint |

## Notes

- La **valeur métier (saisie de temps)** reste engagée pour le **Sprint 3** ; le garde-fou `RSQ-17` (jours depuis le dernier test d'usage réel) est surveillé — ce sprint technique ne doit pas glisser au-delà de 2 semaines.
- Les enablers TECH-1/TECH-2 déchargent la dette Walking Skeleton et débloquent l'ajout d'entités métier (référentiels) au Sprint 3 sur une base saine.
