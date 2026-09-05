# US-031: Structure en lots et jalons

## Métadonnées
- **ID**: US-031
- **EPIC**: EPIC-002
- **Sprint**: 2
- **Statut**: ✅ Done (livré Sprint 6)
- **Points**: 5
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-2, EF-PRJ-3
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet)
- **Spec Technique**: PRJ-2 – Structure lots et jalons

## User Story

**En tant que** Marc, chef de projet,
**je veux** organiser mon projet en lots hiérarchiques avec un budget charge et montant par lot, et y rattacher des jalons datés,
**afin de** suivre la répartition du budget et déclencher automatiquement des actions de facturation sur des jalons clés.

## Critères d'Acceptation

### CA-1 (Nominal): Création d'une arborescence de lots avec budget
```gherkin
GIVEN le projet PRJ-0042 "Refonte SI" existe avec un budget de 200 000 €
WHEN je crée le lot L1 "Analyse" avec 40 j et 32 000 €
  AND je crée le lot L2 "Développement" avec 120 j et 96 000 €
  AND je crée le sous-lot L2.1 "Module Auth" avec 40 j et 32 000 €
  AND je crée le sous-lot L2.2 "Module Reporting" avec 80 j et 64 000 €
  AND je crée le lot L3 "Recette" avec 30 j et 24 000 €
THEN l'arborescence à 2 niveaux est enregistrée
  AND la somme des lots (190 j / 152 000 €) est affichée en regard du budget projet (200 000 €)
  AND l'écart de 48 000 € est signalé par un indicateur orange visible
```

### CA-2 (Alternatif): Jalon "Recette validée" déclenche une facture à émettre
```gherkin
GIVEN le projet PRJ-0042 dispose du jalon J3 "Recette validée" daté au 2027-02-28
  AND le jalon est configuré avec le déclencheur "Facturation automatique 60 000 €"
WHEN Marc passe le statut du jalon J3 à "Atteint"
THEN une facture à émettre de 60 000 € est automatiquement créée dans le module facturation
  AND une notification est envoyée au responsable facturation
  AND le jalon affiche la date d'atteinte réelle 2027-02-25
```

### CA-3 (Alternatif): Réallocation de budget entre lots sans modifier le total projet
```gherkin
GIVEN le projet PRJ-0042 a le lot L1 à 32 000 € et le lot L3 à 24 000 €
WHEN je déplace 8 000 € du lot L1 vers le lot L3
THEN le lot L1 passe à 24 000 € et le lot L3 à 32 000 €
  AND le total du projet reste à 200 000 €
  AND la réallocation est tracée : auteur, date, motif obligatoire
```

### CA-4 (Alternatif): Écart somme lots / budget projet signalé
```gherkin
GIVEN le projet PRJ-0050 a un budget de 100 000 €
WHEN la somme des budgets des lots est de 115 000 €
THEN le système affiche un avertissement "Écart lots/budget : +15 000 € (115 %)"
  AND le chef de projet peut sauvegarder avec confirmation explicite
  AND l'écart est visible sur le tableau de bord du projet
```

### CA-5 (Alternatif): Jalon sans déclencheur de facturation
```gherkin
GIVEN le projet PRJ-0042 dispose du jalon J1 "Lancement" sans déclencheur de facturation
WHEN Marc passe le statut du jalon J1 à "Atteint"
THEN aucune facture n'est créée
  AND le jalon affiche la date d'atteinte et le statut "Atteint"
  AND le tableau de bord des jalons est mis à jour
```

### CA-6 (Erreur): Enregistrement refusé si la somme des budgets des lots dépasse le budget projet sans confirmation explicite
```gherkin
GIVEN le projet PRJ-0050 a un budget de 100 000 €
  AND la somme des budgets des lots saisis est de 118 000 €
WHEN le chef de projet clique sur "Enregistrer" sans avoir coché la case de confirmation de dépassement
THEN le système refuse l'enregistrement avec le message "Confirmation requise : la somme des lots (118 000 €) dépasse le budget projet (100 000 €). Veuillez confirmer explicitement pour sauvegarder (EF-PRJ-2)"
  AND aucune modification n'est persistée en base
  AND le formulaire reste ouvert avec l'indicateur d'écart mis en évidence
```

### CA-7 (Erreur): Jalon daté hors de la période du projet ou déclencheur de facturation sur jalon déjà facturé
```gherkin
GIVEN le projet PRJ-0042 couvre la période du 2026-09-01 au 2027-03-31
  AND le jalon J4 "Livraison finale" est saisi avec la date prévisionnelle 2027-06-15
WHEN Marc tente d'enregistrer le jalon J4
THEN le système refuse avec le message "La date du jalon (2027-06-15) est hors de la période du projet (2026-09-01 – 2027-03-31)"
  AND le jalon n'est pas créé

GIVEN le jalon J3 "Recette validée" est au statut "Atteint" et a déjà déclenché une facture de 60 000 €
WHEN Marc tente de repasser le jalon J3 à "Atteint" pour déclencher une seconde facturation
THEN le système refuse avec le message "Ce jalon a déjà déclenché une facturation le 2027-02-25. Une nouvelle facturation n'est pas autorisée sur ce jalon (EF-PRJ-3)"
  AND aucune facture supplémentaire n'est créée
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

EF-PRJ-2 : les lots supportent minimum 2 niveaux d'arborescence. Le budget est bidimensionnel (charge en jours + montant en €). La somme des lots n'est pas forcément égale au budget projet mais tout écart doit être signalé. EF-PRJ-3 : les jalons portent une date prévisionnelle, une date réelle, un statut (À venir / Atteint / Retardé) et un déclencheur de facturation optionnel.
