# US-036: Vue d'atterrissage et détection de dérive

## Métadonnées
- **ID**: US-036
- **EPIC**: EPIC-002
- **Sprint**: 3
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: P2 (Marc – Chef de projet), P6 (Élodie – Dirigeante)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-14, EF-PRJ-15, EF-PRJ-16
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet), US-033 (budget charge/montant), US-035 (avancement et RAF)
- **Spec Technique**: PRJ-14 – Atterrissage et détection de dérive

## User Story

**En tant que** Marc (chef de projet) ou Élodie (dirigeante),
**je veux** disposer d'une vue synthétique affichant les 5 indicateurs clés (budget, consommé, RAF, atterrissage, écart), être alerté dès qu'une dérive dépasse un seuil, et visualiser l'historique des projections pour comprendre quand la dérive a débuté,
**afin de** prendre des décisions correctives avant que le dépassement ne soit irrattrapable.

## Critères d'Acceptation

### CA-1 (Nominal): Les 5 valeurs clés sur une même vue exportable
```gherkin
GIVEN le projet PRJ-0042 est en cours avec :
     Budget courant = 200 j / 56 000 €
     Consommé à ce jour = 110 j / 30 800 €
     RAF saisi par Marc = 105 j / 29 400 €
WHEN Marc ou Élodie ouvre la vue d'atterrissage du projet PRJ-0042
THEN la vue affiche sur une seule page :
     Budget : 200 j / 56 000 €
     Consommé : 110 j / 30 800 €
     RAF : 105 j / 29 400 €
     Atterrissage (Consommé + RAF) : 215 j / 60 200 €
     Écart (Atterrissage – Budget) : +15 j / +4 200 € (+7,5 %)
  AND un bouton "Exporter CSV/Excel" est disponible et fonctionnel
  AND la vue est accessible depuis le tableau de bord du projet
```

### CA-2 (Alternatif): Alerte au chef de projet au 1er seuil, puis à la direction au 2e seuil
```gherkin
GIVEN les seuils configurés sont : CP = +5 % de dépassement, Direction = +10 %
  AND le projet PRJ-0042 a un écart atterrissage/budget de +6 %
WHEN le système calcule l'atterrissage après la mise à jour hebdomadaire du RAF
THEN Marc reçoit une alerte par notification et email "Dérive détectée sur PRJ-0042 : +6 % (seuil CP atteint)"
  AND Élodie ne reçoit pas encore d'alerte (seuil direction non atteint)
WHEN l'écart atteint +11 %
THEN Marc reçoit une nouvelle alerte "Dérive critique : +11 % (seuil direction atteint)"
  AND Élodie reçoit également une alerte "Dérive critique sur PRJ-0042 : +11 % – Votre validation est requise"
```

### CA-3 (Alternatif): La courbe historique montre quand la projection s'est dégradée
```gherkin
GIVEN le projet PRJ-0042 a des mises à jour hebdomadaires sur 8 semaines
  AND les atterrissages successifs étaient : 200j, 202j, 205j, 210j, 215j, 215j, 215j, 215j
WHEN Marc ouvre l'onglet "Historique des projections" du projet
THEN une courbe temporelle affiche l'évolution de l'atterrissage semaine par semaine
  AND le budget initial (200j) est représenté par une ligne horizontale de référence
  AND le point de première dérive est mis en évidence (semaine 2 : 202j)
  AND il est possible de filtrer l'historique par lot
```

### CA-4 (Alternatif): Vue d'atterrissage par lot
```gherkin
GIVEN le projet PRJ-0042 a deux lots L1 et L2 avec des écarts différents
  AND le lot L1 est dans les clous (atterrissage = budget)
  AND le lot L2 est en dérive de +20 %
WHEN Marc consulte la vue d'atterrissage en mode "Détail par lot"
THEN le lot L1 affiche un indicateur vert (dans les clous)
  AND le lot L2 affiche un indicateur rouge (dérive > seuil direction)
  AND le total projet consolide les deux lots
```

### CA-5 (Erreur): Aucune alerte déclenchée sous le seuil CP
```gherkin
GIVEN les seuils configurés sont : CP = +5 %, Direction = +10 %
  AND le projet PRJ-0055 a un atterrissage de 203 j pour un budget de 200 j (écart +1,5 %)
WHEN le système recalcule l'atterrissage
THEN aucune alerte n'est déclenchée ni pour Marc ni pour Élodie
  AND l'écart est affiché en jaune/orange pour information sans notification push
```

### CA-6 (Erreur): Seuil de dérive incohérent refusé à la configuration, ou RAF manquant rendant l'atterrissage incalculable
```gherkin
GIVEN Marc tente de configurer les seuils d'alerte du projet PRJ-0042 avec :
     seuil Direction = 3 % et seuil Chef de projet = 8 %
     (seuil Direction inférieur au seuil Chef de projet)
WHEN Marc valide la configuration des seuils
THEN la configuration est refusée avec le message "Le seuil direction (3 %) doit être supérieur ou égal au seuil chef de projet (8 %) — configuration incohérente"
  AND les seuils précédemment actifs sont conservés sans modification
  AND aucune alerte n'est reémise sur la base de la configuration invalide

GIVEN le projet PRJ-0042 comporte des lots dont le RAF n'a pas encore été saisi (lot L2 et L3)
WHEN le système tente de calculer l'atterrissage global du projet
THEN l'indicateur d'atterrissage affiche "Incalculable – RAF manquant sur : L2 Développement, L3 Tests"
  AND aucune alerte de dérive n'est déclenchée
  AND Marc reçoit une notification "Mise à jour du RAF requise pour les lots L2 et L3 afin de calculer l'atterrissage"
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

EF-PRJ-14 : la vue d'atterrissage affiche les 5 valeurs (Budget / Consommé / RAF / Atterrissage / Écart) au niveau projet et par lot. Elle est exportable en CSV et Excel. EF-PRJ-15 : double seuil d'alerte configurable par ADMIN — seuil CP (notification chef de projet) et seuil direction (notification dirigeante). L'atterrissage = consommé + RAF. L'écart = atterrissage – budget courant. EF-PRJ-16 : l'historique des atterrissages est conservé à chaque mise à jour du RAF, permettant de tracer une courbe de projection dans le temps. Les seuils sont configurables par projet ou par défaut global.
