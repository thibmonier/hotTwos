# US-079: Raffinements pilotage — export, courbe d'atterrissage & seuil par type

## Métadonnées
- **ID**: US-079
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: 14–15 (découpée : US-079a S14 ; US-079c + US-079b S15)
- **Statut**: ✅ Done
- **Points**: 8 (à découper : 3 sous-capacités)
- **Persona**: P2 (Marc — Chef de projet) / P6 (Direction)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-14 (export), EF-PRJ-15 (seuil par type + 2e seuil), EF-PRJ-16 (courbe)
- **Dépend de**: US-035, US-036 (atterrissage & dérive livrés)

## User Story

**En tant que** chef de projet (P2) et directeur (P6),
**je veux** exporter le tableau de pilotage, voir la **courbe d'atterrissage** dans le temps et **paramétrer le seuil de dérive par type de projet**,
**afin de** partager le suivi, comprendre *quand* la trajectoire s'est dégradée et adapter la sensibilité de l'alerte.

## Contexte (Conversation)

US-035/036 ont livré la MMF (avancement/RAF + atterrissage + alerte précoce). Restent trois raffinements
du CDC, tous *Should*, regroupés ici (à découper au planning si nécessaire) :
- **EF-PRJ-14 (export)** : les 5 valeurs (budget, consommé, RAF, atterrissage, écart) par lot/projet, exportables (CSV).
- **EF-PRJ-16 (courbe)** : historiser l'atterrissage à chaque calcul et en présenter l'évolution.
- **EF-PRJ-15 (seuil par type)** : rendre le seuil de dérive paramétrable **par type de projet** (aujourd'hui constantes OBJ-2 en dur) + **2e seuil** escaladant à la direction.

## Critères d'Acceptance (Confirmation)

### CA-1 (Export — EF-PRJ-14)
```gherkin
GIVEN un projet avec ses lots (budget, consommé, RAF, atterrissage, écart)
WHEN j'exporte le tableau de pilotage
THEN j'obtiens un fichier CSV contenant les 5 valeurs par lot et le total projet
```

### CA-2 (Courbe — EF-PRJ-16)
```gherkin
GIVEN un projet dont l'atterrissage a été recalculé sur plusieurs périodes
WHEN je consulte la courbe d'atterrissage
THEN je vois son évolution dans le temps (pas seulement la valeur courante)
```

### CA-3 (Seuil par type — EF-PRJ-15)
```gherkin
GIVEN un type de projet « forfait » avec un seuil de dérive de 8 % et « régie » à 15 %
WHEN la dérive d'un projet forfait dépasse 8 %
THEN l'alerte est émise selon le seuil du type (pas une constante globale)
```

### CA-4 (2e seuil direction — EF-PRJ-15)
```gherkin
GIVEN un 2e seuil (escalade) supérieur au 1er
WHEN la dérive dépasse ce 2e seuil
THEN l'alerte est également escaladée à la direction
```

## Definition of Done
- [x] **US-079a** Export CSV du tableau de pilotage (EF-PRJ-14) — gating HAB-1 sur les colonnes de coût — **livré S14 (PR #78)**
- [x] **US-079c** Historisation de l'atterrissage + visualisation de la courbe (EF-PRJ-16) — **livré S15 (PR #82)** — capture via **handler séparé** (`ComputeProjectMargins` inchangé)
- [x] **US-079b** Seuil de dérive **par type de projet** + 2e seuil direction (EF-PRJ-15) — **livré S15 (PR #83)**
- [x] Tests par sous-capacité ; `make ci` vert

## Note de découpage (S14)
La story parapluie a été livrée en tranches : **US-079a export** en S14 ; **US-079c courbe** et **US-079b
seuil par type** reportés au S15 (la courbe demande une historisation dans le handler de figeage de marge —
intégration disproportionnée pour une valeur Should).

## Notes
Story « parapluie » : au sprint planning, découper en 3 (export / courbe / seuil) selon la capacité.
Le seuil par type généralise la décision S12 (constantes 10 %/50 %) — cf. `ChargeLandingCalculator`.
