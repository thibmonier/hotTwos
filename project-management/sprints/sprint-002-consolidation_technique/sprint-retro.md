# Rétrospective — Sprint 2 (Consolidation technique)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-08-31 |
| Format | Starfish (⭐) |
| Facilitateur | Scrum Master |
| Périmètre | TECH-1, TECH-2, US-006, US-008 (20/20 pts) |

> **Directive Fondamentale (Norm Kerth).** « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait le meilleur travail possible, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles et de la situation du moment. »

## Rappel du Sprint

- **Goal atteint** : socle durci et reproductible (migrations versionnées, worker éprouvé, observabilité) — une réserve sur l'activation RLS en prod.
- **Résultat** : 20/20 pts, staging validé en direct en mode worker, CI verte.
- **Marquant** : la validation par déploiement réel a révélé plusieurs blocages d'écosystème (Symfony 8), tous contournés.

## Observations (Starfish)

### 🟢 Continuer
- **Valider par le déploiement réel** : c'est ce qui a révélé les vrais problèmes (paquet worker incompatible, `preDeployCommand` ignoré, base interne, schéma non migré) — invisibles en local/CI.
- **TDD par tranches commitées + `make ci`** : chaque tranche verte, 0 CI rouge sur le code ; les migrations validées sur base scratch avant commit.
- **Ports/DIP systématiques** : Deptrac a rattrapé deux fois une dépendance UI→Infra (MetricsController, HardenCommand) — corrigées par un port Application. La discipline paie.
- **Décisions d'outillage explicites avec le PO** (Sentry, /metrics) avant d'implémenter.

### 🟡 Commencer
- **Suite d'un smoke de déploiement automatisé** : un script post-déploiement qui vérifie `/health`, `/metrics`, `/api/login` (401) — aujourd'hui fait à la main.
- **Migrer la config Railway vers Infrastructure-as-Code** (`.railway/railway.ts`) : le config-as-code (`railway.toml`) est déprécié et son `preDeployCommand` a été silencieusement ignoré.
- **Vérifier la compatibilité Symfony 8 des paquets AVANT de s'engager** : deux paquets visés (`runtime/frankenphp-symfony`, recette `sentry`) ont posé problème.

### 🔴 Arrêter
- **Compter sur une fonctionnalité de plateforme non vérifiée** : le `preDeployCommand` a été supposé fonctionner ; il ne l'était pas. Vérifier l'effet réel, pas l'intention.
- **Supposer qu'un paquet « standard » supporte la dernière version du framework** : Symfony 8 étant récent, l'écosystème est en retard.

### ⬆️ Plus de
- **Bridges manuels maîtrisés quand l'écosystème est en retard** : le worker Symfony-8 a été écrit à la main plutôt que d'attendre le paquet — débloque tout de suite, avec dégradation gracieuse.
- **Résilience des migrations** (rôle applicatif tolérant au privilège) : permet de tourner sur un Postgres managé sans superutilisateur.

### ⬇️ Moins de
- **Allers-retours de déploiement** : 4 déploiements pour converger (worker → migrations → entrypoint → observabilité). Un smoke automatisé et l'IaC réduiraient le nombre d'itérations.

## Thème principal : la vérité est au déploiement (votes ●●●●●)

**Problème** : plusieurs hypothèses (paquet worker, preDeployCommand, base joignable) se sont révélées fausses seulement au déploiement.
**5 Pourquoi** : Pourquoi ces surprises ? → Non testables en local/CI. → Pourquoi ne pas les avoir anticipées ? → Écosystème Symfony 8 récent + plateforme évolutive. → Cause racine : absence de boucle de validation déploiement rapide et scriptée.
**Solution** : smoke post-déploiement automatisé + IaC Railway.

## Actions Sprint 3

### Action 1 : Smoke de déploiement automatisé
| Attribut | Valeur |
|----------|--------|
| Description | Script (ou job) post-déploiement vérifiant /health, /metrics, /api/login (401), /api/status |
| Responsable | Équipe technique |
| Deadline | Sprint 3 |
| DoD | `make smoke URL=...` (ou étape CI/CD) rouge si un endpoint dévie |
| Priorité | Haute |

### Action 2 : Migrer la config Railway vers IaC
| Attribut | Valeur |
|----------|--------|
| Description | Passer de `railway.toml` (déprécié) à `.railway/railway.ts` ; y porter la commande de migration si supportée |
| Responsable | Équipe technique |
| Deadline | Sprint 3 |
| DoD | Config Railway en IaC, sans avertissement de dépréciation |
| Priorité | Moyenne |

### Action 3 : Activer la RLS runtime en production (DBT-RUN-2)
| Attribut | Valeur |
|----------|--------|
| Description | Donner LOGIN + mot de passe à `hotones_app`, basculer `DATABASE_URL` prod dessus, vérifier l'isolation par intrusion en prod |
| Responsable | Équipe technique + PO |
| Deadline | Sprint 3 |
| DoD | Requête cross-tenant en prod → 0 ligne, filtre ORM désactivé |
| Priorité | Haute |

## Suivi des actions Sprint 1

| Action S1 | Statut |
|-----------|--------|
| A1 · Migrations Doctrine + durcissement versionné | ✅ Fait (TECH-1) |
| A2 · RLS runtime bout-en-bout | ⚠️ Mécanisme fait (TECH-2), activation prod → Action 3 ci-dessus |
| A3 · Relecture croisée ARC-106 avant faits métier | ⏳ À planifier avant Sprint 3 métier |
| A4 · Registre de dette Walking Skeleton | ✅ Fait (`walking-skeleton-debt.md`) |

## Check-out — ROTI

| Perspective | Score | Commentaire |
|-------------|-------|-------------|
| Équipe technique | 5/5 | Socle durci, staging validé en worker, 20/20 — l'investissement déploiement a payé |

**À emporter :** « La vérité est au déploiement : automatiser le smoke et vérifier l'effet réel, pas l'intention. »

## Métriques à surveiller (CDC)

- Jours depuis le dernier test d'usage réel (`RSQ-17`) — **la saisie de temps est due au Sprint 3** : ne plus décaler.
- Dépréciations (`ARC-51`) — 0 (une corrigée ce sprint).
- Couverture sur règles critiques (`ENF-MAINT-1`) — isolation, RLS, worker, observabilité couvertes.
