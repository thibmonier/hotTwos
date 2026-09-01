# Module Tarification (US-011)

Référentiel de profils portant coûts de revient et taux de vente **historisés à date d'effet**,
et moteur de résolution du tarif en vigueur. Brique pivot de la valorisation (US-060) : une
révision tarifaire ne réécrit jamais les valeurs passées.

## Modèle de domaine

| Élément | Rôle |
|---------|------|
| `Domain\Pricing\CalculationMode` | Mode de calcul du coût : `direct` / `loaded` (chargé) / `full` (complet) — EF-REF-20. |
| `Domain\Pricing\Profile` | Profil portant le mode de calcul, désactivable (RG-REF-1). |
| `Domain\Pricing\ProfileRate` | Entrée tarifaire : coût et taux de vente en **centimes entiers** (INV-2), sur une `EffectivePeriod`. |
| `Domain\Pricing\RateResolver` | **Moteur unique (ARC-6)** : résout le tarif en vigueur à une date (`from <= date < to`), déterministe. |
| `Domain\Pricing\LoadedCostCalculator` | Coût chargé journalier `brut × (1 + charge) / jours ouvrés`, arithmétique entière (CA-2). |

Toutes les entités sont `TenantOwned`. Montants **toujours en centimes entiers** — jamais de flottant stocké.

## Règles métier (couche Application)

- **`ManageProfiles`** : création / désactivation de profils (habilitation `MANAGE_PRICING`).
- **`DefineProfileRate`** : définition d'une entrée tarifaire, avec règles serveur (ARC-19) —
  - refus des valeurs ≤ 0 (CA-6) ;
  - refus de chevauchement de périodes pour un profil (CA-5, `EffectivePeriod::overlaps`) ;
  - saisie **rétroactive** (date d'effet < aujourd'hui, via `ClockInterface`) soumise à
    confirmation explicite et tracée (CA-3, RG-REF-4, INV-2) ;
  - une révision **ajoute** une entrée, n'altère jamais les précédentes.

## API (DTO strict, ADR-4)

| Opération | Effet |
|-----------|-------|
| `GET /api/profiles` | Liste des profils du tenant. |
| `POST /api/profiles` | Création (mode `direct`/`loaded`/`full`). |
| `DELETE /api/profiles/{id}` | **Désactivation** (RG-REF-1). |
| `GET /api/profile-rates?profileId=…` | Historique tarifaire d'un profil. |
| `POST /api/profile-rates` | Définition d'un tarif (`confirmRetroactive` pour CA-3). |

Refus d'habilitation → **403**, erreur métier (chevauchement, ≤ 0, mode invalide, rétroactif) →
**422** (`PricingException`), sans trace. Écran d'administration : `/profils` (timeline avec ligne
en vigueur mise en évidence, CA-4).

## Sécurité des données (RLS)

Les tables `profile` et `profile_rate` naissent avec Row-Level Security (`ENABLE` + `FORCE` +
policy `tenant_isolation` en comparaison texte, robuste au contexte absent). Le test d'intrusion
`PricingRlsRuntimeTest` vérifie, sous rôle non-superutilisateur, qu'un **coût de revient** (donnée
sensible) ne fuit jamais entre tenants.

## Consommation par la valorisation (US-060)

`RateResolver::resolveAt(tenant, profileId, date)` renvoie la `ProfileRate` en vigueur à la date
de la prestation. Absence de tarif → `NoEffectiveRateException`, consommée par US-060 (CA-4 « taux
manquant » → valorisation partielle). Le coût et le taux **figés** au moment de la validation
garantiront la non-réécriture (INV-2/INV-3).

## Revue (T-011-08) — findings traités

Revues croisées `security-auditor` (OWASP 2025) et `symfony-reviewer` : **aucun finding
Critique/Élevé**. Corrections appliquées :

- **Traçage de la lecture du coût** (Medium, HAB-6) : `ProfileRateCollectionProvider` utilise
  `authorizeSensitiveRead` (comme le coût collaborateur) → événement `sensitive_data_read`.
- **Montants bornés** (Medium, A07/CWE-190) : plafond métier (~10 M€) dans `DefineProfileRate`
  → 422, évite un dépassement de la colonne `INT` (32 bits) et donc un 500.
- **Saisie du jour non rétroactive** (Low) : comparaison à minuit du jour (l'heure de `now()`
  ne rend plus un tarif effectif « aujourd'hui » rétroactif à tort).
- **Parsing de date strict** (Low) : `getLastErrors()` rejette les dates qui débordent
  (`2026-13-45`) → 422, dans les processors tarif et rattachement.
- **Refus de tarif sur profil désactivé** (Low) → 422.
- *Nuancé* : l'écart de garde `ProfileRate` (≥ 0, invariant technique) vs `DefineProfileRate`
  (> 0, règle métier CA-6) est un **layering DDD volontaire**, pas une incohérence.

## Limites connues / suite

- Câblage du `LoadedCostCalculator` à l'écran (dérivation auto du coût pour un profil `loaded`).
- Migration des colonnes de montants en `BIGINT` si des montants > 10 M€ deviennent plausibles.
- Harmonisation `WITH CHECK` explicite des policies RLS (défensif, transverse au socle).
