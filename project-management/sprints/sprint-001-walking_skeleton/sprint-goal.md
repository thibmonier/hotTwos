# Sprint 1 : Walking Skeleton — Socle multi-tenant

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 1 |
| Début | 2026-09-01 (lundi) |
| Fin | 2026-09-12 (vendredi) |
| Durée | 10 jours ouvrés (2 semaines) |
| Capacité (prévision) | ~29 points |
| Lot | Lot 1 — Cœur (fondations applicatives) |
| Prérequis | **Sprint 0 terminé** (socle, CI/CD, staging, outillage) |

## Sprint Goal

> **Établir la charpente technique de HotOnes : un tenant strictement isolé (barrière vérifiée par test d'intrusion), des utilisateurs authentifiés et habilités, une chaîne CI/CD verte en mode worker, et un modèle analytique reconstructible sans divergence — le socle sur lequel la saisie de temps se branchera au Sprint 2.**

Ce sprint pose le *Walking Skeleton* au sens du CDC : la tranche technique end-to-end (multi-tenant → sécurité → analytique) qui rend toute valeur métier ultérieure sûre et mesurable. La **première valeur utilisateur visible** (saisie de temps) est engagée pour le **Sprint 2** — cet engagement est le garde-fou contre l'effet tunnel (`RSQ-11`) et la « construction refuge » (`RSQ-17`).

## ⚠️ Hypothèse de capacité (à confirmer — ARB-20 / HYP-15)

Le périmètre complet a été retenu en supposant l'équipe cible (~4,5 ETP) constituée. La capacité ci-dessous suppose une **équipe de réalisation** disponible ; si le démarrage se fait à 1 personne (`HYP-15`), **la durée du sprint s'allonge à capacité constante** — le contenu reste valable, le calendrier non.

| Membre (hypothèse) | Jours dispo | Focus | Capacité |
|---|---|---|---|
| Responsable technique | 10 | 60 % | 6 j |
| Développeur 1 | 10 | 75 % | 7,5 j |
| Développeur 2 | 10 | 75 % | 7,5 j |
| **Total** | | | **~21 j-dev** |

> Pas d'historique de vélocité (sprint 1) : l'engagement de **34 pts est une prévision**, à recalibrer après ce sprint. Sprint de fondation avec setup lourd (conteneurisation, CI, RLS, mode worker) — surveiller le risque de sous-livraison.

## Definition of Done (rappel — cf. `definition-of-done.md`)

- [ ] Tous les critères d'acceptation (Gherkin) validés
- [ ] TDD : un test nommé par règle de gestion `RG-*` (`ARC-103`) ; tests écrits depuis l'exigence (`ARC-108`)
- [ ] Couverture ≥ 80 % sur règles critiques (`ENF-MAINT-1`) — bloquant CI
- [ ] PHPStan niveau max + Deptrac (frontières) verts
- [ ] Tests d'isolation multi-tenant **et** tests en mode worker verts (`ARC-50`)
- [ ] Périmètre de sécurité (habilitations, isolation) relu à la main, non délégué (`ARC-106`)
- [ ] Tolérance zéro aux dépréciations (`ARC-51`)
- [ ] Code review approuvée
- [ ] Documentation (ADR/tech-spec) à jour

## Sprint Backlog — 34 points

| ID | Titre | Points | Statut | Dépend de |
|----|-------|--------|--------|-----------|
| US-001 | Fondation multi-tenant et isolation des données | 8 | 🔵 To Do | Sprint 0 |
| US-002 | Authentification et cycle de vie des utilisateurs | 5 | 🔵 To Do | Sprint 0 |
| US-003 | Rôles et habilitations (RBAC + périmètre) | 8 | 🔵 To Do | US-001 |
| US-005 | Modèle analytique en étoile et non-divergence | 8 | 🔵 To Do | US-001 |

**Total engagé : 29 points** (dans la vélocité cible 20-40 pts/sprint).

> `US-004` (chaîne CI/CD) est déplacée au **Sprint 0**. L'étape « tests d'isolation multi-tenant » de la pipeline se remplit ici avec US-001.

## Critères de sortie du sprint (bloquants)

- [ ] **Isolation inter-tenant** validée par test d'intrusion dédié (identifiant forgé, export) — `ENF-SEC-4`
- [ ] Jeu de tests exécuté **en configuration worker**, vert — `ARC-50`
- [ ] **Test de non-divergence** analytique/transactionnel vert en CI — `ARC-113`
- [ ] L'étape « tests d'isolation multi-tenant » de la pipeline (installée au Sprint 0) passe au vert — `ADR-12`
- [ ] Démonstration en Sprint Review : création d'un tenant isolé, connexion d'un utilisateur habilité, reconstruction du modèle analytique

## Dépendances externes / prérequis (lot 0)

| Prérequis | Réf | Statut |
|---|---|---|
| Choix du socle technique arrêté | `ARB-18` / cdc/12 | ✅ (16 ADR) |
| Squelette technique validé bout-en-bout | `ADR-1/2/8` | ⏳ lot 0 |
| Audit de l'existant | `AUD-1` / `AUD-2` | ⏳ **à réaliser avant** |
| Environnement conteneurisé + données de test 3 tailles | `ADR-11`, `ARC-87` | ⏳ lot 0 |

## Risques identifiés

| Risque | Réf | P×I | Mitigation |
|---|---|---|---|
| Fuite d'état entre requêtes en mode worker | `RSQ-15` | 15 | `ARC-47..50`, tests en config worker dès le sprint (US-004) |
| Fuite inter-tenant (barrière insuffisante) | `RSQ-2` | 15 | Double barrière `ADR-6` (US-001), test d'intrusion critère de sortie |
| Effet tunnel : rien de démontrable à un utilisateur | `RSQ-11` | 12 | Engagement ferme de livrer la saisie (US-050) au Sprint 2 ; démo socle en review |
| Setup lourd → sous-livraison des 34 pts | — | — | Prévision à recalibrer ; US-005 décalable au Sprint 2 si besoin |

## Cérémonies

| Cérémonie | Quand | Durée |
|---|---|---|
| Sprint Planning (Part 1 & 2) | J1 | 2×2h |
| Daily Scrum | Quotidien | 15 min |
| Backlog Refinement (préparer Sprint 2 : saisie de temps) | J6 | 1h |
| Sprint Review | J10 | 2h |
| Rétrospective (Directive Fondamentale) | J10 | 1h30 |

> **Directive Fondamentale de la Rétrospective** : « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait le meilleur travail possible, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles et de la situation du moment. »

## Notes

Composition retenue : **socle EPIC-000 intégral** (US-001..005). Alternative possible (tranche verticale métier dès le Sprint 1) écartée car la chaîne saisie→valorisation dépasse une capacité de sprint et suppose le socle multi-tenant déjà posé. Le Walking Skeleton se complète Sprint 1 (charpente) → Sprint 2 (première saisie valorisée).
