# Tâches techniques transverses — Sprint 6

## T-TECH-06-01 : Évolution du modèle `Project` sans régression
- **Type** : [BE] · **Est.** : inclus dans T-030-01/05
- **Raison** : l'entité `Project` minimale devient un agrégat (client, budget, statut). **Adapter tous
  les appelants** : `TimesheetPageController`, `TimesheetDayController`, `EnsureAbsenceProject`,
  `ActivitySummary`, `SeedDemoDataCommand`, et les tests fonctionnels de saisie (piège #5 — ajouter les
  nouvelles entités au `$schema`). Valeurs par défaut de rétro-compatibilité pour le projet système
  « Absence ».
- **DoD** : `make ci` vert après l'évolution ; aucun test de saisie existant cassé.

## T-TECH-06-02 : Chaîne de gardes d'imputation cohérente
- **Type** : [BE] · **Est.** : inclus dans T-030-05 / T-037-04 / T-038-04
- **Raison** : `RecordTimeEntry`/`RecordWeek` accumulent des gardes (période clôturée US-057, absence
  US-054, statut projet US-030, affectation US-037, clôture projet US-038). **Ordonner** et **messages
  clairs** ; éviter la duplication (extraire une politique si nécessaire — KISS/DRY).
- **DoD** : ordre déterministe testé ; un seul point de vérité par règle.

## T-TECH-06-03 : Doc module `docs/modules/project.md`
- **Type** : [DOC]
- **Raison** : documenter l'agrégat, le cycle de vie, la structure lots/jalons, l'affectation, la
  clôture, et **toutes les dégradations** (facturation, budget vente, plan de charge, reporting).
- **DoD** : doc + sections « Revue » par US (findings traités).

## Notes
- **Dette rétro S5 (hors périmètre chiffré)** : livraison effective des notifications — non incluse
  dans ce sprint (priorité donnée à l'ouverture d'EPIC-002) ; reste au backlog des actions S6.
