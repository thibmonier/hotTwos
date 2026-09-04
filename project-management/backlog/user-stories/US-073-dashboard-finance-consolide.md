# US-073: Tableau de bord finance consolidé (direction)

## Métadonnées
- **ID**: US-073
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 9
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: P6 (Directeur financier / contrôleur de gestion), P7 (Dirigeant)
- **Créé le**: 2026-09-04
- **Mis à jour**: 2026-09-04

## Traçabilité
- **Implémente**: EF-FIN (vue consolidée multi-projets / multi-clients), OBJ-3 (reporting financier automatisé — éliminer la réconciliation manuelle mensuelle), ENF-PERF-3 (tableau de bord finance < 3 s P95 sur 5 ans d'historique), HAB-1 (coûts unitaires visibles seulement finance/direction)
- **Dépend de**: US-071 (marge réelle par projet), US-072 (budget vs réalisé), US-014 (clients/contacts — dimension client)
- **Dépendant**: EPIC-007 (Pilotage) — réutilisera les indicateurs financiers consolidés

## User Story

**En tant que** directeur financier (P6) et dirigeant (P7),
**je veux** un **tableau de bord consolidé** de la rentabilité, **par projet et par client**, agrégeant CA reconnu, coût, marge et dérives,
**afin de** piloter la rentabilité globale de l'agence en un coup d'œil, sans réconciliation manuelle mensuelle.

## Critères d'Acceptation

### CA-1 (Nominal) : Consolidation multi-projets et multi-clients

```gherkin
GIVEN plusieurs projets valorisés et clôturés sur la période, rattachés à différents clients (US-014)
WHEN le directeur financier ouvre le tableau de bord finance consolidé pour la période
THEN il voit les totaux tenant : CA reconnu, coût valorisé, marge, taux de marge
  AND une ventilation par client (CA/coût/marge par client, triable)
  AND une ventilation par projet (réutilise la ventilation par projet du Sprint 8)
  AND le nombre de projets en dérive financière (US-072) est mis en avant
```

### CA-2 (Non-fonctionnel) : Performance sur historique

```gherkin
GIVEN un tenant disposant de 5 ans d'historique de valorisation
WHEN le tableau de bord finance consolidé est chargé pour une période ou une plage
THEN la page répond en < 3 s au P95 (ENF-PERF-3)
  AND l'agrégation s'appuie sur les faits pré-agrégés (`fact_project_revenue`) et les index existants (T-060-09)
  AND aucun calcul de marge n'est refait côté front (ARC-6 — valeurs fournies par le backend)
```

### CA-3 (Habilitation) : Réservé finance/direction (HAB-1)

```gherkin
GIVEN un utilisateur sans rôle finance/direction (ni VIEW_PROJECT_FINANCIALS ni VIEW_COLLABORATOR_COST)
WHEN il tente d'accéder au tableau de bord finance consolidé
THEN l'accès est refusé (deny-by-default) avec une page 403 habillée
  AND un porteur de VIEW_PROJECT_FINANCIALS voit le CA consolidé mais pas le coût/marge (réservés VIEW_COLLABORATOR_COST)
  AND la lecture des données de coût/marge consolidées est tracée (HAB-6)
```

### CA-4 (Alternatif) : Sélection de période et filtre client

```gherkin
GIVEN le tableau de bord finance consolidé est affiché
WHEN l'utilisateur sélectionne une période (mois clôturé) ou filtre sur un client
THEN les totaux et ventilations sont recalculés côté backend pour le périmètre choisi
  AND les périodes non clôturées sont exclues ou clairement marquées "provisoire"
```

## Critères UI/UX

### Web
- Cohérence visuelle avec `/valorisation` (KPI vedette CA, gating coût, tabular-nums, thème Tailwind).
- Tableaux de ventilation (client, projet) avec tri et défilement horizontal contenu (overflow-x auto).
- Indicateur clair « données figées (clôturé) » vs « provisoire (période ouverte) ».

### Mobile
- Vue consolidée simplifiée : totaux tenant (CA, marge) + top clients ; détail par projet réservé au desktop.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | À décomposer (`/project:decompose-tasks 009`) | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Perf < 3 s P95 vérifiée (jeu de données d'historique) — ENF-PERF-3
- [ ] Gating HAB-1 (accès + coût/marge) · a11y (US-065)
- [ ] `make ci` vert · revue de clôture · recette navigateur sur données peuplées

---

## Notes

**Réutilisation Sprint 8/9** : ce tableau de bord agrège la marge d'US-071 et les dérives d'US-072, et réutilise la **ventilation par projet** et `fact_project_revenue` (Sprint 8). L'ajout majeur est la **dimension client** (US-014) et la **consolidation tenant**.

**Perf** : privilégier des requêtes agrégées sur `fact_project_revenue` (déjà au grain tenant/période/projet) plutôt que le rejeu des snapshots ; profiter de l'index `idx_time_entry_project` (T-060-09). Si nécessaire, envisager une projection dédiée « fact_project_margin » (décision à acter — ne pas dupliquer le moteur de marge, ARC-6).
