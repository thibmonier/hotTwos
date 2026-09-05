# US-034: Engagements externes rattachés au projet

## Métadonnées
- **ID**: US-034
- **EPIC**: EPIC-002
- **Sprint**: 2
- **Statut**: ✅ Done (livré Sprint 6)
- **Points**: 3
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-10
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet), US-033 (budget charge/montant)
- **Spec Technique**: PRJ-10 – Engagements externes

## User Story

**En tant que** Marc, chef de projet,
**je veux** rattacher des engagements externes (sous-traitance, achats, licences) à mon projet avec leur coût et leur statut,
**afin que** la marge projet reflète l'ensemble des coûts engagés et pas uniquement les coûts de ressources internes.

## Critères d'Acceptation

### CA-1 (Nominal): La marge projet intègre les engagements externes
```gherkin
GIVEN le projet PRJ-0042 "Refonte SI" a un budget de vente de 56 000 €
  AND des ressources internes imputées représentent un coût de 23 600 €
  AND un engagement externe "Sous-traitance maquettage" de 4 500 € est rattaché au projet
  AND un engagement externe "Licence outil de tests" de 800 € est rattaché au projet
WHEN Marc consulte la vue financière du projet
THEN la marge affichée est : 56 000 – 23 600 – 4 500 – 800 = 27 100 € (48,4 %)
  AND la ligne "Coûts externes" détaille les deux engagements séparément
  AND la ligne "Coûts internes" détaille uniquement le temps interne
```

### CA-2 (Alternatif): Saisie d'un engagement externe avec type et statut
```gherkin
GIVEN le projet PRJ-0042 est au statut "En cours"
WHEN Marc crée un engagement "Sous-traitance intégration" de type "Sous-traitance", montant 6 000 €,
     fournisseur "DevShop SAS", statut "Engagé", date d'engagement 2026-10-01
THEN l'engagement est enregistré et visible dans l'onglet "Engagements externes" du projet
  AND le coût prévisionnel du projet est mis à jour en intégrant ces 6 000 €
  AND la marge prévisionnelle est recalculée immédiatement
```

### CA-3 (Alternatif): Vue des engagements externes filtrée par type
```gherkin
GIVEN le projet PRJ-0042 a 3 engagements : 2 sous-traitances et 1 licence logicielle
WHEN Marc filtre la liste des engagements par type "Sous-traitance"
THEN seuls les 2 engagements de sous-traitance sont affichés
  AND le sous-total affiché correspond à la somme des 2 engagements
  AND le filtre est cumulable avec un filtre par statut (Prévisionnel / Engagé / Facturé / Soldé)
```

### CA-4 (Alternatif): Engagement externe lié à un lot
```gherkin
GIVEN le projet PRJ-0042 a deux lots : L1 "Analyse" et L2 "Développement"
WHEN Marc rattache l'engagement "Sous-traitance intégration" au lot L2 "Développement"
THEN la vue du lot L2 affiche cet engagement dans ses coûts
  AND la marge du lot L2 intègre ce coût externe
  AND la marge du lot L1 n'est pas affectée
```

### CA-5 (Erreur): Tentative de rattachement à un projet clôturé refusée
```gherkin
GIVEN le projet PRJ-0030 est au statut "Clôturé"
WHEN Marc tente de créer un nouvel engagement externe sur PRJ-0030
THEN le système refuse avec le message "Impossble de créer un engagement sur un projet clôturé (RG-PRJ-5)"
  AND aucun engagement n'est créé
  AND les engagements existants restent consultables en lecture seule
```

### CA-6 (Erreur): Engagement externe refusé si le montant ou le fournisseur est absent
```gherkin
GIVEN le projet PRJ-0042 est au statut "En cours"
WHEN Marc tente de créer un engagement externe de type "Sous-traitance" sans renseigner le montant et sans renseigner le fournisseur
THEN le système refuse l'enregistrement avec les messages de validation :
     "Le montant de l'engagement est obligatoire"
     "Le fournisseur est obligatoire"
  AND aucun engagement n'est créé
  AND le formulaire reste ouvert avec les champs en erreur mis en évidence
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

EF-PRJ-10 : les types d'engagements sont : Sous-traitance, Achat matériel, Achat logiciel/licence, Frais de déplacement, Autre. Les statuts sont : Prévisionnel, Engagé, Facturé reçu, Soldé. Un engagement peut être rattaché au projet en général ou à un lot spécifique. Le montant des engagements est en € HT. Les engagements sont inclus dans le calcul de la marge mais pas dans le calcul de la charge (jours).
