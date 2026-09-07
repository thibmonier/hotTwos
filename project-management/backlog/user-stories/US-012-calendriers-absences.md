# US-012: Jours fériés & calcul unifié des jours ouvrés

## Métadonnées
- **ID**: US-012
- **EPIC**: EPIC-001
- **Sprint**: 16
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P-ADMIN (administrateur tenant)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-07 (affinage S16 — recadrage sur l'existant)

## Traçabilité
- **Implémente**: EF-REF-6 (calendrier tenant : jours ouvrés & fériés), RG-REF-1 (immuabilité des références utilisées)
- **Dépend de**: US-001 (multi-tenant), US-010 (structure organisationnelle)
- **Réutilise / ne re-spécifie pas** : les **absences** (types, demandes, validation, impact capacité) sont **déjà livrées** — US-054 (`Domain\Absence\*`, `OccupationReport`). Cette US ne touche pas aux absences.
- **Reporté (hors périmètre, US ultérieures)** :
  - **EF-REF-7** — calendriers **différenciés** par entité/pays et par collaborateur (temps partiel, forfait jours). Nécessite un rattachement pays/région (absent d'`OrgUnit`) → US dédiée.
  - **EF-REF-9** — période de **fermeture entreprise** globale → US dédiée (s'appuiera sur le référentiel de fériés livré ici).

## User Story

**En tant qu'** administrateur tenant,
**je veux** déclarer les **jours fériés** de mon organisation et disposer d'un **calcul unifié des jours ouvrés** qui les prend en compte,
**afin que** la capacité productive, l'occupation et la complétude reflètent fidèlement les jours réellement travaillés (et non seulement « hors week-end »).

## Contexte (Conversation)

Aujourd'hui, le « nombre de jours ouvrés » est recalculé **en 4 endroits** par simple exclusion du week-end
(`format('N') ≤ 5`), **sans aucun jour férié** :
`Application\Valuation\OccupationReport`, `Application\Completeness\CompletenessGrid`,
`Application\Activity\ActivitySummary`, `Application\Reminder\ScheduleReminders`
(ce dernier annonce déjà en commentaire « raffinement jours fériés ultérieur »).

Cette US livre le socle **EF-REF-6** : un référentiel de fériés par tenant + un **service unique**
`WorkingDaysCalculator` (Domaine) qui centralise l'exclusion week-end **et** fériés, puis branche ce
service aux calculs de capacité/occupation/complétude (DRY). La formule d'occupation existante
(`capacité = jours ouvrés − absences validées`, US-060) est **conservée**, les jours ouvrés étant
désormais **nets des fériés**.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : un jour férié est exclu des jours ouvrés
```gherkin
GIVEN le tenant a déclaré le 14/07/2027 (mardi) comme jour férié
WHEN le système calcule les jours ouvrés du mois de juillet 2027
THEN le total est égal au nombre de jours hors week-ends MOINS 1 (le 14/07)
  AND le 14/07 n'est jamais compté comme jour ouvré, quel que soit l'écran (occupation, complétude, activité)
```

### CA-2 (Nominal) : le férié réduit la capacité d'occupation
```gherkin
GIVEN un collaborateur sans absence sur une semaine contenant un jour férié tenant
WHEN le tableau d'occupation calcule sa capacité pour cette semaine
THEN la capacité = (jours ouvrés de la semaine − 1 férié) − absences validées
  AND le taux d'occupation utilise cette base nette de fériés
```

### CA-3 (Alternatif) : l'administrateur gère les jours fériés
```gherkin
GIVEN un administrateur (permission MANAGE_ORGANIZATION) sur la page de paramétrage des jours fériés
WHEN il ajoute un jour férié (date + libellé) puis consulte la liste
THEN le jour férié apparaît dans la liste triée par date
  AND il peut le supprimer ; après suppression, il n'impacte plus le calcul des jours ouvrés futurs
```

### CA-4 (Alternatif) : isolation multi-tenant des fériés
```gherkin
GIVEN le tenant A a déclaré le 02/06/2027 comme férié et le tenant B ne l'a pas déclaré
WHEN on calcule les jours ouvrés de juin 2027 pour chacun
THEN le 02/06 est exclu pour A mais compté comme ouvré pour B
  AND aucun tenant ne voit ni n'utilise les fériés d'un autre (RLS)
```

### CA-5 (Erreur) : date de férié en doublon refusée
```gherkin
GIVEN le 25/12/2027 est déjà déclaré comme férié pour le tenant
WHEN l'administrateur tente d'ajouter à nouveau le 25/12/2027
THEN l'ajout est refusé avec un message explicite (« Ce jour férié existe déjà »)
  AND aucun doublon n'est créé (unicité tenant + date)
```

### CA-6 (Erreur) : accès refusé sans habilitation
```gherkin
GIVEN un utilisateur sans la permission MANAGE_ORGANIZATION
WHEN il tente d'accéder à la page de paramétrage des jours fériés (GET ou POST)
THEN l'accès est refusé (403) et aucune modification n'est possible
```

## Notes techniques (pour la décomposition)
- **Nouvelle entité** `Domain\Calendar\Holiday` (`TenantOwned`) : `tenantId`, `date` (date), `label` ;
  **unicité `(tenant_id, date)`** ; migration + **policy RLS** (pattern standard).
- **Service Domaine** `WorkingDaysCalculator` : `workingDaysBetween(from, to): int` et `isWorkingDay(day): bool`,
  excluant week-ends **et** fériés du tenant (les fériés injectés via un port `HolidayRepository`).
  **Remplacer** les 4 implémentations inline (Occupation, Completeness, Activity, Reminder) → DRY.
- **Immuabilité (RG-REF-1)** : supprimer un férié n'altère pas les calculs figés/historisés (les valorisations et marges déjà figées ne sont pas recalculées).
- **UI** : page admin `/parametrage/jours-feries` (Twig, gating `MANAGE_ORGANIZATION`, CSRF), liste + ajout + suppression.
- **Tests** : unit `WorkingDaysCalculator` (week-end + fériés + isolation) ; fonctionnels page admin (CRUD + 403) ; **ajouter `Holiday::class` aux SchemaTool** des tests fonctionnels touchant occupation/complétude.
- **Deptrac** : le port `HolidayRepository` dans le Domaine ; l'UI ne dépend pas de l'Infra.

## Definition of Ready
- [x] Description claire (INVEST) recadrée sur l'existant (absences non re-spécifiées)
- [x] Critères Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Estimation confirmée : 5 pts (fériés tenant + centralisation ; différenciés/fermeture reportés)
- [x] Dépendances identifiées (US-001/010) ; réutilise US-054/US-060
- [x] Impact multi-tenant/RLS et gating (MANAGE_ORGANIZATION) explicités

## Definition of Done
- [ ] Tous les CA validés (tests unit + fonctionnels)
- [ ] `WorkingDaysCalculator` unique ; 4 duplications supprimées
- [ ] Migration + RLS pour `holiday`
- [ ] `make ci` vert (PHPStan max, Deptrac, couverture ≥ 80 %)
- [ ] Code review

---

## Notes
Périmètre volontairement resserré à **EF-REF-6** (jours fériés tenant + calcul unifié) pour tenir dans
5 pts et livrer une valeur immédiate (fériés partout). Les **calendriers différenciés** (EF-REF-7 :
temps partiel, forfait jours, par pays/entité) et la **fermeture entreprise** (EF-REF-9) feront l'objet
d'US dédiées, en s'appuyant sur ce socle. La hiérarchie de résolution collaborateur > entité > pays >
tenant reste la cible à terme.
