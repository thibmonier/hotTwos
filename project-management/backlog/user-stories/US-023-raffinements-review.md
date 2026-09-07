# US-023: Raffinements Review — fériés mobiles & audit étendu

## Métadonnées
- **ID**: US-023
- **EPIC**: EPIC-001 (finitions)
- **Sprint**: 18
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P-ADMIN
- **Créé le**: 2026-09-07
- **Mis à jour**: 2026-09-07 (affinage S18)

## Traçabilité
- **Origine**: arbitrages Sprint Review S16/S17 (approuvés PO)
- **Étend**: US-019 (`InitializeTenantDefaults`, EF-REF-6), US-020 (`ConfigAuditRecorder`, EF-REF-33)
- **Réutilise / ne re-spécifie pas** : `Holiday` (US-012), `ConfigAuditRecorder` (US-020).

## User Story

**En tant qu'** administrateur,
**je veux** que l'onboarding provisionne aussi les **jours fériés mobiles** (Pâques, Lundi de Pâques,
Ascension, Pentecôte, Lundi de Pentecôte) et que le **journal d'audit** couvre les changements
d'**organisation** et de **profils/taux**,
**afin d'** avoir des défauts complets et une traçabilité étendue du paramétrage.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : l'onboarding provisionne les fériés mobiles de l'année
```gherkin
GIVEN un tenant initialisé (tenant:init) pour une année civile donnée
WHEN les défauts sont provisionnés
THEN les fériés mobiles (Lundi de Pâques, Ascension, Lundi de Pentecôte) de l'année sont créés,
     en plus des fériés fixes déjà provisionnés (US-019)
  AND ils sont exclus des jours ouvrés (via WorkingDaysCalculator)
```

### CA-2 (Nominal) : calcul correct de Pâques
```gherkin
GIVEN l'algorithme de calcul du dimanche de Pâques (comput)
WHEN on calcule Pâques pour 2027
THEN la date est le 28/03/2027
  AND Ascension = Pâques + 39 j, Lundi de Pentecôte = Pâques + 50 j
```

### CA-3 (Nominal) : une modification d'organisation est auditée
```gherkin
GIVEN un administrateur modifie une unité d'organisation (OrgUnit)
WHEN la modification est enregistrée
THEN une entrée d'audit « Organisation » est créée (auteur, avant/après, horodatage)
```

### CA-4 (Alternatif) : une modification de taux de profil est auditée
```gherkin
GIVEN un administrateur modifie un taux de profil (ProfileRate)
WHEN la modification est enregistrée
THEN une entrée d'audit « Profil / Taux » est créée et visible dans le journal (US-020)
```

### CA-5 (Erreur) : idempotence de l'onboarding conservée
```gherkin
GIVEN un tenant déjà initialisé (fériés fixes + mobiles présents)
WHEN tenant:init est rejoué
THEN aucun doublon de férié mobile n'est créé
```

### CA-6 (Erreur) : le journal d'audit reste réservé
```gherkin
GIVEN un utilisateur sans VIEW_AUDIT_LOG
WHEN il tente de consulter les entrées d'audit org/profils
THEN l'accès est refusé (403)
```

## Notes techniques (pour la décomposition)
- **Fériés mobiles** : fonction pure de calcul de Pâques (Butcher/Gauss) → dates dérivées ; ajoutées dans
  `InitializeTenantDefaults::defaultHolidays()` (US-019). Idempotence déjà assurée (existsForDate).
- **Audit étendu** : appeler `ConfigAuditRecorder::record()` depuis les écritures OrgUnit et Profile/ProfileRate
  (contrôleurs/use cases concernés) ; réutiliser objectType « Organisation » / « Profil ».
- **Tests** : unit (calcul de Pâques 2–3 années de référence) ; fonctionnels (onboarding crée les mobiles,
  audit org/profils, 403) ; **SchemaTool** : entités déjà couvertes, ajouter au besoin.

## Definition of Ready
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Réutilise US-012/019/020 ; idempotence conservée
- [x] Estimation 5 pts ; gating VIEW_AUDIT_LOG

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert ; code review
