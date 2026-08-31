# Rétrospective — Sprint 1 (Walking Skeleton)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-08-31 |
| Format | Starfish (⭐) |
| Facilitateur | Scrum Master |
| Périmètre | US-001, US-002, US-003, US-005 (tranche S1) |

> **Directive Fondamentale (Norm Kerth).** « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait le meilleur travail possible, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles et de la situation du moment. »

## Rappel du Sprint

- **Sprint Goal :** charpente applicative — multi-tenant isolé (testé), auth/RBAC, modèle analytique reconstructible.
- **Résultat :** objectif **atteint**. US-001, US-002, US-003 et la tranche Sprint 1 d'US-005 livrées, `make ci` vert (65 tests / 153 assertions, PHPStan max, Deptrac 0), CI GitHub verte à chaque poussée.
- **Événements marquants :** incident « bug h2c » sur la staging (en réalité alias `curl→curlie` côté client) ; fuite de port Docker 5432/5433 contournée ; décision de cadrage « mécanisme sur sonde » pour US-003 et US-005 (report du métier).

## Observations (Starfish)

### 🟢 Continuer
- **TDD par tranches courtes commitées** (13 commits de feature sur le sprint) : chaque tranche verte et poussée, jamais de « big bang ».
- **`make ci` en miroir de la CI + hook pre-commit** : les problèmes (PHPStan, Deptrac, style) sont attrapés en local avant la poussée — 0 CI rouge sur les commits de code.
- **Deptrac + PHPStan niveau max** : les frontières Clean Architecture (UI↛Infra) et la qualité de typage ont été tenues sans compromis, y compris dans les tests.
- **Sondes de validation** (ProtectedRecord, `/api/_probe`, `RevenueRecognized`) : prouver un invariant de sécurité bout-en-bout sans attendre le métier.

### 🟡 Commencer
- **Migrations Doctrine versionnées** : aujourd'hui le schéma (et le DDL de durcissement RLS/trigger) passe par SchemaTool/commande ; il faut une chaîne de migration reproductible pour la prod.
- **Activer la RLS au runtime** (rôle applicatif non-superutilisateur + `SET app.current_tenant` par requête) : la RLS est en place et testée sous rôle dédié, mais pas encore branchée sur le cycle de requête réel (finition US-001).
- **Journal explicite des « dettes Walking Skeleton »** : centraliser les reports (CA-3, swap atomique, faits métier, ARC-106) dans un seul endroit suivi, au-delà des notes par US.

### 🔴 Arrêter
- **Suspecter le serveur avant de vérifier le client** : ~5 déploiements Railway gaspillés sur un « bug h2c » inexistant, causé par l'alias `curl→curlie` (POST au lieu de GET). Réflexe à supprimer.
- **Découvrir Rector/cs-fixer au moment du commit** : plusieurs allers-retours parce que les fixers tournaient après coup (désormais `rector` est dans `make ci`).

### ⬆️ Plus de
- **Décisions de cadrage explicites et tracées** (mécanisme vs métier) : très efficace pour tenir la taille du sprint sans sacrifier la conformité CDC — à généraliser à chaque US « à cheval ».
- **Tests d'intrusion / négatifs** (RLS hors filtre ORM, écriture directe rejetée, chef de projet 403 sur le coût) : ce sont eux qui prouvent réellement la sécurité.

### ⬇️ Moins de
- **Friction d'environnement local** (fuite de port Docker 5432/5433, `phpunit.xml` local gitignoré) : à stabiliser pour ne plus y penser.
- **DDL appliqué « à la main »** (commande de durcissement hors migration) : transitoire, à réduire dès l'arrivée des migrations.

## Thèmes identifiés

### Thème 1 : Reproductibilité de l'infrastructure de données (votes ●●●●●)
Le schéma, la RLS et les triggers ne sont pas encore versionnés en migrations → risque de dérive entre local, CI et prod.

**5 Pourquoi :** Pourquoi appliquer le DDL à la main ? → Pas de chaîne de migration. → Pourquoi ? → Non nécessaire au tout début (SchemaTool en test). → Cause racine : l'outillage migration a été volontairement différé, il devient bloquant avec la RLS/triggers.
**Solution :** introduire doctrine/migrations au Sprint 2 et y porter le durcissement.

### Thème 2 : Vérifier ses hypothèses de diagnostic (votes ●●●)
L'incident `curlie` a coûté plusieurs cycles. Cause racine : hypothèse « serveur fautif » non vérifiée côté client.
**Solution :** réflexe documenté — reproduire avec la requête exacte et inspecter ce que le client envoie avant de toucher au serveur.

## Actions Sprint 2

### Action 1 : Migrations Doctrine + durcissement versionné
| Attribut | Valeur |
|----------|--------|
| Description | Introduire doctrine/migrations ; convertir le DDL de RLS + trigger anti-écriture en migration versionnée et idempotente |
| Responsable | Équipe technique |
| Deadline | Sprint 2 |
| DoD | Le schéma prod se construit via migrations ; `app:analytics:harden-schema` devient une migration ; CI applique les migrations |
| Priorité | Haute |

### Action 2 : RLS runtime bout-en-bout (finition US-001)
| Attribut | Valeur |
|----------|--------|
| Description | Rôle applicatif non-superutilisateur + `SET app.current_tenant` par requête ; RLS active sur toutes les entités TenantOwned |
| Responsable | Équipe technique |
| Deadline | Sprint 2 |
| DoD | Test d'intrusion « RLS seule » (filtre ORM désactivé) vert sur ProtectedRecord et les faits analytiques, en contexte requête réel |
| Priorité | Haute |

### Action 3 : Relecture croisée ARC-106 avant faits/ressources métier
| Attribut | Valeur |
|----------|--------|
| Description | Planifier la relecture humaine ligne à ligne des règles d'habilitation/isolation avant tout branchement de données sensibles réelles |
| Responsable | Product Owner + Tech Lead |
| Deadline | Avant la 1re US métier sensible (Sprint 2) |
| DoD | Checklist ARC-106 revue et signée, tracée dans la US concernée |
| Priorité | Moyenne |

### Action 4 : Registre unique des dettes Walking Skeleton
| Attribut | Valeur |
|----------|--------|
| Description | Centraliser les reports (US-005 CA-3, swap atomique, faits métier, RLS runtime, ARC-106) dans un fichier suivi unique |
| Responsable | Product Owner |
| Deadline | Début Sprint 2 |
| DoD | `project-management/backlog/walking-skeleton-debt.md` créé et référencé dans l'index |
| Priorité | Moyenne |

## Suivi des actions précédentes (Sprint 0)

| Sprint | Action | Statut |
|--------|--------|--------|
| S-0 | Mettre en ligne socle + CI/CD + staging | ✅ Fait (staging Railway PHP 8.5, CI verte, branch protection) |
| S-0 | Outillage qualité (PHPStan max, Deptrac, cs-fixer, Rector, gitleaks) | ✅ Fait (rejoué à chaque commit) |
| S-0 → S-1 | Ajouter Sprint 0 (socle) avant Sprint 1 | ✅ Fait |

## Check-out — ROTI

| Perspective | Score | Commentaire |
|-------------|-------|-------------|
| Équipe technique | 5/5 | Objectif atteint, tout vert, dette maîtrisée et tracée |

**À emporter :** « Prouver la sécurité par des tests négatifs, sur sonde, sans attendre le métier — et versionner l'infra de données au plus tôt. »

## Métriques à surveiller (issues du CDC)

- Vélocité réelle vs prévision (34 pts) — 4 US du socle livrées (dont 2 US de 8 pts à mécanisme complet) ; recalibrer la prévision Sprint 2.
- Jours depuis le dernier test d'usage réel (`RSQ-17`, seuil > 30 j) — **la première valeur métier (saisie de temps) est due au Sprint 2** : garde-fou contre l'effet tunnel.
- Dépréciations déclenchées par le code (`ARC-51`, seuil > 0) — 0 (Rector/PHPStan verts).
- Couverture de tests sur règles critiques (`ENF-MAINT-1`, seuil < 80 %) — isolation, habilitation, non-divergence couvertes par des tests dédiés.
