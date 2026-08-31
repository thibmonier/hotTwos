# US-033: Budget bidimensionnel charge/montant et avenants

## Métadonnées
- **ID**: US-033
- **EPIC**: EPIC-002
- **Sprint**: 2
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-8, EF-PRJ-9, EF-PRJ-11, INV-2, RG-PRJ-4
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-010 (organisation), US-011 (profils et taux), US-030 (création projet), US-031 (lots et jalons)
- **Spec Technique**: PRJ-8 – Budget bidimensionnel et avenants

## User Story

**En tant que** Marc, chef de projet,
**je veux** saisir le budget de mon projet en jours par profil, voir automatiquement le montant de vente et le coût calculés à partir des taux en vigueur, et enregistrer des avenants horodatés pour reconstituer l'historique du budget,
**afin de** piloter avec précision la rentabilité prévisionnelle et justifier toute évolution budgétaire auprès de la direction.

## Critères d'Acceptation

### CA-1 (Nominal): Budget 60 jours profil mixte affiche €vente et €coût
```gherkin
GIVEN le projet PRJ-0042 "Refonte SI" est créé
  AND les profils actifs sont : Consultant Senior (TJM vente 850 €, coût 450 €) et Consultant Junior (TJM vente 600 €, coût 280 €)
WHEN Marc saisit 40 j Consultant Senior et 20 j Consultant Junior dans le budget
THEN le système affiche :
     Charge totale = 60 j
     Montant de vente = 40×850 + 20×600 = 46 000 €
     Coût prévisionnel = 40×450 + 20×280 = 23 600 €
     Marge prévisionnelle = 22 400 € (48,7 %)
  AND ces valeurs sont recalculées en temps réel à chaque saisie
```

### CA-2 (Alternatif): Historique des avenants reconstituable dans le temps
```gherkin
GIVEN le projet PRJ-0042 a un budget initial de 46 000 € signé le 2026-09-01
WHEN Marc enregistre un avenant +15 000 € le 2026-11-15 avec motif "Extension périmètre module reporting"
  AND Marc enregistre un avenant -5 000 € le 2027-01-10 avec motif "Réduction scope recette"
THEN le budget courant est de 56 000 €
  AND l'historique affiche dans l'ordre chronologique :
     Budget initial : 46 000 € – 2026-09-01
     Avenant 1 : +15 000 € – 2026-11-15 – Marc Dupont – "Extension périmètre module reporting"
     Avenant 2 : -5 000 € – 2027-01-10 – Marc Dupont – "Réduction scope recette"
     Budget courant : 56 000 €
  AND il est possible d'exporter cet historique en CSV
```

### CA-3 (Alternatif): Réallocation entre lots tracée sans modifier le total projet
```gherkin
GIVEN le projet PRJ-0042 a le lot L1 à 25 000 € et le lot L2 à 31 000 €
  AND le budget projet total est 56 000 €
WHEN Marc déplace 6 000 € du lot L1 vers le lot L2 avec le motif "Renforcement développement"
THEN le lot L1 passe à 19 000 € et le lot L2 passe à 37 000 €
  AND le total projet reste à 56 000 € (inchangé)
  AND la réallocation est tracée : auteur = Marc Dupont, date = 2027-01-12, motif = "Renforcement développement"
  AND aucun avenant n'est créé (réallocation interne, pas de modification du budget projet)
```

### CA-4 (Erreur): Modification du budget d'un projet actif exige motif et validation (RG-PRJ-4)
```gherkin
GIVEN le projet PRJ-0042 est au statut "En cours"
WHEN Marc tente de modifier le budget sans renseigner de motif
THEN le système bloque la modification et affiche "Un motif est obligatoire pour modifier le budget d'un projet actif (RG-PRJ-4)"
WHEN Marc renseigne le motif "Avenant contractuel signé le 2027-01-10"
  AND clique sur "Soumettre pour validation"
THEN une demande de validation est créée et notifiée à Élodie (direction)
  AND la modification n'est effective qu'après validation d'Élodie
```

### CA-5 (Alternatif): Taux en vigueur utilisés pour le calcul (lien avec US-011)
```gherkin
GIVEN le TJM du profil Consultant Senior était 800 € au 2026-01-01 et a été révisé à 850 € au 2026-09-01
WHEN Marc consulte le budget prévisionnel du projet PRJ-0042 démarré le 2026-09-01
THEN le calcul utilise le TJM 850 € (taux en vigueur à la date de démarrage)
  AND si Marc consulte un projet antérieur démarré le 2026-06-01
THEN ce projet utilise le TJM 800 € dans son historique
```

### CA-6 (Erreur): Réallocation entre lots refusée si elle modifie le budget total du projet
```gherkin
GIVEN le projet PRJ-0042 a le lot L1 à 25 000 € et le lot L2 à 31 000 €
  AND le budget total du projet est 56 000 €
WHEN Marc saisit une réallocation en augmentant le lot L1 de 10 000 € (L1 → 35 000 €) sans réduire le lot L2 du même montant (L2 reste à 31 000 €)
THEN le système refuse l'enregistrement avec le message "Une réallocation ne peut pas modifier le budget total du projet. Le montant débité (0 €) doit être égal au montant crédité (10 000 €) (EF-PRJ-11)"
  AND les budgets des lots restent inchangés (L1 : 25 000 €, L2 : 31 000 €)
  AND le budget total du projet reste à 56 000 €
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

EF-PRJ-8 : budget initial + avenants + budget courant tous horodatés et signés. EF-PRJ-9 : le budget est saisi en charge (jours) par profil ET en montant, les deux dimensions étant liées par les taux en vigueur au moment de la saisie (lien US-011). EF-PRJ-11 : la réallocation entre lots n'est pas un avenant — elle ne modifie pas le total projet mais doit être tracée (auteur, date, motif). INV-2 : indépendance entre budget charge et budget montant — les taux peuvent évoluer sans réécrire le budget. RG-PRJ-4 : toute modification du budget d'un projet actif nécessite un motif et une validation de la direction.
