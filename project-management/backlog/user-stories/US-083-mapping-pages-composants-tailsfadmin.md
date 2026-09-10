# US-083: Mapping pages ↔ composants tailsfadmin (+ gaps)

## Métadonnées
- **ID**: US-083
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 19
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: Tous (P1–P6)
- **Créé le**: 2026-09-10
- **Mis à jour**: 2026-09-10

## Traçabilité
- **Implémente**: EPIC-013 (C4 — Mapper chaque page aux composants tailsfadmin et identifier les gaps)
- **Dépend de**: US-080 (référentiel des pages cibles), US-081 (parcours par persona — priorise les pages critiques pour le mapping)
- **Alimente**: US-085 (le backlog reskin intègre les gaps de composants pour pondérer l'effort), EPICs modules (chaque développeur front sait quel layout + composants utiliser pour un écran donné)

## User Story

**En tant que** Product Owner / concepteur UX,
**je veux** associer chaque page du référentiel à son layout tailsfadmin (`admin` ou `auth`) et à la liste des composants `<twig:tsf:…>` du bundle mobilisables, en identifiant explicitement les composants manquants (gaps) à créer côté bundle,
**afin de** garantir que chaque futur écran est « une greffe sur un terrain stable » — sans dérive de style, sans composant inventé hors design system — et de constituer un backlog actionnable de demandes d'évolution vers `tailsfadmin-bundle`.

## Contexte (Conversation)
Le socle de thème est externalisé dans `tailsfadmin/tailsfadmin-bundle` (ADR-0023, US-086 livrée).
Le bundle expose un jeu de composants Twig UX réels :
`<twig:tsf:card>`, `<twig:tsf:table>`, `<twig:tsf:table-advanced>`, `<twig:tsf:chart>`,
`<twig:tsf:form:*>` (champs, select, textarea, checkbox…), `<twig:tsf:tabs>`,
`<twig:tsf:progress-bar>`, `<twig:tsf:modal>`, `<twig:tsf:dropdown>`, `<twig:tsf:calendar>`,
`<twig:tsf:clipboard>` — ainsi que le layout sidebar/header surchargeables.
(Liste à confirmer sur la branche `feature/us-086-migration-socle` avant démarrage.)

Cette US produit une table de mapping dans `project-management/architecture/page-component-mapping.md` :
une ligne par page du référentiel (US-080), avec le layout cible et les composants tailsfadmin
mobilisables pour chaque section de la page. Lorsqu'un composant n'existe pas dans le bundle,
un « gap » est créé : description fonctionnelle, page(s) concernée(s), priorité estimée (Must/Should/Could
pour le bundle). Les gaps constituent un backlog d'évolution formalisé (issues potentielles tailsfadmin).

Points à clarifier :
- Tous les composants référencés dans le mapping doivent exister réellement dans le bundle — tout composant
  hypothétique sans gap explicite sera refusé par le PO.
- Chaque gap doit inclure : nom proposé pour le composant, usage fonctionnel, pages concernées, priorité
  bundle et exigences WCAG 2.2 AA le cas échéant.
- La parité tactile est mentionnée dans les gaps si le composant existant n'est pas accessible au tactile.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : toutes les pages mappées à un layout et des composants
```gherkin
GIVEN le référentiel `page-inventory.md` (US-080) et la liste des composants réels du bundle tailsfadmin
WHEN le concepteur produit la table de mapping
THEN chaque page du référentiel est associée à un layout explicite (`admin` ou `auth`)
  AND chaque section principale de la page référence au moins un composant `<twig:tsf:…>` existant
    OR signale un gap avec description fonctionnelle dans la section « Gaps »
```

### CA-2 (Nominal) : liste des gaps formalisée comme backlog d'évolution du bundle
```gherkin
GIVEN les gaps identifiés lors du mapping
WHEN le PO consulte le livrable
THEN une section « Gaps — demandes d'évolution tailsfadmin » liste chaque composant manquant
  AND chaque entrée de gap inclut : nom proposé, description de l'usage fonctionnel,
      page(s) concernée(s), priorité bundle (Must/Should/Could)
  AND la liste des gaps constitue un backlog actionnable pour l'équipe bundle
```

### CA-3 (Alternatif) : pages d'authentification correctement associées au layout auth
```gherkin
GIVEN les pages d'authentification (connexion, mot de passe oublié, invitation, reset)
     présentes dans le référentiel
WHEN le mapping est produit
THEN ces pages sont associées au layout `auth` (et non `admin`)
  AND les composants de formulaire tailsfadmin (`<twig:tsf:form:*>`) correspondants sont référencés
  AND aucun composant de la sidebar ou du header admin n'est listé pour ces pages
```

### CA-4 (Alternatif) : composant utilisé sur plusieurs pages correctement documenté
```gherkin
GIVEN le composant `<twig:tsf:card>` mobilisé par la majorité des pages de tableau de bord
WHEN le mapping est consulté
THEN ce composant apparaît dans la ligne de chaque page concernée
  AND le mapping reste lisible (une ligne par page, pas une ligne par composant)
  AND des variantes d'usage du composant (ex. card-stat, card-chart) sont distinguées si elles existent
```

### CA-5 (Erreur) : composant hypothétique référencé sans gap explicite
```gherkin
GIVEN un composant non encore disponible dans le bundle tailsfadmin
     (ex. un composant gantt, un composant de signature électronique)
WHEN le concepteur le référence dans le mapping sans créer d'entrée dans la section Gaps
WHEN le PO effectue la revue de validation
THEN il refuse la validation pour la ligne concernée
  AND tout composant absent du bundle doit être inscrit en section Gaps avant approbation
```

### CA-6 (Erreur) : page sans layout assigné
```gherkin
GIVEN une page du référentiel dont la colonne « Layout » est vide dans la table de mapping
WHEN le PO effectue la revue de validation
THEN il refuse la validation pour cette ligne
  AND la story ne peut être marquée Done tant qu'une page est sans layout explicite
```

## Notes sur les livrables
- **Livrable** : `project-management/architecture/page-component-mapping.md`
- **Format** : tableau Markdown (colonnes : ID page, Nom, Layout (`admin`/`auth`), Composants tailsfadmin, Gaps) ; une ligne par page ; les composants d'une même page séparés par des sauts de ligne ou des listes dans la cellule.
- **Composants réels du bundle** (à confirmer sur la branche feature/us-086 avant démarrage) : `card`, `table`, `table-advanced`, `chart`, `form:*`, `tabs`, `progress-bar`, `modal`, `dropdown`, `calendar`, `clipboard` ; layouts : `admin` (sidebar + header surchargeables), `auth`.
- **Accessibilité** : les gaps signalent si un composant manquant a des exigences WCAG 2.2 AA critiques (ex. modal non piégeable au clavier → gap priorité Must ; datepicker sans navigation clavier → gap priorité Must).
- **Hors périmètre** : implémentation des gaps (portée par des sprints bundle ultérieurs) ; mapping des comportements Stimulus (JS — documenté séparément si besoin).
- **Validation** : revue PO + vérification technique que les composants listés existent bien dans la branche feature/us-086.

## Definition of Ready
- [x] Description INVEST (borné : mapping de `page-inventory.md` sur le bundle actuel ; 3 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Dépend d'US-080 (périmètre des pages) et US-081 (priorisation par parcours persona)
- [x] Liste des composants bundle à confirmer sur la branche feature/us-086 avant démarrage
- [x] Validation INVEST : Independent ✓ (démarrée après US-080 + US-081) / Negotiable ✓ (granularité du mapping adaptable) / Valuable ✓ (ancre la conception sur le design system réel, élimine les dérives de style) / Estimable ✓ (3 pts — mapping tabulaire sur ~30 pages) / Sized ✓ (≤ 8 pts) / Testable ✓ (chaque ligne vérifiable : layout présent, composants existent ou gap explicite)

## Definition of Done
- [ ] `project-management/architecture/page-component-mapping.md` créé et commité
- [ ] Toutes les pages du référentiel (US-080) présentes dans le mapping avec layout + composants ou gap
- [ ] Aucun composant hypothétique sans entrée de gap correspondante
- [ ] Section « Gaps » complète : nom, usage, pages concernées, priorité bundle (Must/Should/Could)
- [ ] Revue PO et vérification technique (composants réels confirmés sur le bundle)
- [ ] PR mergée sur la branche de sprint 19
