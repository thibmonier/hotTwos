# Saisie quotidienne mobile (US-052)

Vue **web responsive mobile-first** de saisie du temps, alternative à la grille hebdomadaire desktop
(`/saisie`), pour le collaborateur en déplacement. Parité fonctionnelle avec US-050 (CA-2) sans
basculer en desktop. `EF-TMP-6`, `ENF-UX-3`.

## Choix : responsive vs natif, vue dédiée vs media query

- **Pas d'app native** (pas de Flutter dans le codebase) : le « mobile » est une vue web responsive
  (Symfony UX/Turbo/Stimulus). Une app native reste une évolution ultérieure.
- **Vue dédiée** `/saisie/jour/{date}` plutôt qu'une transformation CSS de la grille hebdo : une table
  HTML (`.timesheet`, jours en colonnes) ne se linéarise pas proprement sous 480px sans casser la
  sémantique (`<th scope>`). La vue jour a sa propre structure **liste de cartes** (une carte par
  projet). Les deux vues coexistent, avec un **lien réciproque** semaine ↔ jour sur tout viewport.
- **Aucune nouvelle route serveur** : la soumission réutilise l'API de saisie US-050
  (`POST /api/time-entries/week`, best-effort par cellule). Le contrôleur `TimesheetDayController` ne
  fait que présenter projets + imputations du jour (ARC-15).

## Interactions (Stimulus `timesheet-day`)

| Fonction | Comportement |
|----------|--------------|
| Total du jour | Somme des durées, affichée en direct (`Xh` / `XhYY`) au fil de la saisie. |
| Navigation jour | Flèches préc./suiv. (liens) **et** swipe horizontal (seuil franc, zone hors bords pour ne pas entrer en conflit avec le « retour » navigateur). Le swipe est un **raccourci** ; les flèches restent le chemin accessible (WCAG 2.5.1). |
| Reprise de la veille | Remplit les durées à partir du jour précédent (confirmation si la journée courante contient déjà des saisies). |
| Soumission | Un bouton « Enregistrer la journée » (thumb-zone, sticky bas) envoie le lot ; bouton désactivé pendant l'envoi (anti double-tap). |
| Offline (CA-4) | En cas d'échec réseau à la soumission, les saisies sont conservées en `localStorage` (clé par date) — **aucune perte** — et un bandeau `role="status"` informe. Au retour réseau (`online`), un bandeau propose la **resynchronisation en un tap**. |

## Accessibilité (ENF-UX-3, WCAG 2.2 AA)

- Cibles tactiles ≥ 44×44px (flèches, champs, bouton) ; `font-size ≥ 16px` sur les champs (anti-zoom
  iOS) ; `touch-action: manipulation`.
- `<meta name="viewport" content="width=device-width, initial-scale=1">` (ajouté en base) — zoom
  **non bloqué** (1.4.4). `lang="fr"` sur `<html>`.
- Labels associés (`for`/`id`), unité indiquée (« Durée (minutes) »), `inputmode="numeric"`.
- Statuts (offline / resync / soumission) annoncés via `aria-live="polite"`.
- Dégradation 320px : mobile-first, `box-sizing: border-box`, pas de scroll horizontal (pas de table).

## Tests

- `TimesheetDayPageTest` : rendu des projets et de l'imputation existante, structure mobile-first
  (contrôleur Stimulus, `inputmode`, alternative flèches au swipe, `<meta viewport>`), 401 anonyme.
- Parité fonctionnelle et offline/swipe validés manuellement (VoiceOver/TalkBack, DevTools 320/375/390)
  — non automatisables en test fonctionnel serveur.

## Limites connues / suite

- **Saisie en minutes** (parité stricte avec l'API et la vue hebdo). Le raffinement UX proposé — stepper
  ±15 min avec affichage « Xh Ymin » et autosave debounce — est une **évolution suivie** (JS plus lourd,
  hors MVP).
- **Sélecteur de projet** : tous les projets actifs sont listés ; un filtrage/repli bottom-sheet pour de
  longues listes viendra si le volume l'exige.
- **Résolution de conflit** offline multi-appareils : la resynchronisation ré-émet le lot (best-effort
  serveur) ; une stratégie de conflit fine relève du Tech Lead.

## Revue (T-052-06) — findings traités

Revues `symfony-reviewer` + `accessibility-expert` — **GO** après corrections.

- **[Critique — corrigé] Fuite d'écouteurs tactiles** (`symfony-reviewer`) : les listeners
  `touchstart/touchend` étaient ajoutés en `connect()` sans être retirés en `disconnect()` → doublons
  au retour Turbo. **Corrigé** : références conservées et retirées en `disconnect()`.
- **[Majeur — corrigé] Deux requêtes jour + veille** (`symfony-reviewer`) : consolidées en **une seule**
  `findForUserInRange(veille, jour)`, découpée en mémoire par `workDate()`.
- **[Majeur — corrigé] Style `:disabled`** (`accessibility-expert`) : `button:disabled` (opacity +
  `not-allowed`) pour un état lisible pendant l'envoi.
- **[Mineur — corrigé] `aria-busy`** (`accessibility-expert`) : posé sur le bouton pendant l'envoi
  (cohérent avec la vue hebdo).
- **[Bloquante — rejetée] Remplacer `window.confirm()` par `<dialog>`** (`accessibility-expert`) :
  `confirm()` natif **est** accessible au clavier (Entrée/Échap) et annoncé par les lecteurs d'écran ;
  un composant `<dialog>` dédié serait disproportionné pour une simple confirmation d'écrasement.
- **[Mineur — documenté] Couverture des tests** : le fonctionnel vérifie la structure/parité ; le
  total, l'offline localStorage, la resync et le swipe sont validés **manuellement** (non
  automatisables en test serveur).

**Conclusion : GO** — fuite mémoire et requête dupliquée corrigées, a11y AA (états `:disabled`/`aria-busy`).
