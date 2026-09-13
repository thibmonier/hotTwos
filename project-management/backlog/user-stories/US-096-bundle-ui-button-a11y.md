# US-096: Composant Ui:Button accessible (focus visible) — socle tailsfadmin

## Métadonnées
- **ID**: US-096
- **EPIC**: EPIC-003 / transverse (enabler socle) — débloque le reskin EPIC-002
- **Sprint**: 23
- **Statut**: ✅ Done
- **Points**: 3
- **Persona**: P2/P3 (managers) + tous (utilisateurs clavier / lecteurs d'écran)
- **Créé le**: 2026-10-23 (kickoff S23)

## Traçabilité
- **Origine**: Rétrospective S22 — ACTION-1 (dette a11y transverse : focus non visible sur les boutons primaires pleins)
- **Défaut**: Tailwind v4 (Preflight) supprime l'outline par défaut ; les boutons `bg-brand-500` des écrans reskinnés n'ont pas de `focus:ring` → WCAG 2.4.7 (Focus Visible) non respecté
- **Corrigé ponctuellement**: US-095 (bouton « Enregistrer » de `/relances`)
- **Socle**: bundle tailsfadmin (v1.6.0, cf. US-087 StatCard/PageHeader) — modèle de livraison
- **Enabler**: fournit le bouton accessible réutilisé par US-097/098/099 (reskin EPIC-002)

## User Story
**En tant que** utilisateur au clavier / lecteur d'écran (et développeur du parcours),
**je veux** un composant bouton unique du socle, avec focus visible et variantes sémantiques,
**afin de** disposer de boutons cohérents et accessibles (WCAG 2.2 AA) sur l'ensemble du parcours, sans dupliquer les classes écran par écran.

## Contexte (Conversation)
La dette a11y relevée en rétro S22 vient de la duplication manuelle des boutons (`<button class="bg-brand-500 … hover:bg-brand-600">`) sans style de focus. Un composant `tsf:Ui:Button` du bundle, portant `focus:ring` par défaut et les variantes (primary / success / error / secondary), corrige la classe entière d'un coup et évite la récurrence. Livraison selon le pattern « dev-dans-le-bundle » (US-087) : composant + release bundle + bump app + adoption.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : bouton primaire accessible
```gherkin
GIVEN un bouton d'action primaire du parcours
WHEN il est rendu via le composant tsf:Ui:Button (variant primary)
THEN il porte un focus visible au clavier (anneau focus contrasté, non supprimé par Preflight)
  AND une cible tactile suffisante (hauteur ≥ 44px) et un contraste AA
```

### CA-2 (Alternatif) : variantes sémantiques
```gherkin
GIVEN une action de validation, de refus ou secondaire
WHEN j'utilise le variant success / error / secondary
THEN le bouton porte les tokens tailsfadmin correspondants (texte + couleur, pas la couleur seule)
  AND le focus visible et l'état disabled sont gérés de façon cohérente
```

### CA-3 (Erreur) : adoption sans régression
```gherkin
GIVEN les écrans déjà reskinnés (absences, complétude, validation, relances)
WHEN leurs boutons primaires sont portés sur tsf:Ui:Button
THEN les hooks Stimulus et actions (submit/click) sont préservés (tests existants verts)
  AND le job a11y CI (US-093) ne signale plus de violation 2.4.7 sur ces routes
```

## Notes sur les livrables
- Composant `tsf:Ui:Button` dans le bundle tailsfadmin (variants + `focus:ring` par défaut + `disabled`), release + bump Composer côté hotTwos (cf. séquençage US-087).
- Adoption sur les boutons primaires des écrans reskinnés ; hooks Stimulus préservés.
- **Accessibilité** : WCAG 2.2 AA (2.4.7 Focus Visible, 1.4.3 Contraste, 2.5.8 cible) — vérifiée par le job US-093.

## Definition of Ready
- [x] Défaut a11y identifié (rétro S22) ; pattern bundle connu (US-087) ; job a11y en place (US-093)
- [x] INVEST : Valuable ✓ (dette a11y + enabler) / Estimable ✓ (3 pts) / Testable ✓

## Definition of Done
- [ ] Composant tsf:Ui:Button livré (bundle + release + bump app) avec focus visible + variantes + disabled
- [ ] Adopté sur les boutons primaires des écrans reskinnés (hooks préservés, tests verts)
- [ ] WCAG 2.4.7 attesté (job a11y US-093, 0 violation focus) ; CI verte ; PR mergée
