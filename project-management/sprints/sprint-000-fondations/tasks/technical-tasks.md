# Tâches Techniques Transverses — Sprint 0

Tâches d'amorçage qui ne relèvent pas d'une US précise mais conditionnent le sprint.

## Dépôt & organisation

### T-TECH-01 : Initialisation du dépôt et stratégie de branches
- **Type** : `[OPS]` · **Estimation** : 1h
- Dépôt Git initialisé, branche `main` protégée, convention de branches (`feature/`, `fix/`…) et Conventional Commits documentés (cf. `.claude/rules/09-git-workflow.md`).
- **Critères** : `main` protégée ; template de PR en place ; commitlint configuré (peut être branché en CI par US-004).

### T-TECH-02 : README racine du projet
- **Type** : `[DOC]` · **Estimation** : 1h
- Prérequis, installation, démarrage (`make up`), tests, structure — pointant vers `tasks/README.md` et `tech-spec.md`.
- **Critères** : un nouvel arrivant démarre le projet en suivant le README seul.

## Décisions à fermer pendant le sprint

### T-TECH-03 : Fermer les arbitrages infra ouverts
- **Type** : `[OPS]` · **Estimation** : 2h
- Instruire `ARB-25` (hébergement prod UE — décision distincte, lot 2) et `ARB-3`/`CTR-5` (fournisseur d'inférence IA UE). Produire un `ADR-0017` si une décision est prise.
- **Critères** : chaque arbitrage a un statut (décidé / reporté avec échéance).

## Rappel — livrables Lot 0 NON couverts par ce sprint (hors backlog dev)

Ces éléments avancent **en parallèle**, hors décomposition de tâches de dev :

- `AUD-1` / `AUD-2` — audit technique et cartographie fonctionnelle de l'existant (**peut réviser le scénario C**, `CDR-5`).
- `AUD-3` / `ARB-2` — mesure des situations de référence `OBJ-1..7` (relevé 4 semaines).
- `ENF-RGPD-5` — AIPD (prérequis bloquant aux modules RH et à `EF-TMP-10`).
- `CTR-3` / `ARB-14` — qualification juridique AI Act.
- Design system (`cdc/11`) — livrable UX/UI distinct.
- Vérification de la licence du thème Skote pour usage SaaS (`existing-assets.md`).
