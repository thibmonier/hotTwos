# US-070: Suites de la recette US-069 (findings à traiter)

## Métadonnées
- **ID**: US-070
- **EPIC**: EPIC-012 (contexte recette) — **transverse** (touche aussi EPIC-005 finance, EPIC-011 industrialisation)
- **Sprint**: À planifier (backlog)
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: P2 Marc (manager — valorisation/validation), équipe de dev/OPS
- **Créé le**: 2026-09-03
- **Mis à jour**: 2026-09-03

## Traçabilité
- **Source**: recette peuplée US-069 / T-069-03 — `.recette/reports/REC-20260903-us069-report.md`
- **Dépend de**: US-069 (correctifs mergés), ADR-0019 (Tailwind v4)

## User Story

**En tant que** équipe produit et technique,
**Je veux** traiter les anomalies révélées par la recette peuplée d'US-069,
**Afin de** rendre la valorisation démontrable, cohérente en unités, et fiabiliser le build.

## Critères d'acceptation

### CA-1 (F-INFRA-1) : `make up` build à nouveau

```gherkin
GIVEN le Dockerfile buildé de zéro (sans var/tailwind préexistant)
WHEN l'image est construite (`make up`)
THEN `php bin/console tailwind:build --minify` est exécuté AVANT `asset-map:compile`
  AND le build aboutit sans « Built Tailwind CSS file does not exist » (conforme ADR-0019)
```

### CA-2 (F2) : Valorisation démontrable sur le seed démo

```gherkin
GIVEN le jeu de démonstration (`app:demo:seed`)
WHEN un manager ouvre /valorisation
THEN des profils et des tarifs existent (profile / profile_rate peuplés)
  AND le CA reconnu et le compteur « X / Y imputations valorisées » sont non nuls
  AND l'écran illustre une valorisation réelle (plus de « 0,00 € / 0 / 0 »)
```

### CA-3 (F3) : Unité cohérente sur /validation

```gherkin
GIVEN l'écran /validation qui liste les imputations en attente
WHEN le manager lit la durée d'une imputation
THEN elle est présentée dans la même unité que la saisie (heures, ex. « 7h00 » ou décimal)
  AND non en minutes brutes (« 420 ») incohérentes avec l'écran de saisie
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| T-070-01 | [OPS] | `Dockerfile` : `tailwind:build --minify` avant `asset-map:compile` (débloque `make up`) | 🔴 | 1h |
| T-070-02 | [FE-WEB] | `/validation` : afficher la durée en heures (cohérence avec la saisie décimale) | 🔴 | 2h |
| T-070-03 | [DB] | `SeedDemoDataCommand` : peupler profils + tarifs + assignations pour rendre la valorisation démontrable | 🔴 | 4h |

## Progression

0/3 tasks complétées (0%)

## Definition of Done

- [ ] `make up` build de zéro sans erreur Tailwind (CA-1)
- [ ] /valorisation non nulle sur le seed démo (CA-2)
- [ ] /validation en unité horaire cohérente avec la saisie (CA-3)
- [ ] `make ci` vert ; passe de recette de contrôle tracée dans `.recette/`

---

## Notes

Findings **non bloquants** issus de la recette peuplée US-069 (Sprint 7), priorisés « mineur » à « moyen ».
F2 (valorisation) est le plus conséquent (modèle finance). F-INFRA-1 est un correctif d'infrastructure
simple mais à traiter en priorité (le build `make up` de zéro est cassé — contourné en recette en démarrant
l'image existante + `make tailwind`). Détail et preuves : `.recette/reports/REC-20260903-us069-report.md`.
