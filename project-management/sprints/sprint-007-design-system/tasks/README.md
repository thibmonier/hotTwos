# Tâches — Sprint 7 (Design system, EPIC-012)

> Sprint **frontend/design pur** : pas de DB, pas d'API, **pas de Flutter** (projet Symfony web).
> Le template CRUD générique est adapté — types retenus : `[FE-WEB]` (Twig/CSS/Stimulus), `[UX]`
> (maquettes/validation), `[TEST]` (non-régression + a11y `axe-core`), `[OPS]` (AssetMapper/assets),
> `[REV]`, `[BE]` (dette rétro). Décomposition consolidée ici (pas de fichier par US).

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-061 | Charte & design system (tokens + composants) | 5 | 7 | ~19h | 🔲 |
| US-062 | Conception UX/UI — maquettes validées | 5 | 4 | ~10h | 🔲 |
| US-063 | Intégration du layout Skote | 5 | 6 | ~12h | 🔲 |
| US-064 | Reskin des écrans livrés | 8 | 8 | ~21h | 🔲 |
| — | Tâches techniques transverses | — | 2 | ~5h | 🔲 |

**Total : 27 tâches · ~67h** (10 j × ~6h focus solo ≈ 60h ; écart absorbé par US-062 pré-avancée en conception).

## US-061 — Charte & design system

| ID | Type | Tâche | Est. | Dépend de |
|----|------|-------|------|-----------|
| T-061-01 | FE-WEB | `assets/styles/tokens.css` (couleurs clair/sombre, typo Poppins, espacements, rayons, ombres) depuis `design-system.md` | 3h | — |
| T-061-02 | OPS | Intégrer Bootstrap/Skote **compilé** via AssetMapper + charger Poppins (Google Fonts) | 2h | — |
| T-061-03 | FE-WEB | Migrer `app.css` vers les tokens (table §6 charte) sans régression | 3h | T-061-01 |
| T-061-04 | FE-WEB | Composants tokenisés : boutons, badges, table, form, alerte, drawer | 4h | T-061-01 |
| T-061-05 | FE-WEB | Page **styleguide** (composants + bascule thème) | 3h | T-061-04 |
| T-061-06 | TEST | Contrastes WCAG AA (`axe-core` sur styleguide) + non-régression visuelle | 2h | T-061-05 |
| T-061-07 | DOC/REV | Doc + revue `ui-designer` / `accessibility-expert` | 2h | T-061-06 |

## US-062 — Conception UX/UI (maquettes validées)

| ID | Type | Tâche | Est. | Dépend de |
|----|------|-------|------|-----------|
| T-062-01 | UX | Finaliser les maquettes des écrans restants (absences, relances, projets, validation, organisation, profils, admin/périodes) | 4h | — |
| T-062-02 | UX | Déclinaisons mobile + états (nominal/vide/erreur/chargement/sans-permission) | 3h | T-062-01 |
| T-062-03 | UX | Registre de validation (traçabilité écran/version/date/validateur) | 1h | — |
| T-062-04 | UX/REV | Revue a11y amont (`accessibility-expert`) + **validation PO** des maquettes | 2h | T-062-01, T-062-02 |

> ⚠️ **Gate humain** : T-062-04 (validation PO) n'est pas automatisable et **conditionne** l'ouverture de US-064 écran par écran.

## US-063 — Intégration du layout Skote

| ID | Type | Tâche | Est. | Dépend de |
|----|------|-------|------|-----------|
| T-063-01 | FE-WEB | Layout Skote dans `base.html.twig` (en-tête / sidebar / contenu) | 3h | T-061-01 |
| T-063-02 | FE-WEB | Navigation filtrée par habilitation (parité RBAC serveur) | 2h | T-063-01 |
| T-063-03 | FE-WEB | Responsive (menu repliable) + **trancher breakpoint 640/768** | 2h | T-063-01 |
| T-063-04 | FE-WEB | Bascule thème clair/sombre (contrôleur Stimulus + persistance) | 2h | T-063-01 |
| T-063-05 | TEST | Non-régression fonctionnelle + recette navigateur | 2h | T-063-02, T-063-03, T-063-04 |
| T-063-06 | REV | Revue | 1h | T-063-05 |

## US-064 — Reskin des écrans livrés

| ID | Type | Tâche | Est. | Dépend de |
|----|------|-------|------|-----------|
| T-064-01 | FE-WEB | Reskin saisie (`timesheet/week`, `day`) | 3h | T-061-04, T-063-01, T-062-04 |
| T-064-02 | FE-WEB | Reskin complétude + **F-S5-4 (email)** + **F-S5-5 (code couleur)** | 3h | T-061-04, T-063-01, T-062-04 |
| T-064-03 | FE-WEB | Reskin valorisation | 2h | T-061-04, T-063-01, T-062-04 |
| T-064-04 | FE-WEB | Reskin projets (`index`/`new`/`show`) + validation temps | 3h | T-061-04, T-063-01, T-062-04 |
| T-064-05 | FE-WEB | Reskin absences + relances | 2h | T-061-04, T-063-01, T-062-04 |
| T-064-06 | FE-WEB | Reskin organisation + profils + admin/périodes | 3h | T-061-04, T-063-01, T-062-04 |
| T-064-07 | TEST | Non-régression fonctionnelle + états vide/erreur/chargement | 3h | T-064-01…06 |
| T-064-08 | REV | Revue `symfony-reviewer` + `accessibility-expert` | 2h | T-064-07 |

## Répartition par type

| Type | Tâches | Heures | % |
|------|--------|--------|---|
| FE-WEB | 16 | ~42h | 63% |
| UX | 4 | ~10h | 15% |
| TEST | 3 | ~7h | 10% |
| OPS | 2 | ~4h | 6% |
| BE (dette) | 1 | ~3h | 4% |
| REV/DOC | 1+ | inclus | — |

## Conventions

- **ID** : `T-<US>-<NN>` · **Taille** : 0.5–8h · **Statuts** : 🔲 🔄 👀 ✅ 🚫
- Chemin critique : `T-061-01 → T-063-01 → T-064-*`. US-062 (maquettes validées) **bloque** US-064.
- Voir `technical-tasks.md` (transverses) et `task-board.md` (kanban).
