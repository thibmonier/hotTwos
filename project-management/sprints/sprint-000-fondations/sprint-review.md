# Sprint Review — Sprint 0 (Fondations & Outillage)

**Date :** 2026-08-31

## Incrément démontré

Un socle applicatif **déployé et fonctionnel en ligne** : https://hottwos-production.up.railway.app

- **Squelette Symfony 8.1** en Clean Architecture (Domain / Application / Infrastructure / UI), frontières vérifiées par Deptrac.
- **Endpoint `/health`** (adaptateur HTTP → cas d'usage), **`/api/status`** (API Platform en mode DTO strict), page d'accueil **Twig/Turbo**.
- **PostgreSQL 16 + pgvector 0.8.2** via Docker Compose (extension vérifiée en conditions réelles).
- **Chaîne qualité** : PHPStan niveau max (0 erreur), Rector, php-cs-fixer, `composer audit`, hook pré-commit, conventions versionnées.
- **CI GitHub Actions** (11 étapes) et **staging Railway** (EU West) opérationnels.

## Sprint Goal atteint ?

**Oui.** Le socle permet au Sprint 1 de construire dessus : projet buildable, testé, conteneurisé, déployable. La démonstration end-to-end (page web + API + healthcheck) répond **200** sur le staging public.

## Stories livrées

| ID | Titre | Points | Statut |
|----|-------|--------|--------|
| US-006 | Squelette Symfony 8 + API DTO + Twig/Turbo | 8 | ✅ (worker réel reporté — T-006-02) |
| US-007 | Environnement conteneurisé + pgvector | 5 | ✅ (fixtures effectives → US-001) |
| US-004 | Chaîne CI/CD | 5 | ✅ (branch protection → déblocage billing) |
| US-008 | Staging Railway | 5 | ✅ en ligne |
| US-009 | Outillage qualité/sécurité + conventions | 5 | ✅ (test RG-* → US-001) |

**Points engagés : 28 · livrés : 28** (avec restes mineurs dépendant de US-001/externe, tracés).

## Feedback / observations

- Le staging fonctionne via `railway up` (déploiement local direct) ; l'auto-deploy GitHub attend une CI verte, **bloquée par la facturation** du compte.
- Le mode worker FrankenPHP (ADR-2) reste à activer (bridge runtime à résoudre).
- Écart assumé à tracer : **AssetMapper** utilisé au lieu de **Symfony Reprise** (ADR-5, expérimental).

## Ajustements du backlog

- Le **Sprint 1** démarre le Walking Skeleton applicatif : US-001 (multi-tenant, entamée), US-002 (auth), US-003 (RBAC), US-005 (analytique).
- Restes du Sprint 0 rattachés à leur dépendance : fixtures et test `RG-*` avec US-001 ; branch protection au déblocage de la CI.

## Prêt pour le Sprint 1 ?

- [x] Socle buildable, testé, déployé
- [x] Outillage qualité en place et vert
- [ ] `AUD-1`/`AUD-2` (audit de l'existant) — à mener en parallèle (hors backlog dev)
- [ ] CI verte sur GitHub (dépend du déblocage facturation)
