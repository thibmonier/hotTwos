# EPIC-012 : Intégration du design et de l'ergonomie définitive

## Métadonnées
- **ID**: EPIC-012
- **Statut**: 🔴 To Do
- **Priorité**: High — Must Have (MoSCoW)
- **Module**: UX (transverse)
- **Lot**: transverse (fast-track, à démarrer tôt — avant d'étendre le dev front)
- **MMF**: Un design system posé (charte + composants dérivés du thème Skote) et appliqué au layout + aux écrans déjà livrés du lot 1, avec des maquettes UX/UI validées, permettant de figer l'ergonomie définitive et de la valider auprès des utilisateurs.
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

---

## Description

Jusqu'ici, les écrans ont été construits en « walking skeleton » : fonctionnels mais **sans design
définitif** (HTML sémantique brut, style minimal). La recette a confirmé qu'aucune intégration de
design n'était planifiée dans le backlog — le placeholder `skyblue` du scaffold traînait encore
(F-S5-3, corrigé), les écrans de pilotage sont spartiates, et des écarts d'ergonomie/lisibilité ont
été relevés (F-S5-4 : collaborateurs indistinguables ; F-S5-5 : code couleur partiel).

Cet EPIC pose le **design définitif** et l'intègre sur le socle existant, avec trois entrées :

1. **Thème Skote** (`project-management/Skote_Symfony_v2.2.0/` — template admin Bootstrap) proposé comme
   base visuelle (layout, navigation, composants).
2. **Équipe UX/UI** (agents `ux-ergonome`, `ui-designer`, `accessibility-expert`, orchestrés par
   `uiux-orchestrator`) — conception et validation des maquettes, conformément à la consigne PO
   « phase de conception UX/UI avant tout dev front ».
3. **`design.md`** issu de *claude-design* (brief/spécification de design) comme source de la charte.

L'objectif est qu'un design soit **posé rapidement** pour **valider l'ergonomie définitive des écrans**
et la **facilité d'utilisation pour l'ensemble des utilisateurs** (collaborateur, chef de projet,
resource manager, administrateur, direction), avant d'étendre le développement front aux lots suivants.

---

## Objectifs Business

- **OBJ-7 (adoption)** — Une interface cohérente, lisible et agréable réduit la résistance à l'usage
  (saisie, pilotage) et conditionne l'adoption réelle du produit.
- **RSQ-1 (résistance à la saisie)** — Une ergonomie soignée renforce la contrepartie perçue par le
  collaborateur (cf. US-059) et sert directement l'objectif de complétude OBJ-1.
- **Cohérence multi-écrans** — Un design system unique évite la dette de style hétérogène au fil des
  modules et rend chaque nouvel écran « une greffe sur un terrain stable ».
- **Accessibilité comme critère produit** — Interface utilisable par tous (WCAG 2.2 AA visé),
  contrastes, cibles tactiles, navigation clavier, lecteurs d'écran.

---

## User Stories (créées le 2026-09-02)

| Réf. | ID | Nom | Intention | Points (est.) |
|------|----|-----|-----------|---------------|
| D1 | US-061 | Charte et design system (design.md → tokens + composants Skote) | Poser couleurs, typo, espacements, composants (boutons, tables, badges, formulaires, drawer) dérivés de Skote et du `design.md` | 5 |
| D2 | US-062 | Conception UX/UI des écrans du lot 1 (maquettes validées) | Équipe UX/UI : parcours + maquettes des écrans livrés (saisie, projets, complétude, absences, relances, valorisation), validées avant intégration | 5 |
| D3 | US-063 | Intégration du layout Skote sur le socle Twig/Stimulus | Layout de base (navigation, en-tête, responsive, thème clair/sombre), `base.html.twig` + assets, sans régression fonctionnelle | 5 |
| D4 | US-064 | Reskin des écrans livrés selon les maquettes | Appliquer le design system aux écrans existants (dont F-S5-4 noms collaborateurs, F-S5-5 code couleur complétude) | 8 |
| D5 | US-065 | Audit et mise en conformité accessibilité (WCAG 2.2 AA) | Contrastes, cibles ≥ 44 px (F-S5-1 déjà OK sur mobile), focus visible, ARIA, navigation clavier, lecteurs d'écran | 5 |
| D6 | US-066 | Recette d'ergonomie et validation utilisateurs | Tests d'utilisabilité sur les personas ; ergonomie définitive validée | 3 |

**Total indicatif : ~31 pts** (à affiner en affinage).

> Ordre conseillé : US-061 → US-062 → US-063 → US-064, puis US-065 et US-066.
> Prérequis PO : US-062 (conception) précède US-064 (reskin) — « UX/UI avant dev front ».

---

## Critères de Succès

### Critères bloquants
- [ ] Aucune régression fonctionnelle sur les écrans reskinnés (`make ci` vert, recette navigateur re-passée).
- [ ] Le placeholder de scaffold est éliminé et remplacé par la charte (F-S5-3 — déjà fait, à intégrer au design system).
- [ ] Accessibilité WCAG 2.2 **niveau AA** vérifiée sur les écrans du lot 1 (contraste, focus, cibles, ARIA).

### Critères fonctionnels
- [ ] Un `design.md` (charte) fait autorité et est référencé par les écrans.
- [ ] Layout Skote intégré : navigation cohérente, responsive (mobile ↔ desktop), thème clair/sombre respecté.
- [ ] Écrans lot 1 (saisie, /saisie/jour, projets, complétude, absences, relances, valorisation, organisation,
      profils, administration/périodes) conformes aux maquettes validées.
- [ ] F-S5-4 résolu (collaborateurs identifiables) et F-S5-5 traité (code couleur complet) dans le cadre du reskin.
- [ ] Maquettes UX/UI validées **avant** intégration (traçabilité de la validation).

### Critères non-fonctionnels
- [ ] Poids des assets maîtrisé (pas de dégradation du temps de chargement ; budget CSS/JS raisonnable).
- [ ] Design system documenté et réutilisable pour les modules des lots suivants.
- [ ] Pas d'accès aux fonctionnalités uniquement au survol (parité tactile).

---

## Progression

0/6 US (US-061 → US-066) · 0 % · ~31 points indicatifs (à confirmer en affinage)

---

## Dépendances

### Prérequis
- **EPIC-000** (socle Twig/Stimulus, AssetMapper, base.html.twig) — layout et pipeline d'assets.
- Correctif assets dev (PR #10) et base UI (PR #11) — la CSS se charge et le placeholder est retiré.
- Disponibilité du `design.md` (claude-design) et du thème Skote (présent dans le dépôt).

### Dépendants / synergies
- Chantier **F-S5-4** (`project-management/chantiers/CHANTIER-F-S5-4-completude-noms-collaborateurs.md`)
  à absorber dans D4 (conception UX/UI d'abord).
- Tous les modules front à venir (lots 2→5) réutilisent le design system posé ici.

---

## Notes

> **Fast-track** : l'objectif est de **poser vite** un design pour figer l'ergonomie, pas de tout
> reskiner d'un coup. Prioriser D1→D3 (charte + layout) puis D4 sur les écrans les plus utilisés
> (saisie, complétude).
>
> **Consigne PO (mémoire projet)** : la phase de conception UX/UI (maquettes validées : ux-ergonome +
> ui-designer + accessibility-expert) précède tout dev front — D2 est un prérequis de D4.
>
> **Sources design** : thème Skote `project-management/Skote_Symfony_v2.2.0/` (Admin / Documentation /
> Starterkit) ; brief `design.md` (claude-design) ; commande `/workflow:design`.
>
> **Dette absorbée** : findings recette Sprints 5/6 à composante visuelle — F-S5-3 (fait), F-S5-4
> (chantier), F-S5-5, et le rappel F1 (identifiants bruts). Voir `.recette/reports/`.
