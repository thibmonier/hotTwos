# Sprint 10 : Export comptable & consolidation qualité (EPIC-005 + dette)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 10 |
| Planifié le | 2026-09-05 |
| Début | 2026-12-22 *(provisoire)* |
| Fin | 2027-01-06 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ⚠️ **~15-18 points** — chevauchement fêtes de fin d'année (capacité réduite vs vélocité moyenne ~24) |
| Base git | `main` (après clôture S9 : review + rétro + réconciliation backlog) |
| EPIC | EPIC-005 (Finance & rentabilité, dernière tranche) + dette qualité (actions rétro S9) |

## Sprint Goal

> « Le tenant peut **exporter ses données comptables** (export configurable), livré sur une **base
> qualité assainie** : la **recette sur données peuplées** est enfin réalisée et la **couverture de
> tests est instrumentée et gardée en CI**. »

> **Positionnement** : dernière tranche fonctionnelle d'EPIC-005 (l'export clôt la chaîne temps →
> valorisation → marge → **restitution comptable**) + résorption de la dette rétro escaladée
> (recette peuplée reportée S7→S9, couverture non instrumentée relevée à l'audit).

## Definition of Done (rappel projet)

- [ ] Revue de clôture approuvée (`symfony-reviewer` par lot)
- [ ] Tests (couverture ≥ 80 % **désormais mesurée et enforçée en CI**), `make ci` vert (PHPStan max, Deptrac, gitleaks)
- [ ] Gating HAB-1 des données sensibles (export = coût/marge → habilitation)
- [ ] **Recette navigateur sur données peuplées** tracée dans `.recette/` (action rétro — cette fois tenue)
- [ ] Documentation & ADR si décision structurante ; déployable

## Sprint Backlog (candidat — à détailler via `/project:decompose-tasks 010`)

| Priorité | ID | Titre | Points | Statut |
|----------|-----|-------|--------|--------|
| 🔴 Must | US-074 | Export comptable configurable (EF-FIN-22) | 5 | 🟢 Ready (affinée S9) |
| 🔴 Must | QUAL-1 | Recette navigateur sur données peuplées (écrans finance) — action rétro escaladée | — (dette) | 🔵 À affiner |
| 🔴 Must | QUAL-2 | Instrumenter la couverture : pcov + seuil ≥ 80 % bloquant en CI | — (dette) | 🔵 À affiner |
| 🟡 Should | US-018 | Seuils d'alerte paramétrables par tenant (override du seuil de dérive US-072) | ? | 🔵 À affiner (backlog) |
| 🟢 Could | US-036 | Atterrissage & détection de dérive (charge) — complète la dérive financière S9 | ? | 🔵 À affiner (backlog) |

**Engagement pressenti (Must)** : US-074 (5 pts) + QUAL-1 + QUAL-2. US-018 en Should si la capacité (réduite par les fêtes) le permet ; US-036 en Could.

### Notes de préparation (Definition of Ready)
- **US-074** est la seule story **Ready** (affinée en réserve S9). Les autres nécessitent un **affinage** avant engagement (Planning P1/affinage) — elles sont aujourd'hui `backlog` dans `.bmad/sprint-status.yaml`.
- QUAL-1/QUAL-2 sont des **tâches de dette** (hors points) issues de la rétro S9 et de l'audit ; à décomposer comme tâches transverses.

## Dépendances

| Élément | Dépend de | Note |
|---------|-----------|------|
| US-074 | US-060 (valorisation), US-071 (marge), US-073 (consolidation) | ✅ tous livrés — l'export s'appuie sur les agrégats figés |
| QUAL-1 | Seed données peuplées (`app:demo:seed` / `make db-reset`) | Réutiliser le jeu de démo S8, l'étendre aux écrans finance |
| QUAL-2 | Image Docker app | Ajouter l'extension **pcov** au conteneur + option `--coverage` |
| US-018 | — | Modéliser un référentiel de seuils tenant (patron `ReminderRule`) |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Capacité réduite (fêtes) | **Forte** | Moyen | Engager conservateur (Must uniquement) ; US-018/036 en Should/Could |
| Recette peuplée à nouveau reportée (dette chronique) | Moyenne | Fort | **Action escaladée** : la traiter en **jour 1**, avant tout dev fonctionnel |
| Format d'export comptable (norme FEC ? CSV ? configurable) | Moyenne | Moyen | Décision PO au Planning P1 ; commencer par un export CSV configurable |
| Ajout pcov ralentit la CI | Faible | Faible | pcov léger ; mesurer l'impact, sinon coverage sur job dédié |

## Cérémonies

| Cérémonie | Timing |
|-----------|--------|
| Planning P1 (QUOI) / P2 (COMMENT) | Début S10 |
| Daily | Quotidien |
| Affinage | Début (US-074 déjà Ready ; affiner QUAL-1/2, US-018) |
| **Review + Rétro** | **Fin de sprint** (tenues, pas en dette) |

## Suivi des actions rétro S9 (à solder ce sprint)

| Action rétro S9 | Traitement S10 |
|-----------------|----------------|
| Recette navigateur données peuplées (escaladé) | → **QUAL-1 (Must, jour 1)** |
| Couverture pcov + seuil CI | → **QUAL-2 (Must)** |
| Profilage perf `/finance` (ENF-PERF-3) | À caser (Could) ou avec QUAL-1 sur données peuplées |
| MAILER_DSN staging + e2e reset | Reporté (dépend creds SMTP staging — hors contrôle dev) |
| Allowlist gitleaks (exemples de clés docs) | Quick-win à caser (Could) |

## Notes

Sprint plus court en capacité effective (fêtes) et volontairement orienté **clôture EPIC-005 + qualité**.
Le module de **facturation** (« facturé réel » qui superséderait le proxy CA reconnu d'ADR-0020) reste une
tranche EPIC-005 **ultérieure** (hors S10). Prochaine étape : `/project:decompose-tasks 010` après
validation du Sprint Goal et affinage des candidates non-Ready.
