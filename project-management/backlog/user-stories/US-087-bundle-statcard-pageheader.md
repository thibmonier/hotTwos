# US-087: Bundle — composants `Ui:StatCard` (G1) + `Layout:PageHeader` (G4)

## Métadonnées
- **ID**: US-087
- **EPIC**: EPIC-003 (Temps & activité) — enabler socle
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: Transverse (socle réutilisé par tous les écrans)
- **Créé le**: 2026-09-12

## Traçabilité
- **Comble les gaps**: G1 `Ui:StatCard`, G4 `Layout:PageHeader` (`page-component-mapping.md` §4, `backlog-reskin-priorise.md` « Prérequis bundle »)
- **Débloque**: US-088 (DSH-COLLAB, G1+G4), US-092 (Complétude, G1)
- **Réfère**: maquettes `design-canvas/lot2-saisie/Main.dc.html` (StatCard + PageHeader matérialisés) ; ADR-0023 (socle tailsfadmin externalisé)

## User Story
**En tant que** développeur front (au nom de tous les écrans),
**je veux** deux composants réutilisables `Ui:StatCard` et `Layout:PageHeader` dans le bundle `tailsfadmin`,
**afin de** porter les dashboards et en-têtes de page sans réinventer ces motifs à chaque écran.

## Contexte (Conversation)
L'audit et le mapping (US-083) ont identifié G1/G4 comme **absents du bundle** mais mobilisés par presque tous
les écrans Must. Décision PO (2026-09-12) : les développer **dans le bundle externe**
`github.com/thibmonier/tailsfadmin`, publier une release et **bumper la version** dans hotTwos (pas de composant
local dupliqué). Les maquettes S20 (`lot2-saisie/`) fixent l'anatomie visuelle cible (tokens tailsfadmin).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : StatCard
```gherkin
GIVEN le bundle tailsfadmin
WHEN j'appelle <twig:tsf:Ui:StatCard label="..." value="..." icon="..." />
THEN le composant rend une carte KPI (icône + label + valeur) sur les tokens du bundle
  AND il supporte une variation optionnelle (delta/trend) et une barre de progression optionnelle
  AND il respecte le contraste WCAG 2.2 AA
```

### CA-2 (Nominal) : PageHeader
```gherkin
GIVEN le bundle tailsfadmin
WHEN j'appelle <twig:tsf:Layout:PageHeader title="..."> avec un slot d'actions
THEN le composant rend un bandeau d'en-tête (titre h1 + breadcrumb optionnel + slot actions)
  AND il s'intègre au layout admin existant
```

### CA-3 (Alternatif) : réutilisabilité / adoption
```gherkin
GIVEN les deux composants publiés et la version bumpée dans hotTwos
WHEN un écran (ex. DSH-COLLAB) les utilise
THEN aucun style ad hoc hors bundle n'est nécessaire pour ces motifs
```

### CA-4 (Erreur) : props manquantes
```gherkin
GIVEN un appel StatCard sans valeur ni label
WHEN le template est rendu
THEN le composant échoue explicitement (prop requise) ou rend un état neutre documenté, sans casser la page
```

## Notes sur les livrables
- **Bundle** (`github.com/thibmonier/tailsfadmin`) : `Ui/StatCard` + `Layout/PageHeader` (Twig Component `tsf:`), tests, doc, release.
- **hotTwos** : bump de version Composer + vérification d'intégration.
- **Accessibilité** : contraste AA, focus, sémantique (h1 pour PageHeader).
- **Hors périmètre** : autres gaps (G2 Kanban, G3 FilterBar, G5 Timeline…).

## Definition of Ready
- [x] Anatomie cible fixée par la maquette `Main.dc.html` (StatCard + PageHeader)
- [x] Décision PO : dev dans le bundle externe + bump de version
- [x] INVEST : Independent ✓ / Negotiable ✓ / Valuable ✓ (débloque dashboards) / Estimable ✓ (3 pts) / Sized ✓ / Testable ✓

## Definition of Done
- [ ] `tsf:Ui:StatCard` + `tsf:Layout:PageHeader` livrés dans le bundle (tests + doc)
- [ ] Release bundle + version bumpée dans hotTwos
- [ ] Adoptés dans ≥ 1 écran (US-088)
- [ ] WCAG 2.2 AA vérifié ; PHPStan/CI verts
- [ ] PR mergée
