# US-069: Correctifs d'ergonomie mineurs (suites recette US-066)

## Métadonnées
- **ID**: US-069
- **EPIC**: EPIC-012
- **Sprint**: À planifier (backlog — irritants mineurs)
- **Statut**: 🔴 To Do
- **Points**: 2
- **Persona**: P1 (collaborateur), P3 (manager/validation) — confort de navigation et de lecture
- **Créé le**: 2026-09-03
- **Mis à jour**: 2026-09-03

## Traçabilité
- **Implémente**: EPIC-012 (D6 — ergonomie définitive), suites de la recette US-066 (CA-3 : « les mineurs sont versés au backlog »)
- **Dépend de**: US-066 (recette menée), design refresh lot 2 (PR #17)
- **Source**: irritants recette S7 priorisés « mineur » — registre `.recette/`

## User Story

**En tant que** utilisateur de l'application (saisie et validation),
**Je veux** une navigation et des libellés sans ambiguïté,
**Afin de** me repérer sans effort et lire les périodes de façon naturelle.

## Critères d'acceptation

### CA-1 (Nominal) : Un seul item de menu actif sur `/validation`

```gherkin
GIVEN je suis sur la page de validation (route `timesheet_validation`)
WHEN j'observe la barre de navigation latérale
THEN seul l'item « Validation » est marqué actif
  AND l'item « Saisie » n'est plus surligné simultanément
```

> **Cause identifiée** : dans `templates/base.html.twig`, l'item « Saisie » utilise
> `match: 'timesheet_'` (préfixe) qui capture aussi `timesheet_validation`. Restreindre le match
> (préfixe exclusif ou liste de routes) pour éviter le double actif.

### CA-2 (Nominal) : En-têtes de semaine lisibles

```gherkin
GIVEN j'affiche la grille de complétude (en-têtes de colonnes par semaine)
WHEN je lis un en-tête de semaine
THEN il présente un libellé humain (n° de semaine ISO + date de début courte, date complète en `title`)
  AND non un format technique (« Sem. 2026-08-10 »)
```

> **Note :** l'irritant portait sur la **grille de complétude** (`completeness/index.html.twig`), où la clé
> `week` (date ISO) était affichée telle quelle. La vue semaine de saisie affichait déjà un libellé humain.

### CA-3 (Alternatif) : Recette complémentaire sur données peuplées

```gherkin
GIVEN les parcours valorisation et validation n'ont été rejoués qu'à vide en recette S7
WHEN une passe complémentaire est menée sur un jeu de données peuplé (seed enrichi)
THEN les états peuplés (lignes, totaux, statuts) sont vérifiés et tracés
  AND tout nouvel irritant est priorisé et rattaché à l'écran concerné
```

## Critères UI/UX

### Web
- Navigation : état actif exclusif et cohérent sur toutes les routes de saisie/validation.
- Libellés de période lisibles par un utilisateur non technique.

### Mobile
- Si la nav mobile réutilise le même helper `match`, appliquer le même correctif.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| T-069-01 | [FE-WEB] | Corriger le `match` de nav (« Saisie » ne capture plus `timesheet_validation`) | ✅ | 1h |
| T-069-02 | [FE-WEB] | Libellés d'en-tête de semaine humanisés (grille de complétude : n° ISO + date courte) | ✅ | 2h |
| T-069-03 | [TEST] | Passe recette complémentaire valorisation/validation sur données peuplées | ✅ | 2h |
| T-069-04 | [FE-WEB] | `aria-modal="true"` sur `dialog.summary-dialog` (`timesheet/week.html.twig`) — a11y WCAG AA | ✅ | 0.5h |
| T-069-05 | [BE] | `ValidationPageController` : libellé de repli + log si un projet disparaît entre deux requêtes (au lieu de l'UUID brut) | ✅ | 1h |

## Progression

5/5 tasks complétées (100%). Recette peuplée menée (REC-20260903-us069) : T-01/02/04/05 confirmés.
**Findings à backloguer** : F2 valorisation non démontrable (seed sans profils/tarifs), F3 unité minutes
sur `/validation` vs heures décimales en saisie, F-INFRA-1 `Dockerfile` sans `tailwind:build` avant
`asset-map:compile` (build `make up` cassé). Voir `.recette/reports/REC-20260903-us069-report.md`.

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Un seul item de menu actif sur `/validation` (test de vue)
- [ ] En-têtes de semaine humanisés (saisie semaine et jour)
- [ ] Passe recette complémentaire sur données peuplées tracée dans `.recette/`
- [ ] `aria-modal="true"` sur la dialog de synthèse (a11y)
- [ ] Repli lisible + log si projet disparu (plus d'UUID brut affiché)
- [ ] `make ci` vert

---

## Notes

Irritants **mineurs** issus de la recette US-066 (Sprint 7), priorisés « mineur » (non bloquants pour
la clôture d'EPIC-012). Regroupés dans une US unique pour un correctif efficace, conformément à la CA-3
de US-066. Aucune fonctionnalité nouvelle : confort de navigation et de lecture uniquement.

Les tâches **T-069-04** (`aria-modal`) et **T-069-05** (repli projet disparu) proviennent de la **revue de
clôture Sprint 7** (`symfony-reviewer`, score 88/100) : seuls irritants réels retenus après tri (les autres
findings — arrondi JS sur entier de minutes, assertion anti-fuite de slug, `tabular-nums` natif Tailwind —
étaient des faux positifs).
