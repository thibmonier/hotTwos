# US-021: Calendriers de travail différenciés (temps partiel)

## Métadonnées
- **ID**: US-021
- **EPIC**: EPIC-001
- **Sprint**: 17
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P-ADMIN (administrateur) / P3 (Resource Manager, lecture capacité)
- **Créé le**: 2026-09-07
- **Mis à jour**: 2026-09-07 (affinage S17 — recadrage sur le temps partiel par collaborateur)

## Traçabilité
- **Implémente**: EF-REF-7 (calendriers différenciés — **tranche : temps partiel par collaborateur**)
- **Dépend de**: US-012 (`WorkingDaysCalculator`, fériés), US-022 (fermetures — jours non ouvrés tenant)
- **Réutilise / ne re-spécifie pas** : fériés (US-012), fermetures (US-022), absences (US-054).
- **Reporté (hors périmètre, US ultérieures)** : calendriers par **entité/pays**, **forfait-jours** (unité jours vs heures). Cette US couvre le cas majoritaire (temps partiel individuel) avec une résolution extensible.

## User Story

**En tant qu'** administrateur tenant,
**je veux** définir le **régime de travail d'un collaborateur** (jours travaillés dans la semaine, pour un temps partiel),
**afin que** sa capacité productive et son taux d'occupation reflètent ses jours réellement travaillés (et non un temps plein par défaut).

## Contexte (Conversation)
Aujourd'hui, `Domain\Calendar\WorkingDaysCalculator` calcule les jours ouvrés au **niveau tenant**
(week-end + fériés US-012 + fermetures US-022). Cette US introduit un **régime par collaborateur**
(sous-ensemble des jours ouvrés de la semaine) et une **résolution collaborateur > tenant** : sans
régime défini, le collaborateur est à temps plein (Lun-Ven). Les indicateurs par collaborateur
(`OccupationReport`, `CompletenessGrid`, `ActivitySummary`) basculent sur la variante **par utilisateur**.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : un collaborateur à 80 % (Lun-Jeu) a 4 jours ouvrés/semaine
```gherkin
GIVEN un collaborateur dont le régime déclare les jours travaillés Lundi à Jeudi (vendredi non travaillé)
WHEN le système calcule ses jours ouvrés sur une semaine complète sans férié ni fermeture
THEN le total est 4 (le vendredi n'est pas un jour ouvré pour lui)
  AND le vendredi n'est pas compté comme absence
```

### CA-2 (Nominal) : l'occupation utilise la base du régime, pas 5 jours
```gherkin
GIVEN un collaborateur à temps partiel (4 jours/semaine) sans absence
WHEN le tableau d'occupation calcule sa capacité sur le mois
THEN la base de capacité correspond à ses jours travaillés (nets de fériés et fermetures)
  AND son taux d'occupation n'est pas dilué par un 5e jour non travaillé
```

### CA-3 (Alternatif) : l'administrateur définit le régime d'un collaborateur
```gherkin
GIVEN un administrateur (MANAGE_ORGANIZATION) sur la page des régimes de travail
WHEN il enregistre pour un collaborateur les jours travaillés (ex. Lun, Mar, Mer, Jeu)
THEN le régime est associé au collaborateur et pris en compte dans les calculs de capacité
  AND il peut le modifier ou le retirer (retour au temps plein par défaut)
```

### CA-4 (Alternatif) : sans régime, le collaborateur est à temps plein ; fériés/fermetures s'appliquent
```gherkin
GIVEN un collaborateur sans régime déclaré
WHEN on calcule ses jours ouvrés sur une semaine contenant un férié
THEN il est traité à temps plein (Lun-Ven) moins le férié (et moins toute fermeture)
```

### CA-5 (Erreur) : régime sans aucun jour travaillé refusé
```gherkin
GIVEN l'administrateur enregistre un régime de travail
WHEN aucun jour travaillé n'est sélectionné (ou un jour hors Lun-Ven)
THEN l'enregistrement est refusé avec un message explicite
  AND aucun régime invalide n'est enregistré
```

### CA-6 (Erreur) : accès refusé sans habilitation
```gherkin
GIVEN un utilisateur sans MANAGE_ORGANIZATION
WHEN il tente d'accéder à la page des régimes de travail (GET ou POST)
THEN l'accès est refusé (403)
```

## Notes techniques (pour la décomposition)
- **Entité** `Domain\Calendar\WorkSchedule` (`TenantOwned`) : `userId`, `workingWeekdays` (JSON, sous-ensemble ISO 1..5) ; **unicité (tenant, user)** ; garde : au moins un jour, tous dans 1..5 ; RLS + migration.
- **Port** `WorkScheduleRepository` : `save`, `find(tenant, userId)`, `findForTenant`, `delete`.
- **`WorkingDaysCalculator`** — ajout **rétrocompatible** de variantes par utilisateur :
  - `isWorkingDayForUser(tenant, userId, day)` = `isWorkingDay(tenant, day)` (week-end+férié+fermeture) **ET** jour ∈ régime du collaborateur (ou temps plein si aucun régime).
  - `workingDaysForUser(tenant, userId, from, to)`.
  - La méthode tenant `isWorkingDay()` **reste inchangée** (utilisée par `ScheduleReminders` — plancher anti-spam global).
- **Consommateurs à basculer sur la variante par utilisateur** : `OccupationReport` (capacité), `CompletenessGrid` (jours attendus), `ActivitySummary` (temps ouvré attendu). `ScheduleReminders` **inchangé**.
- **UI** : page `/parametrage/regimes-travail` (Twig, gating `MANAGE_ORGANIZATION`, CSRF) — collaborateur + cases jours travaillés.
- **Tests** : unit calculateur (régime, repli temps plein, combinaison férié/fermeture) ; fonctionnels CRUD + 403 + impact occupation/complétude ; **ajouter `WorkSchedule::class` aux SchemaTool** des tests par utilisateur.
- **Découpe possible si dérapage** : livrer d'abord le calcul + l'impact occupation (cœur), l'UI ensuite.

## Definition of Ready
- [x] Description INVEST recadrée (temps partiel par collaborateur ; entité/pays/forfait reportés)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Modèle de résolution arrêté (collaborateur > tenant) ; impact `WorkingDaysCalculator`/occupation cadré (variantes par utilisateur, tenant-level inchangé)
- [x] Estimation 8 pts ; RLS/gating explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] Migration + RLS ; `ScheduleReminders` non impacté ; code review
