# Sprint Review — Sprint 7 (Design system, EPIC-012)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date review | 2026-09-03 |
| Sprint | 7 — « Design system posé et appliqué au lot 1 » |
| Capacité (prévision) | ~21 points (1 dev) |
| Animateur | Scrum Master |

## Sprint Goal

> « Le **design system définitif** (tokens accessibles) est **posé et appliqué** aux écrans du lot 1 : la
> charte fait autorité, les **maquettes UX/UI validées**, le **layout intégré** sans régression, et les
> écrans les plus utilisés (saisie, complétude, valorisation) **reskinnés** — figeant l'ergonomie définitive
> avant d'étendre le front. »

**Atteint : ✅ OUI** — avec un **pivot technologique majeur en cours de sprint** (ADR-0018 → **ADR-0019**) :
le socle n'est plus Bootstrap précompilé + thème Skote mais **Tailwind CSS v4 CSS-first** (binaire autonome,
**sans Node.js**, via AssetMapper ; composants TailAdmin MIT). L'intention du Goal est inchangée ; seule la
techno d'implémentation change. Layout et reskin **refaits en Tailwind**, backend/tests/tokens réutilisés.

## User Stories livrées (EPIC-012)

| ID | Titre | Points | Statut | Preuve |
|----|-------|--------|--------|--------|
| US-061 | Charte & design system (tokens, `@theme`, styleguide) | 5 | ✅ Livré | `tailwind.css`, axe-core 0 violation, PR #14 |
| US-062 | Conception UX/UI lot 1 (maquettes validées) | 5 | ✅ Livré | `ux-conception-lot1.md`, gate PO levé (2026-09-02) |
| US-063 | Intégration layout (sidebar/topbar, nav RBAC, responsive, thème) | 5 | ✅ Livré | `base.html.twig`, PR #14 |
| US-064 | Reskin des écrans livrés (saisie, complétude, valorisation, projets, validation…) | 8 | ✅ Livré | tous templates reskinnés, 417→424 tests verts |
| US-065 | Conformité contraste WCAG 2.2 AA (axe-core) | 5 | ✅ Livré | commit `d83f98f` |
| US-066 | Recette d'ergonomie & validation | 3 | ✅ Livré | recette menée, trace `.recette/`, findings → US-069/US-070 |

**Périmètre EPIC-012 livré : 31/31 points (100%).**

## Chantiers transverses absorbés (hors périmètre initial)

- **Pivot ADR-0018 → ADR-0019** : migration complète Bootstrap/Skote → **Tailwind v4** (poids 305 KB → 17 KB
  purgé, un seul système, risque licence Skote éliminé). Mergé (PR #14).
- **Sécurité** (PR #16) : 403 web habillée + `/validation` deny-by-default (`ensureCan(VALIDATE_TIME)`),
  non-fuite du slug de permission.
- **Design refresh lot 2 + ergonomie de saisie** (PR #17) : Heroicons, ombres, carte vedette ; saisie en
  heures décimales + totaux jour/projet/semaine (recette US-066).
- **Resync `design-system.md`** vers Tailwind v4 (PR #19) + **revue de clôture** `symfony-reviewer` (88/100).
- **US-069** — correctifs ergonomie mineurs (nav, en-têtes complétude, aria-modal, repli projet) — **2 pts, livré** (PR #20).

## User Stories non terminées / reportées

| ID | Titre | Points | Décision |
|----|-------|--------|----------|
| US-070 | Suites recette US-069 (findings : Dockerfile build, unité `/validation`, seed valorisation) | 3 | **Backloguée** (PR #21) → candidate Sprint 8 |

## Métriques

| Métrique | Valeur |
|----------|--------|
| Points planifiés (capacité) | ~21 |
| Points livrés (EPIC-012 + US-069) | **33** |
| Tests automatisés | 417 → **424 verts** |
| Qualité | PHPStan max 0 erreur · Deptrac 0 violation · gitleaks clean |
| Accessibilité | axe-core **0 violation** (9 écrans, clair) ; contrastes AA (US-065) |
| Findings recette | 5 — F1 corrigé · F2/F3/F-INFRA-1 backlogués (US-070) |
| PR mergées | #14, #16, #17, #18, #19, #20, #21 (+ #11 ouverture chantier) |

> Le sprint a **dépassé la capacité prévue** malgré un pivot techno en cours de route : le pivot a « refait »
> US-063/064, mais tokens, maquettes, backend, tests et méthodo axe-core ont été réutilisés, limitant le surcoût.

## Démonstration (parcours réels, données peuplées)

1. **Design system Tailwind** — styleguide, thème clair/sombre, tokens `@theme`.
2. **Saisie reskinnée** (`/saisie`) — heures décimales, totaux, dialog synthèse (aria-modal).
3. **Complétude** (`/completude`) — grille peuplée, en-têtes « Sem. 33 » humanisés, badges de statut.
4. **Validation** (`/validation`) — imputations en attente, boutons Valider/Refuser, nav sans double-actif.
5. **Sécurité** — 403 habillée sur accès non autorisé, `/validation` deny-by-default.

## Feedback & impact backlog

### Points forts
- Design system **unifié et durable** (un seul système, purge JIT, licence MIT).
- Ergonomie de saisie nettement améliorée (heures décimales, totaux).
- Accessibilité AA centralisée dans les tokens.

### À améliorer / findings à traiter (→ US-070)
- **Valorisation non démontrable** sur le seed démo (pas de profils/tarifs → CA 0 €) — lien avec **US-060** (valorisation automatique, reliquat S4).
- **Unité `/validation`** en minutes vs heures décimales en saisie (incohérence).
- **Build `make up`** cassé (Dockerfile sans `tailwind:build` avant `asset-map:compile`).

### Impact sur le backlog
| Action | US | Description |
|--------|-----|-------------|
| Créée | US-069 | Correctifs ergonomie (livrée) |
| Créée | US-070 | Findings recette (backlog S8) |
| À prioriser | US-060 | Valorisation automatique (reliquat S4, lié au finding F2) |

## Prochaines étapes

1. **Rétrospective S7** (`/workflow:retro`).
2. **Sprint 8** : périmètre combiné finance/valorisation + auth/profil (US-060, US-070, US-067, US-068) sous réserve de capacité.
3. Traiter **F-INFRA-1** (build Dockerfile) tôt dans le S8 — impacte l'outillage de dev.
