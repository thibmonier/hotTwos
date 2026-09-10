# US-081: Cartographie des parcours par persona (P1–P6)

## Métadonnées
- **ID**: US-081
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 19
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P1–P6
- **Créé le**: 2026-09-10
- **Mis à jour**: 2026-09-10

## Traçabilité
- **Implémente**: EPIC-013 (C2 — Cartographier les parcours par persona)
- **Dépend de**: US-080 (référentiel des pages cibles — les nœuds du diagramme référencent les IDs de `page-inventory.md`)
- **Alimente**: US-083 (le mapping composants priorise les pages les plus traversées par les parcours), US-085 (les ruptures révèlent les priorités MoSCoW)

## User Story

**En tant que** concepteur UX,
**je veux** cartographier, pour chacun des 6 personas (P1–P6), le parcours principal allant du *job-to-be-done* jusqu'à la séquence d'écrans HotOnes, sous la forme de diagrammes Mermaid,
**afin de** révéler les ruptures de navigation, les pages orphelines et les besoins non couverts — permettant une conception centrée sur les usages réels plutôt que sur la structure applicative.

## Contexte (Conversation)
Les 6 personas (`personas.md`) sont définis avec leurs objectifs, frustrations et scénarios d'utilisation clés.
Le référentiel de pages (US-080) liste toutes les pages disponibles. Cette US les croise : pour chaque
persona, elle trace le chemin emprunté pour accomplir son job-to-be-done principal, en référençant
les pages du référentiel à chaque étape.

Les diagrammes Mermaid sont stockés dans `project-management/architecture/parcours-personas.md`
(un fichier unique avec une section par persona, ou des fichiers séparés si la lisibilité l'exige).
Chaque diagramme identifie :
- les pages traversées (avec ID de `page-inventory.md`) ;
- les décisions de navigation (branchements conditionnel) ;
- les points de rupture (pages manquantes, actions impossibles, allers-retours inutiles) ;
- les pages orphelines (non atteintes par aucun parcours persona).

Points à clarifier :
- Se limiter au parcours principal de chaque persona (1 diagramme principal + 1 variante max si utile).
- Les ruptures et pages orphelines sont annotées directement sur le diagramme ou dans un tableau récapitulatif associé au fichier.
- Accessibilité WCAG 2.2 AA : les ruptures sur le parcours de P1 (saisie de temps) sont signalées comme prioritaires (critère de rejet : saisie > 2 min).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : 6 parcours principaux documentés
```gherkin
GIVEN le référentiel `page-inventory.md` (US-080) et le fichier `personas.md`
WHEN le concepteur produit les diagrammes de parcours
THEN un diagramme Mermaid `flowchart` est présent pour chacun des 6 personas (P1–P6)
  AND chaque diagramme part du job-to-be-done principal du persona
      (ex. P1 : « Saisir mon temps en < 2 min » ; P3 : « Arbitrer les affectations sur 12 semaines »)
  AND chaque nœud référence l'ID de page correspondant dans `page-inventory.md`
```

### CA-2 (Nominal) : ruptures et pages orphelines identifiées
```gherkin
GIVEN les 6 diagrammes de parcours produits
WHEN le PO consulte les livrables
THEN toute rupture de parcours (page manquante, action sans écran, aller-retour non justifié)
     est signalée par une annotation explicite dans le diagramme ou un tableau récapitulatif dédié
  AND toute page du référentiel non atteinte par au moins un parcours persona
     est listée comme « page orpheline »
```

### CA-3 (Alternatif) : variante de parcours pour un scénario secondaire
```gherkin
GIVEN le persona P3 (Sophie — Resource Manager) dont l'usage couvre deux scénarios
      (arbitrage hebdo ET simulation d'une nouvelle affaire)
WHEN le concepteur documente les parcours de P3
THEN le diagramme P3 comporte une branche conditionnelle ou un diagramme secondaire
     couvrant la variante « simulation »
  AND les deux branches référencent des pages existantes de `page-inventory.md`
```

### CA-4 (Alternatif) : cohérence avec les scénarios d'utilisation clés des personas
```gherkin
GIVEN le scénario d'utilisation clé de chaque persona dans `personas.md`
WHEN le PO compare les diagrammes avec les scénarios correspondants
THEN les étapes décrites dans les scénarios personas se retrouvent dans les nœuds
     des diagrammes (pas de divergence non justifiée)
  AND le critère de rejet de chaque persona est documenté en annotation sur le diagramme
      (ex. P1 : « REJET si saisie > 2 min » ; P3 : « REJET si plan de charge irréaliste »)
```

### CA-5 (Erreur) : persona sans parcours
```gherkin
GIVEN l'un des 6 personas (P1 à P6) sans diagramme de parcours dans le livrable
WHEN le PO effectue la revue de validation
THEN il refuse la validation et retourne la story en révision
  AND la story ne peut être marquée Done que lorsque les 6 parcours sont présents et validés
```

### CA-6 (Erreur) : nœud sans correspondance dans le référentiel de pages
```gherkin
GIVEN un nœud de diagramme Mermaid dont l'ID de page ne correspond à aucune entrée de `page-inventory.md`
WHEN le PO ou le concepteur effectue la revue de cohérence
THEN l'incohérence est signalée et corrigée avant validation
  AND soit la page manquante est ajoutée à `page-inventory.md` (US-080 amendé),
      soit le nœud du diagramme est rectifié pour référencer une page existante
```

## Notes sur les livrables
- **Livrable** : `project-management/architecture/parcours-personas.md` (sections P1 à P6) ou fichiers séparés dans `project-management/architecture/parcours/`.
- **Format** : diagrammes Mermaid `flowchart TD` ou `flowchart LR` intégrés dans Markdown ; nœuds étiquetés `[ID-PAGE : Nom de la page]` ; ruptures annotées par commentaire ou class Mermaid dédiée.
- **Tableau récapitulatif** : une table Markdown listant ruptures et pages orphelines, avec colonne « Impact » (bloquant / dégradé / cosmétique) et colonne « Priorité refonte ».
- **Accessibilité** : ruptures sur le parcours P1 (saisie de temps, lundi matin, mobile) marquées WCAG 2.2 AA — cibles ≥ 44 px, navigation clavier complète, pas d'action au survol.
- **Hors périmètre** : analyse des métriques d'usage réel (pas encore disponibles) ; parcours des intégrations système tierces.
- **Validation** : revue PO en fin de story ; cohérence vérifiée avec `page-inventory.md`.

## Definition of Ready
- [x] Description INVEST (borné : 1 diagramme principal par persona, variantes optionnelles ; 5 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Dépend d'US-080 (IDs de pages disponibles avant de démarrer)
- [x] Format Mermaid défini, emplacement des livrables précisé
- [x] Validation INVEST : Independent ✓ (démarrée après US-080) / Negotiable ✓ (nombre de variantes par diagramme adaptable) / Valuable ✓ (révèle ruptures et orphelins, base de priorisation) / Estimable ✓ (6 parcours × effort moyen, 5 pts) / Sized ✓ (≤ 8 pts) / Testable ✓ (présence des 6 diagrammes et cohérence IDs vérifiables)

## Definition of Done
- [ ] 6 diagrammes Mermaid (P1–P6) produits et syntaxiquement valides
- [ ] Chaque nœud référence un ID de page existant dans `page-inventory.md`
- [ ] Ruptures de parcours documentées (annotations ou tableau récapitulatif)
- [ ] Pages orphelines listées
- [ ] Critères de rejet de chaque persona annotés dans le diagramme correspondant
- [ ] Revue et validation PO formalisées
- [ ] Fichier(s) commités, PR mergée sur la branche de sprint 19
