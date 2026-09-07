# US-023: Raffinements Review — fériés mobiles & audit étendu

## Métadonnées
- **ID**: US-023
- **EPIC**: EPIC-001 (finitions)
- **Sprint**: 18
- **Statut**: 🔵 Backlog (à affiner)
- **Points**: 5 (estimation)
- **Persona**: P-ADMIN
- **Créé le**: 2026-09-07

## Traçabilité
- **Origine**: arbitrages Sprint Review S16/S17 (approuvés PO)
- **Étend**: US-019 (onboarding, EF-REF-6), US-020 (audit, EF-REF-33)

## User Story (esquisse — à affiner)

**En tant qu'** administrateur, **je veux** que l'onboarding provisionne aussi les **fériés mobiles**
(Pâques, Ascension, Pentecôte) et que le **journal d'audit** couvre les paramètres d'**organisation** et
de **profils/taux**, **afin d'** avoir des défauts complets et une traçabilité étendue.

## Notes de cadrage (pour l'affinage)
- **Fériés mobiles** : calcul du dimanche de Pâques (algorithme de Gauss/Butcher) → Lundi de Pâques,
  Ascension (+39 j), Pentecôte/Lundi (+50 j) ; ajoutés dans `InitializeTenantDefaults` (US-019).
- **Audit étendu** : instrumenter `ConfigAuditRecorder` sur les écritures org (OrgUnit) et profils/taux
  (Profile/ProfileRate) — même pattern qu'US-020 (seuils/fériés/compétences).
- **DoR** : Gherkin par volet, tests (unit calcul de Pâques, fonctionnels audit org/profils).
