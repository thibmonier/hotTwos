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
GIVEN j'affiche la vue semaine de saisie
WHEN je lis l'en-tête de la période
THEN il présente un libellé humain (ex. « Semaine du 10 au 16 août 2026 »)
  AND non un format technique (« Sem. 2026-08-10 »)
```

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
| T-069-01 | [FE-WEB] | Corriger le `match` de nav (« Saisie » ne capture plus `timesheet_validation`) | 🔴 | 1h |
| T-069-02 | [FE-WEB] | Libellés d'en-tête de semaine humanisés (vue semaine + jour) | 🔴 | 2h |
| T-069-03 | [TEST] | Passe recette complémentaire valorisation/validation sur données peuplées | 🔴 | 2h |

## Progression

0/3 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Un seul item de menu actif sur `/validation` (test de vue)
- [ ] En-têtes de semaine humanisés (saisie semaine et jour)
- [ ] Passe recette complémentaire sur données peuplées tracée dans `.recette/`
- [ ] `make ci` vert

---

## Notes

Irritants **mineurs** issus de la recette US-066 (Sprint 7), priorisés « mineur » (non bloquants pour
la clôture d'EPIC-012). Regroupés dans une US unique pour un correctif efficace, conformément à la CA-3
de US-066. Aucune fonctionnalité nouvelle : confort de navigation et de lecture uniquement.
