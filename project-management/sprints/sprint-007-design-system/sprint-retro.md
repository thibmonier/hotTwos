# Rétrospective — Sprint 7 (Design system, EPIC-012)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-03 |
| Format | Starfish (⭐) |
| Facilitateur | Scrum Master |
| Contexte | 1 dev + assistance IA ; pivot techno majeur en cours de sprint |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux
> qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des
> ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Goal** : design system posé et appliqué au lot 1. **Atteint ✅** malgré un pivot ADR-0018 → **ADR-0019**
  (Bootstrap/Skote → Tailwind v4 sans Node.js) décidé en cours de sprint.
- **Livré** : EPIC-012 (US-061→066) 100% + US-069 ; sécurité durcie ; recette menée. **33 pts**, 424 tests verts.
- **Reporté** : US-070 (findings recette) → backlog S8.

---

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **ADR pour les décisions structurantes** : l'ADR-0019 a cadré et justifié le pivot Tailwind (poids, licence,
  un seul système) — décision tracée, réversibilité documentée.
- **Recette navigateur réelle** (Claude in Chrome) : a révélé des findings concrets impossibles à voir en tests
  unitaires (seed sans responsable de projet, valorisation vide, unité minutes).
- **Gate qualité systématique** : `make ci` rejoué à chaque commit (PHPStan max, Deptrac, tests, gitleaks) — 0 régression.
- **Traçabilité `.recette/`** : plans, rapports et captures versionnés — la recette est auditable.
- **Findings versés immédiatement au backlog** (US-069 traité, US-070 créée) — rien ne se perd.

### 🟡 Commencer
- **Corriger le build `make up`** : le `Dockerfile` doit lancer `tailwind:build --minify` **avant**
  `asset-map:compile` (F-INFRA-1) — le build de zéro est cassé depuis le pivot.
- **Seed démo représentatif** : profils + tarifs (valorisation démontrable), responsables de projet (validation) —
  le seed doit couvrir tous les parcours, y compris peuplés.
- **Procédure de reset propre de la base démo** (cible `make db-reset`) pour éviter l'accumulation de tenants.
- **Recette peuplée dès la livraison** (pas seulement à vide) — l'inscrire dans la DoD.

### 🔴 Arrêter
- **Laisser les cérémonies de clôture en dette** : la Review/Rétro S7 ont été faites après-coup — les tenir en fin de sprint.
- **Accumuler des seeds multi-tenants** sans reset : 3× `marc@demo.test` ont faussé la résolution du login en recette.
- **Documenter une mécanique sans resync** : `design-system.md` a dérivé (Skote/Bootstrap) longtemps après l'ADR-0019.

### ⬆️ Plus de
- **Revues de clôture** `symfony-reviewer` systématiques en fin de lot (88/100 sur le code S7).
- **Boucles de vérification** navigateur + captures pour les livrables front.

### ⬇️ Moins de
- **Pivots techno en cours de sprint** : justifié ici (licence Skote), mais coûteux (US-063/064 refaites) —
  à cadrer en affinage/amont plutôt qu'en plein sprint quand c'est possible.
- **Dépendance aux états de cache dev** : plusieurs blocages (cache dev périmé après pull, container debug pour PHPStan).

---

## Thèmes priorisés

### Thème 1 — Fiabilité de l'environnement de dev/démo ●●●●●
Le build `make up` cassé, le cache dev périmé (services obsolètes → `ArgumentCountError`), le pool de
connexions DB gardant l'ancien tenant après seed, et l'accumulation de seeds ont coûté du temps en recette.

### Thème 2 — Représentativité du jeu de démonstration ●●●●
Le seed ne rendait ni la validation (pas de responsable) ni la valorisation (pas de profils/tarifs)
démontrables → angles morts jamais recettés jusqu'ici.

### Thème 3 — Timing des cérémonies ●●
Review/Rétro tenues après la livraison plutôt qu'en clôture de sprint.

---

## Actions (Sprint 8)

### Action 1 — Réparer le build `make up` (F-INFRA-1)
| Attribut | Valeur |
|----------|--------|
| Description | `Dockerfile` : `php bin/console tailwind:build --minify` avant `asset-map:compile` (conforme ADR-0019) |
| Responsable | Dev |
| Deadline | Début Sprint 8 (avant tout dev front) |
| DoD | `make up` build de zéro sans erreur Tailwind ; CI verte |
| Priorité | **Haute** — porté par **US-070 / T-070-01** |

### Action 2 — Seed démo représentatif + reset propre
| Attribut | Valeur |
|----------|--------|
| Description | Seed : profils + tarifs (valorisation), responsables de projet (fait pour la validation) ; cible `make db-reset` (purge + migrate + seed unique) |
| Responsable | Dev |
| Deadline | Sprint 8 |
| DoD | `/valorisation` et `/validation` démontrables sur seed ; un seul tenant après reset |
| Priorité | **Haute** — porté par **US-070 / T-070-03** |

### Action 3 — DoD : recette peuplée + cérémonies en clôture
| Attribut | Valeur |
|----------|--------|
| Description | Ajouter à la DoD : « recette navigateur sur données peuplées » ; tenir Review + Rétro en fin de sprint |
| Responsable | Scrum Master / Dev |
| Deadline | Sprint 8 |
| DoD | DoD mise à jour ; Review/Rétro S8 datées de la clôture |
| Priorité | Moyenne |

## Suivi des actions précédentes

| Sprint | Action | Status |
|--------|--------|--------|
| S7 | (Première rétro formalisée du cycle design) | — |

## Check-out

- **ROTI** : 5/5 — la recette a transformé des angles morts en findings actionnables.
- Verbatim : « Le pivot Tailwind était le bon choix ; l'outillage de démo doit maintenant suivre. »
