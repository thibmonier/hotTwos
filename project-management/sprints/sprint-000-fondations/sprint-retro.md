# Rétrospective — Sprint 0 (Fondations & Outillage)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-08-31 |
| Format | Starfish ⭐ |
| Contexte | Développement assisté par agent, session unique (pas d'équipe multi-personnes) |
| Sprint Goal | Installer le socle technique pour que le Sprint 1 puisse construire dessus |

> **Directive Fondamentale (Norman Kerth)** : « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences, des ressources disponibles et de la situation. »

## Rappel du Sprint

- **Livré** : US-006 (squelette Symfony 8 + API Platform DTO + Twig/Turbo), US-007 (Docker + PostgreSQL 16/pgvector), US-009 (PHPStan max, Rector, php-cs-fixer, hook pré-commit, conventions), US-004 (CI GitHub Actions 11 étapes), US-008 (staging Railway déployé et **en ligne**).
- **Base** : 4 tests verts (→ 11 avec le démarrage US-001), PHPStan niveau max 0, Deptrac 0 violation, `make ci` vert.
- **Événements marquants** : déploiement Railway ayant demandé ~8 itérations de débogage ; CI GitHub bloquée par la facturation du compte ; une interruption de processus (agents en arrière-plan perdus) récupérée via vérification du filesystem ; un doublon de projet Railway créé puis supprimé.

## ⭐ Starfish

### 🟢 CONTINUER (ce qui fonctionne)
- **TDD systématique** (RED → GREEN) sur chaque brique (`/health`, DTO, page Twig, TenantId, contexte tenant).
- **Vérification avant chaque commit** : `make ci` (php-cs-fixer + PHPStan max + Deptrac + tests) + hook pré-commit qui rejoue tout — aucun commit rouge.
- **Commits atomiques par US**, messages Conventional Commits, écarts documentés dans le message même (ex : AssetMapper vs Reprise).
- **Isolation méthodique des causes** au débogage : chaque erreur Railway → une cause identifiée → un fix ciblé.
- **Prudence sur l'infra facturée** : confirmation avant de créer des ressources cloud (question posée avant Railway).

### 🟡 COMMENCER (à essayer)
- **`docker build` + test local AVANT tout `railway up`** — érigé en règle (voir Actions). Le test local a fini par isoler tous les pièges bien plus vite que les cycles Railway.
- **Runbook de déploiement** capitalisant les pièges FrankenPHP (Composer, superuser, cache prod, importmap, h2c).
- **Vérifier les prérequis externes** (facturation GitHub Actions, quotas) **avant** de câbler une CI qui en dépend.

### 🔴 ARRÊTER
- **Déployer sur Railway sans avoir validé l'image en local** — les 3 premiers échecs (`composer not found`, runtime, symfony-cmd) auraient été vus en local.
- **Se fier au statut Railway immédiat** : il affiche l'ancien déploiement (« Failed ») pendant que le nouveau build démarre → faux négatifs, boucles de surveillance interrompues trop tôt.
- **Fan-out de sous-agents rédigeant des tables croisées sans source unique** : l'agent EPIC a inventé des titres d'US divergents (corrigé après coup).

### ⬆️ PLUS DE
- **Provisionnement cloud progressif** avec point de contrôle (provisionner → tester → déployer).
- **Vérifications déterministes** (scripts de comptage INVEST, `docker exec curl` interne) plutôt que le jugement variable d'un agent validateur ou un client HTTP capricieux.

### ⬇️ MOINS DE
- **Itérations de déploiement** (~8 builds Railway) — réductibles par le test local systématique.
- **Dépendance à des composants jeunes non maîtrisés** en première intention (le mode worker FrankenPHP reste à finaliser — `T-006-02`).

## Analyse — cause racine du déploiement laborieux

**Problème** : le déploiement staging a demandé ~8 itérations.

**5 Pourquoi** :
1. Pourquoi tant d'itérations ? → Chaque build échouait sur une cause différente.
2. Pourquoi ne pas les voir d'un coup ? → Les erreurs étaient séquentielles (l'une masquait la suivante).
3. Pourquoi les découvrir sur Railway (lent) ? → Le build n'était pas testé en local d'abord.
4. Pourquoi pas testé en local ? → Sous-estimation de l'écart image de base minimale (FrankenPHP) ↔ app Symfony prod.
5. **Cause racine** → Absence d'une étape « valider l'image de production en local » dans la Definition of Done d'une story d'infra.

**Solution** : intégrer le test d'image local à la DoD des stories de déploiement (Action 1).

## 🎯 Actions pour le Sprint 1

### Action 1 : Valider toute image de prod en local avant déploiement
| Attribut | Valeur |
|----------|--------|
| Description | `docker build` + run + `curl` interne (`/health` 200) obligatoires avant tout `railway up`. |
| Deadline | Sprint 1 (immédiat) |
| DoD | Aucun déploiement cloud sans build local vert au préalable ; ajouté à la DoD des stories d'infra. |
| Priorité | Haute |

### Action 2 : Runbook de déploiement (capitalisation)
| Attribut | Valeur |
|----------|--------|
| Description | Documenter dans `docs/` les pièges résolus (Composer via `@composer`, `COMPOSER_ALLOW_SUPERUSER`, `cache:clear` au build, `importmap:install`, `serverVersion`, Caddyfile `protocols h1 h2`). |
| Deadline | Sprint 1 |
| DoD | `docs/deploiement-staging.md` créé et référencé depuis le README. |
| Priorité | Moyenne |

### Action 3 : Débloquer la CI GitHub (prérequis externe)
| Attribut | Valeur |
|----------|--------|
| Description | Régler la limite de facturation GitHub Actions, puis activer la branch protection (`ARC-89`). |
| Responsable | Sponsor / propriétaire du compte |
| Deadline | Avant de compter sur la CI comme garde-fou de merge |
| DoD | Un run CI passe au vert sur GitHub ; branch protection active sur `main`. |
| Priorité | Haute |

### Action 4 : Source unique pour la génération croisée d'artefacts
| Attribut | Valeur |
|----------|--------|
| Description | Fixer les titres/numéros d'US avant de déléguer une table qui les référence (éviter les divergences d'agents). |
| Deadline | Sprint 1 |
| DoD | Aucune divergence entre index, EPIC et fichiers US au prochain contrôle. |
| Priorité | Basse |

## Check-out

**ROTI** (retour sur temps investi) — auto-évaluation de la session : **4/5**. Beaucoup livré (socle + staging en ligne), mais le déploiement a coûté du temps évitable (d'où l'Action 1).

**Ce que j'emporte** : un socle vert, testé et déployé — et une leçon nette : *tester l'image de production en local est le raccourci, pas le détour.*
