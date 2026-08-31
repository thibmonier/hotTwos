# US-016: Devises et taux de change

## Métadonnées
- **ID**: US-016
- **EPIC**: EPIC-001
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-22, INV-2
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-22 (devises actives + taux de change, devise de référence par tenant)

## User Story

**En tant qu'** administrateur tenant,
**je veux** configurer les devises acceptées par le tenant, définir la devise de référence et saisir les taux de change historisés,
**afin de** permettre l'établissement de devis et le suivi de projets en multi-devises avec une conversion fiable et traçable vers la devise de référence, quelle que soit la période concernée.

## Critères d'Acceptation

### CA-1 (Nominal) : Configuration de la devise de référence et de deux devises actives
```gherkin
GIVEN un nouveau tenant "GlobalConsulting" est créé
WHEN l'ADMIN configure EUR comme devise de référence, active USD et GBP comme devises secondaires
  AND saisit les taux de change EUR/USD = 1,0850 et EUR/GBP = 0,8570 avec date d'effet 01/09/2026
THEN la devise EUR est affichée comme devise par défaut dans tous les modules (chiffrage, facturation, reporting)
  AND un devis créé en USD de 10 850 $ est converti automatiquement en 10 000 € dans les rapports consolidés
  AND un devis créé en GBP de 8 570 £ est converti en 10 000 € dans les rapports consolidés
```

### CA-2 (Nominal) : Historisation des taux de change — projet passé valorisé au taux de la période
```gherkin
GIVEN EUR/USD = 1,0500 du 01/01/2026 au 30/06/2026
  AND EUR/USD = 1,0850 du 01/07/2026 en cours
  AND un projet "US Migration" facturé en USD a des saisies de temps en juin (10 000 $) et juillet (10 000 $)
WHEN un rapport de rentabilité est généré pour ce projet sur la période janvier–juillet
THEN les saisies de juin sont converties à 1,0500 → 9 524 €
  AND les saisies de juillet sont converties à 1,0850 → 9 217 €
  AND le rapport indique clairement le taux de change utilisé pour chaque période (pas seulement le montant converti)
```

### CA-3 (Alternatif) : Ajout d'une nouvelle devise en cours d'exercice
```gherkin
GIVEN le tenant utilise EUR (référence) et USD depuis le 01/01
  AND un nouveau client canadien est signé en août
WHEN l'ADMIN active la devise CAD avec un taux EUR/CAD = 1,4780 à compter du 01/08
THEN CAD est disponible dans les menus de sélection de devise dès sa date d'activation
  AND les projets et devis antérieurs au 01/08 restent en EUR ou USD (aucune conversion rétroactive automatique)
  AND la liste des devises actives affiche CAD avec sa date d'activation et son taux courant
```

### CA-4 (Alternatif) : Mise à jour mensuelle du taux de change avec historique visible
```gherkin
GIVEN EUR/CHF = 0,9320 depuis le 01/07
WHEN l'ADMIN saisit un nouveau taux EUR/CHF = 0,9450 avec date d'effet 01/09
THEN l'entrée du 01/07 reçoit automatiquement la date de fin 31/08
  AND la nouvelle entrée EUR/CHF = 0,9450 est active à partir du 01/09
  AND la vue "Historique des taux" pour EUR/CHF affiche la chronologie complète des taux depuis l'activation
  AND les montants convertis avant le 01/09 ne sont pas recalculés (conformément à INV-2)
```

### CA-5 (Erreur) : Désactivation de la devise de référence → refus
```gherkin
GIVEN EUR est configurée comme devise de référence du tenant
  AND 240 projets et 89 devis utilisent EUR comme devise
WHEN l'ADMIN tente de désactiver EUR ou de changer la devise de référence via l'interface
THEN le système refuse avec le message : "La devise de référence EUR ne peut pas être désactivée. Pour changer de devise de référence, contactez le support (opération à fort impact)."
  AND aucune modification n'est effectuée
  AND un lien vers la documentation de migration de devise de référence est proposé
```

### CA-6 (Erreur) : Taux de change manquant pour une devise à une date donnée → refus de conversion et alerte
```gherkin
GIVEN la devise CHF est activée depuis le 01/01/2026
  AND aucun taux EUR/CHF n'est configuré pour la période du 01/06/2026 au 31/08/2026 (lacune dans l'historique)
WHEN un rapport de rentabilité tente de convertir un montant de 5 000 CHF facturé le 15/07/2026
THEN le système refuse la conversion et affiche : "Taux de change EUR/CHF manquant pour la date du 15/07/2026. Saisissez un taux pour cette période avant de générer le rapport."
  AND le rapport n'est pas généré partiellement (aucune conversion approximative ou silencieuse n'est effectuée)
  AND une alerte est envoyée à l'ADMIN listant tous les couples devise/période sans taux configuré
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

Les taux de change sont stockés dans une table `exchange_rate_history(tenant_id, from_currency, to_currency, rate, effective_from, effective_to)`. La résolution du taux pour une date donnée utilise la même mécanique que US-011 (date d'effet avec effective_to NULL pour l'entrée courante).

INV-2 : les taux de change passés ne doivent jamais être modifiés rétroactivement sans trace d'audit. Si une correction est nécessaire (erreur de saisie), l'ancienne entrée est fermée et une nouvelle entrée est créée avec une note de correction.

Pour le Sprint 1, seule la saisie manuelle des taux est requise. La mise à jour automatique via un flux de taux (BCE, fixer.io) est prévue pour un lot ultérieur (EF-REF-22 étendu).

La devise de référence est unique par tenant et ne peut être changée qu'avec une procédure supervisée (impact sur tous les montants historiques convertis).
