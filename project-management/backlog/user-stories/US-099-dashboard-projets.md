# US-099: DSH-PRJ — Dashboard projets (build)

## Métadonnées
- **ID**: US-099
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: 23
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P2 (chef de projet / manager) — point d'entrée pilotage
- **Créé le**: 2026-10-23 (kickoff S23)

## Traçabilité
- **Backlog reskin**: `backlog-reskin-priorise.md` — Should, DSH-PRJ (EPIC-002), 5 pts (**Build** natif sur socle)
- **Type**: Build (écran nouveau) — construit nativement sur le socle tailsfadmin
- **Gaps liés**: G1 `Ui:StatCard` + G4 `Layout:PageHeader` (au bundle, US-087)
- **Références de conception**: écrans **hotones (legacy)** + visuels `project-management/architecture/design-canvas/tailadmin-ref/` (`cards-kpi.png`, `cards-multistats.png`, `cards-revenue.png`, `monthly-target.png`, `graph-sales.png`) — inspiration KPI/alertes dérive
- **Modèle**: US-088 (DSH-COLLAB — dashboard build sur StatCard/PageHeader)
- **Socle**: `tsf:Ui:StatCard` (G1), `tsf:Layout:PageHeader` (G4), `tsf:Ui:Button` (US-096)

## User Story
**En tant que** chef de projet / manager (P2),
**je veux** un tableau de bord des projets avec les KPI clés et les projets en dérive,
**afin de** repérer d'un coup d'œil les projets à risque (dérive charge/marge, OBJ-2) et y accéder directement.

## Contexte (Conversation)
Point d'entrée P2 du module projets (pendant du DSH-COLLAB pour P1). Construit nativement sur le socle :
`PageHeader` (G4) contextuel, une rangée de `StatCard` (G1 : nb projets actifs, en dérive, marge moyenne,
budget consommé), et une **liste des projets en dérive/alerte** (lien direct vers la fiche PG-PRJ-02).
S'inspirer des dashboards **hotones (legacy)** et des cartes de référence `tailadmin-ref/` pour la hiérarchie
visuelle des KPI et des alertes. Cohérence stricte avec DSH-COLLAB (pas de dépendance à une librairie de
graphes côté client — se limiter aux StatCards + table d'alertes, comme DSH-COLLAB ; un `Chart` éventuel
serait une itération ultérieure). Habilitation : périmètre équipe/projets du responsable (voters existants).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : KPI + projets en dérive
```gherkin
GIVEN un chef de projet responsable de projets, dont certains en dérive (charge/marge)
WHEN j'ouvre le dashboard projets
THEN un PageHeader contextuel et une rangée de StatCard (actifs / en dérive / marge / budget) sont affichés
  AND une liste des projets en alerte de dérive donne un accès direct à chaque fiche projet
```

### CA-2 (Alternatif) : aucun projet en dérive
```gherkin
GIVEN aucun projet en dérive sur mon périmètre
WHEN j'ouvre le dashboard
THEN les StatCards s'affichent (compteurs à 0 le cas échéant) et un état vide clair remplace la liste d'alertes
```

### CA-3 (Erreur) : sans périmètre / sans habilitation
```gherkin
GIVEN un utilisateur sans projet sous sa responsabilité (ou non habilité)
WHEN il accède au dashboard projets
THEN l'accès est refusé/masqué conformément aux voters existants, ou un état vide non trompeur est affiché
```

## Notes sur les livrables
- Nouveau contrôleur + route (ex. `/projets/tableau-de-bord`) + template `templates/project/dashboard.html.twig` sur le socle (StatCard G1 + PageHeader G4 + table d'alertes + `tsf:Ui:Button`).
- Réutiliser les services de pilotage existants (marge/dérive charge — EPIC-002) pour alimenter KPI et alertes ; **pas de nouveau calcul métier** (agrégation de l'existant).
- Tests fonctionnels : rendu KPI + liste d'alertes (CA-1), état vide (CA-2), habilitation (CA-3).
- **Accessibilité** : WCAG 2.2 AA (StatCards labellisées, table accessible, focus, contraste) — vérifiée par le job US-093.

## Definition of Ready
- [x] Composants G1/G4 au bundle ; services dérive/marge EPIC-002 existants ; références de conception (hotones + tailadmin-ref) disponibles
- [x] INVEST : Valuable ✓ (entrée pilotage P2) / Estimable ✓ (5 pts) / Testable ✓

## Definition of Done
- [ ] Dashboard projets construit sur le socle (StatCard G1 + PageHeader G4 + alertes dérive) ; agrégation de l'existant (pas de nouveau calcul)
- [ ] États (vide, sans habilitation) couverts ; tests fonctionnels verts ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
