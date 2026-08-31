# US-053: Pré-remplissage assisté depuis le plan (IA) et confirmation

## Métadonnées
- **ID**: US-053
- **EPIC**: EPIC-003
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P1 (Camille — collaborateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-9 (pré-remplissage de la semaine depuis les affectations planifiées ; semaine conforme au plan = confirmation en un seul geste), EF-TMP-11 (aucune imputation n'est créée sans confirmation explicite du collaborateur), RG-TMP-4 (une proposition non confirmée reste à l'état "proposition" et n'alimente aucun calcul ni rapport), ENF-IA-1 (explicabilité : la proposition affiche les données sources qui la fondent), ENF-IA-2 (non-substitution : l'IA aide, ne décide pas), HAB-5 (filtrage des projets à la source selon les affectations du collaborateur)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-037 (affectation projet — données planifiées), US-050 (saisie de base), socle IA EPIC-010
- **Spec Technique**: EF-TMP-9, EF-TMP-11, RG-TMP-4, ENF-IA-1, ENF-IA-2, HAB-5

## User Story

**En tant que** collaborateur (Camille),
**je veux** que le système me propose automatiquement un pré-remplissage de ma semaine de saisie basé sur mes affectations planifiées, que je puisse confirmer en un seul geste si la semaine est conforme,
**afin de** réduire encore le temps de saisie pour les semaines standard et d'éviter les ressaisies manuelles redondantes.

## Critères d'Acceptation

### CA-1 (Nominal) : Semaine conforme au plan — confirmation en un seul geste

```gherkin
GIVEN Camille est affectée en planning sur la semaine du 24-28/08/2026 : 8h/j sur P-Alpha (lun-mer) et 8h/j sur P-Beta (jeu-ven)
  AND ces affectations existent dans le module de planification (US-037)
  AND la semaine du 24/08/2026 est ouverte et vide dans la vue de saisie
WHEN Camille ouvre la vue de saisie hebdomadaire du 24/08/2026
THEN le système affiche une proposition pré-remplie : 8h sur P-Alpha lundi, mardi, mercredi et 8h sur P-Beta jeudi, vendredi
  AND la proposition indique clairement son origine : "Basé sur votre planning semaine 35 — affectations P-Alpha 24-26/08, P-Beta 27-28/08"
  AND un bouton "Confirmer la semaine telle quelle" est visible en position prioritaire
  AND Camille peut confirmer en un seul clic, sans aucune saisie ni modification
  AND la confirmation crée les imputations avec les données exactes de la proposition
```

### CA-2 (Alternatif) : Semaine partiellement conforme — modification partielle avant confirmation

```gherkin
GIVEN la proposition IA pré-remplit 5 jours × 8h sur P-Alpha
  AND Camille a en réalité travaillé 4h sur P-Alpha et 4h sur P-Gamma le mercredi 26/08/2026 (réunion non planifiée)
WHEN elle modifie la cellule du mercredi en saisissant 4h P-Alpha et 4h P-Gamma
  AND valide la semaine modifiée
THEN seul le mercredi reflète les données modifiées
  AND les autres jours conservent les valeurs de la proposition
  AND les imputations créées correspondent aux données finales confirmées (proposition + modifications)
  AND la proposition d'origine reste visible en grisé pour référence visuelle jusqu'à confirmation
```

### CA-3 (Alternatif) : Explicabilité — la proposition affiche les données qui la fondent

```gherkin
GIVEN le système génère une proposition de pré-remplissage pour la semaine de Camille
WHEN elle clique sur l'icône "Pourquoi cette proposition ?" à côté d'une ligne pré-remplie
THEN une infobulle ou panneau latéral affiche :
  - Le projet source et son code
  - La période d'affectation planifiée (dates début/fin, taux d'affectation %)
  - L'auteur et la date de création de l'affectation dans le planning
  AND aucune donnée confidentielle d'un autre collaborateur n'est exposée
  AND le lien vers l'affectation source est cliquable pour un chef de projet habilité (lecture seule pour le collaborateur)
```

### CA-4 (Erreur) : Proposition non confirmée — n'alimente aucun calcul ni rapport

```gherkin
GIVEN le système a généré une proposition de pré-remplissage pour la semaine du 24/08/2026
  AND Camille n'a effectué aucune action de confirmation (ni "Confirmer", ni modification + soumission)
WHEN un chef de projet consulte les rapports d'activité de la semaine 35
THEN les données de Camille n'apparaissent pas dans les rapports (aucune imputation confirmée)
  AND aucun calcul de taux d'occupation ou de coût projet n'inclut la proposition non confirmée
  AND le tableau de bord de complétude (US-058) indique Camille en "Saisie manquante" pour la semaine 35
  AND la proposition est stockée avec le statut "PROPOSITION" — jamais avec le statut "VALIDÉ" ou "SOUMIS"
```

### CA-5 (Erreur) : Absence de données de planning — proposition vide sans erreur

```gherkin
GIVEN Camille n'a aucune affectation planifiée pour la semaine du 31/08/2026
  AND le module de planification (US-037) ne retourne aucune donnée pour cette semaine
WHEN elle ouvre la vue de saisie de la semaine du 31/08/2026
THEN la vue s'affiche vide, sans proposition pré-remplie
  AND un message contextuel indique "Aucune affectation planifiée pour cette semaine — saisissez manuellement"
  AND aucune erreur technique n'est affichée (pas de stack trace, pas de message d'erreur système)
  AND la saisie manuelle reste disponible normalement (EF-TMP-1, EF-TMP-2)
```

## Critères UI/UX

### Web
- La proposition est visuellement distincte de la saisie confirmée (fond de couleur différent, icône "proposition", opacité réduite).
- Le bouton "Confirmer la semaine telle quelle" est placé en position prominente (première action visible) pour éviter que le collaborateur n'ait à chercher le moyen le plus rapide.
- L'icône d'explicabilité (ENF-IA-1) est accessible sans interrompre le flux : infobulle non bloquante au survol ou clic, panneau latéral glissant sans recharger la vue.
- En cas de modification partielle, les cellules modifiées sont marquées d'un indicateur visuel (ex : point bleu) pour distinguer ce que le collaborateur a choisi de ce que l'IA avait proposé.

### Mobile
- Sur mobile, le bouton "Confirmer la semaine" est le premier élément visible après l'ouverture de la vue (positionnement en haut de l'écran ou dans la thumb zone basse selon la disposition).
- L'explicabilité est accessible via tap sur l'icône, avec bottom-sheet de 50 % de la hauteur d'écran (non bloquant).
- La modification de la proposition se fait en tapant directement sur la cellule à modifier (même interaction que la saisie manuelle US-052).

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

**RG-TMP-4** : le statut "PROPOSITION" est distinct du statut "SOUMIS" en base de données. Aucune requête sur les rapports, valorisations ou indicateurs de complétude ne doit inclure des enregistrements de statut "PROPOSITION". Cette règle est testée par un test d'intégration dédié (requête API de synthèse + assertion sur l'absence de données non confirmées).

**ENF-IA-1 (Explicabilité)** : le modèle ou le moteur de règles qui génère la proposition doit exposer, pour chaque ligne pré-remplie, les identifiants sources utilisés (ID affectation, période, taux). Ces métadonnées sont stockées avec la proposition et affichées à la demande (pas de boîte noire).

**ENF-IA-2 (Non-substitution)** : le système ne peut jamais créer, modifier ou supprimer une imputation sans action explicite du collaborateur. L'IA est un assistant de saisie, pas un agent autonome.

**HAB-5** : les projets proposés dans le pré-remplissage sont filtrés sur les affectations actives du collaborateur (RG-TMP-1). Aucun projet hors périmètre n'est proposé, même s'il apparaît dans des données de planning agrégées.

**Dépendance EPIC-010** : si le socle IA n'est pas disponible au moment du sprint 2, la fonctionnalité se dégrade gracieusement en pré-remplissage par règles déterministes (copie des affectations du planning sans modèle ML), ce qui satisfait EF-TMP-9 et RG-TMP-4.
