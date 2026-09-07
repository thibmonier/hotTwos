# Tâches - US-023 : Raffinements Review (fériés mobiles & audit étendu)

## Informations US
- **Epic** : EPIC-001 (finitions) · **Persona** : P-ADMIN · **Points** : 5 · **Sprint** : sprint-018 · **Ordre** : #2

## Résumé
Provisionner les fériés mobiles à l'onboarding + étendre l'audit aux paramètres org & profils/taux.

## Vue d'ensemble des tâches

| ID | Type | Tâche | Est. | Dépend | Statut |
|----|------|-------|------|--------|--------|
| T-023-01 | [BE] | Fonction pure de calcul de Pâques (Butcher) + dérivés (Lundi Pâques, Ascension, Pentecôte) | 2h | - | 🔲 |
| T-023-02 | [BE] | Ajouter les fériés mobiles à `InitializeTenantDefaults` (idempotent) | 1.5h | 01 | 🔲 |
| T-023-03 | [BE] | Instrumenter `ConfigAuditRecorder` sur écritures OrgUnit + Profile/ProfileRate | 2.5h | - | 🔲 |
| T-023-04 | [TEST] | Unit (calcul de Pâques 2–3 années) + Functional (onboarding mobiles, audit org/profils, 403) | 3h | 02,03 | 🔲 |
| T-023-05 | [DOC]+[REV] | PHPDoc + review + `make ci` | 1h | 04 | 🔲 |

**Total estimé** : ~10h

## Détails clés
- **T-023-01** : `Domain\Calendar\EasterCalculator` (ou fonction statique) — algorithme de Butcher (grégorien), pur/testable. Dérivés : Lundi de Pâques (+1), Ascension (+39), Lundi de Pentecôte (+50).
- **T-023-02** : dans `InitializeTenantDefaults::defaultHolidays()`, fusionner fixes + mobiles de l'année ; idempotence déjà via `existsForDate`.
- **T-023-03** : appeler `record()` depuis les use cases/contrôleurs org (OrgUnit create/rename/deactivate) et pricing (Profile/ProfileRate). objectType « Organisation » / « Profil ».
- **T-023-04** : **ajouter les entités concernées aux SchemaTool** (ou utiliser le trait QUAL-3 livré avant).

## Résumé
| Couche | Tâches | Heures |
|--------|--------|--------|
| [BE] | 3 | 6h |
| [TEST] | 1 | 3h |
| [DOC]/[REV] | 1 | 1h |
| **TOTAL** | **5** | **~10h** |
