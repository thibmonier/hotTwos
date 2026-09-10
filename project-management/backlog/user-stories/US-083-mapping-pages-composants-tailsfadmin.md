# US-083: Mapping pages ↔ composants tailsfadmin (+ gaps)

## Métadonnées
- **ID**: US-083
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 19
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: Tous (P1–P6)
- **Créé le**: 2026-09-10
- **Mis à jour**: 2026-09-10 (affinage S19)

## Traçabilité
- **Implémente**: EPIC-013 (C4 — Mapper chaque page aux composants tailsfadmin et identifier les gaps)
- **Dépend de**: US-080 (référentiel des pages cibles), US-081 (parcours par persona — priorise les pages critiques pour le mapping)
- **Alimente**: US-085 (le backlog reskin intègre les gaps de composants pour pondérer l'effort), EPICs modules (chaque développeur front sait quel layout + composants utiliser pour un écran donné)

## User Story

**En tant que** Product Owner / concepteur UX,
**je veux** associer chaque page du référentiel à son layout tailsfadmin (`admin` ou `auth`) et à la liste des composants `<twig:tsf:…>` du bundle mobilisables, en identifiant explicitement les composants manquants (gaps) à créer côté bundle,
**afin de** garantir que chaque futur écran est « une greffe sur un terrain stable » — sans dérive de style, sans composant inventé hors design system — et de constituer un backlog actionnable de demandes d'évolution vers `tailsfadmin-bundle`.

## Contexte (Conversation)
Le socle de thème est externalisé dans `tailsfadmin/tailsfadmin-bundle` (ADR-0023, US-086 livrée,
**mergée sur `main`, bundle v1.4.2**). Liste des composants **confirmée en affinage S19** (inspection de
`vendor/tailsfadmin/tailsfadmin-bundle/templates/components/`) — syntaxe réelle **`<twig:tsf:<Espace>:<Composant>>`** :
- **Ui** : `<twig:tsf:Ui:Alert>`, `Avatar`, `Badge`, `Button`, `Card`, `MediaCard`, `GridImage`, `Dropdown`, `Modal`, `Preloader`, `ProgressBar`, `Ribbon`, `Table`, `TableAdvanced`, `Tabs`, `Video`
- **Form** : `<twig:tsf:Form:Input>`, `InputGroup`, `Select`, `Textarea`, `Checkbox`, `Radio`, `Toggle`, `Datepicker`, `Upload` (+ form theme Symfony)
- **Layout** : `<twig:tsf:Layout:Header>`, `Sidebar` (prop `mini`), `Breadcrumb`
- **Chart** : `<twig:tsf:Chart:Line>`, `Bar`, `VectorMap`  ·  **Calendar** : `<twig:tsf:Calendar>`
- **Layouts** : `@Tailsfadmin/layout/admin.html.twig` (sidebar/header surchargeables) et `auth`
- **Contrôleurs Stimulus** associés : `tailsfadmin--{tabs,clipboard,kanban,sidebar,submenu,modal,dropdown,theme,otp,datepicker,dropzone,apexcharts,calendar,vectormap}`

Cette US produit une table de mapping dans `project-management/architecture/page-component-mapping.md` :
une ligne par page du référentiel (US-080), avec le layout cible et les composants tailsfadmin
mobilisables pour chaque section de la page. Lorsqu'un composant n'existe pas dans le bundle,
un « gap » est créé : description fonctionnelle, page(s) concernée(s), priorité estimée (Must/Should/Could
pour le bundle). Les gaps constituent un backlog d'évolution formalisé (issues potentielles tailsfadmin).

Décisions d'affinage (S19) — arrêtées :
- **Référentiel de composants confirmé** (cf. Contexte ci-dessus) : le « à confirmer » est **levé**. Tout composant du mapping doit appartenir à cette liste réelle ; sinon → entrée obligatoire en section Gaps.
- **Structure d'un gap** : nom proposé, usage fonctionnel, page(s) concernée(s), priorité bundle (Must/Should/Could), exigences WCAG 2.2 AA le cas échéant.
- **Parité tactile** : signalée dans le gap si le composant (existant ou à créer) n'est pas utilisable au tactile.
- **Granularité** : une ligne par page ; composants d'une même page listés dans la cellule (pas une ligne par composant).

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
- **Composants réels du bundle** (confirmés en affinage S19, `main` v1.4.2) : `Ui:Alert/Avatar/Badge/Button/Card/MediaCard/GridImage/Dropdown/Modal/Preloader/ProgressBar/Ribbon/Table/TableAdvanced/Tabs/Video`, `Form:Input/InputGroup/Select/Textarea/Checkbox/Radio/Toggle/Datepicker/Upload`, `Layout:Header/Sidebar/Breadcrumb`, `Chart:Line/Bar/VectorMap`, `Calendar` ; layouts : `admin` (sidebar + header surchargeables), `auth`.
- **Accessibilité** : les gaps signalent si un composant manquant a des exigences WCAG 2.2 AA critiques (ex. modal non piégeable au clavier → gap priorité Must ; datepicker sans navigation clavier → gap priorité Must).
- **Hors périmètre** : implémentation des gaps (portée par des sprints bundle ultérieurs) ; mapping des comportements Stimulus (JS — documenté séparément si besoin).
- **Validation** : revue PO + vérification technique que les composants listés existent bien dans le bundle sur `main` (v1.4.2).

## Definition of Ready
- [x] Description INVEST (borné : mapping de `page-inventory.md` sur le bundle actuel ; 3 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] **Liste des composants bundle CONFIRMÉE** (affinage S19, `main` v1.4.2) — « à confirmer » **levé**
- [x] **Condition d'entrée** : `page-inventory.md` (US-080) **et** `parcours-personas.md` (US-081) validés PO. ⏳ *À lever au démarrage effectif.*
- [x] Validation INVEST : Independent ✓ (démarrée après US-080 + US-081) / Negotiable ✓ (granularité du mapping adaptable) / Valuable ✓ (ancre la conception sur le design system réel, élimine les dérives de style) / Estimable ✓ (3 pts — mapping tabulaire sur ~30 pages) / Sized ✓ (≤ 8 pts) / Testable ✓ (chaque ligne vérifiable : layout présent, composants existent ou gap explicite)
- **DoR : ✅ LEVÉE sous réserve des conditions d'entrée (US-080 + US-081 validées)**

## Definition of Done
- [ ] `project-management/architecture/page-component-mapping.md` créé et commité
- [ ] Toutes les pages du référentiel (US-080) présentes dans le mapping avec layout + composants ou gap
- [ ] Aucun composant hypothétique sans entrée de gap correspondante
- [ ] Section « Gaps » complète : nom, usage, pages concernées, priorité bundle (Must/Should/Could)
- [ ] Revue PO et vérification technique (composants réels confirmés sur le bundle)
- [ ] PR mergée sur la branche de sprint 19
