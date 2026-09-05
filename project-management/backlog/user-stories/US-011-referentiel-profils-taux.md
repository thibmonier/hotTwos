# US-011: Référentiel de profils avec coûts et taux historisés à date d'effet

## Métadonnées
- **ID**: US-011
- **EPIC**: EPIC-001
- **Sprint**: Sprint 4
- **Statut**: ✅ Done (livré Sprint 4)
- **Points**: 8
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-01
<!-- last_sync: 2026-09-01 (source: workflow-status.yaml current_sprint id:4) -->

## Traçabilité
- **Implémente**: EF-REF-4, EF-REF-5, EF-REF-20, INV-2, RG-REF-2, RG-REF-4
- **Dépend de**: US-001 (fondation multi-tenant), US-010 (structure organisationnelle)
- **Spec Technique**: EF-REF-4 (profils portant coût de revient + taux de vente), EF-REF-5 (historisation à date d'effet), EF-REF-20 (mode de calcul coût de revient paramétrable)

## User Story

**En tant qu'** administrateur tenant,
**je veux** créer et maintenir un référentiel de profils portant chacun un coût de revient (mode de calcul paramétrable : direct, chargé ou complet) et un taux de vente standard, historisés à date d'effet,
**afin de** valoriser automatiquement les temps saisis avec les tarifs en vigueur à la période concernée et permettre un chiffrage et une rentabilité fiables sans modifier les données passées lors d'une révision tarifaire.

## Critères d'Acceptation

### CA-1 (Nominal) : Augmentation tarifaire au 01/07 — la valorisation de juin reste inchangée
```gherkin
GIVEN le profil "Développeur Senior" a un taux de vente de 700 €/j et un coût de revient de 420 €/j depuis le 01/01
  AND des saisies de temps en juin ont été valorisées sur cette base (ex. : 15 jours × 700 € = 10 500 €)
WHEN l'ADMIN crée une nouvelle entrée tarifaire pour "Développeur Senior" avec taux de vente 750 €/j, effective au 01/07
THEN les saisies de temps de juin restent valorisées à 700 €/j (10 500 € inchangé)
  AND les saisies de temps de juillet et après appliquent automatiquement 750 €/j
  AND un rapport de rentabilité distingue les deux périodes tarifaires sur l'axe temporel
```

### CA-2 (Nominal) : Mode de calcul "coût chargé" paramétré par défaut au niveau tenant
```gherkin
GIVEN le mode de calcul du coût de revient est configuré à "chargé" pour le tenant (salaire brut + charges patronales)
  AND le profil "Chef de Projet" a un salaire annuel brut de 60 000 € avec un taux de charge de 42 %
WHEN l'ADMIN crée ou met à jour le profil "Chef de Projet"
THEN le coût de revient journalier est calculé automatiquement : (60 000 × 1,42) / 218 jours = 391,74 €/j
  AND le taux de vente standard reste saisie manuelle libre et indépendant du coût
  AND le mode de calcul affiché dans la fiche profil indique "Chargé (salaire + charges patronales)"
```

### CA-3 (Alternatif) : Modification rétroactive d'un taux → confirmation avec volume impacté
```gherkin
GIVEN le profil "Consultant Junior" a un taux de vente de 500 €/j en vigueur depuis le 01/01
  AND 45 saisies de temps totalisant 67,5 jours ont été valorisées sur cette base
WHEN l'ADMIN tente de modifier rétroactivement ce taux à 520 €/j avec effet au 01/01 (modification de l'entrée existante)
THEN le système affiche un message d'avertissement : "Cette modification rétroactive impactera 45 saisies (67,5 jours). Confirmer ?"
  AND l'ADMIN doit cliquer "Confirmer la modification rétroactive" explicitement (conformément à RG-REF-4)
  AND après confirmation, toutes les valorisations antérieures sont recalculées avec le nouveau taux
  AND un événement d'audit enregistre : utilisateur, date, ancien taux, nouveau taux, volume impacté
```

### CA-4 (Alternatif) : Consultation de l'historique tarifaire d'un profil
```gherkin
GIVEN le profil "Architecte" a eu 3 révisions tarifaires : 01/01/2025, 01/07/2025, 01/01/2026
WHEN un utilisateur ADMIN consulte la fiche du profil "Architecte" en vue "Historique tarifaire"
THEN la liste affiche les 3 entrées avec date d'effet, taux de vente, coût de revient et auteur de chaque modification
  AND la ligne active (date d'effet ≤ aujourd'hui, date de fin NULL) est mise en évidence
  AND la timeline visuelle permet d'identifier les périodes sans chevauchement ni trou
```

### CA-5 (Erreur) : Chevauchement de périodes tarifaires → refus
```gherkin
GIVEN le profil "Designer UX" a une entrée tarifaire du 01/01 au 30/06 (taux : 600 €/j) et une entrée du 01/07 en cours (taux : 650 €/j)
WHEN l'ADMIN tente de créer une nouvelle entrée avec date d'effet 15/03 (chevauchant la première période)
THEN le système refuse avec le message "La date d'effet 15/03 chevauche une période existante (01/01 – 30/06). Veuillez ajuster les dates."
  AND aucune nouvelle entrée n'est créée
  AND les deux entrées existantes restent inchangées
```

### CA-6 (Erreur) : Saisie d'un taux de vente ou d'un coût de revient négatif → refus
```gherkin
GIVEN l'ADMIN accède à la fiche du profil "Développeur Junior" pour créer une nouvelle entrée tarifaire effective au 01/09
WHEN l'ADMIN saisit un taux de vente de -150 €/j (valeur négative)
THEN le système refuse la saisie avec le message "Le taux de vente doit être un montant positif. Valeur saisie : -150 €/j."
  AND aucune entrée tarifaire n'est créée
  AND le champ taux de vente est mis en évidence avec l'erreur de validation
  AND la même contrainte s'applique au coût de revient (refus si valeur ≤ 0)
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

L'historisation (EF-REF-5 / RG-REF-2) repose sur une table `profile_rate_history` avec `(profile_id, tenant_id, effective_from, effective_to, cost_price, selling_price, calculation_mode)`. La requête de valorisation d'un temps saisi utilise `effective_from <= timesheet_date < effective_to` (ou IS NULL pour l'entrée en cours).

INV-2 : toute modification de données financières passées doit être journalisée avec auteur, timestamp, valeur avant/après et volume impacté. Cette journalisation alimente l'US-020 (journal d'audit).

Les trois modes de calcul EF-REF-20 (direct / chargé / complet) sont configurables au niveau du tenant et peuvent être surchargés au niveau du profil individuel. Le changement de mode ne doit pas effacer l'historique des calculs précédents.
