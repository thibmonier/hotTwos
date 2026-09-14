# Sprint Review — Sprint 23

**Thème** : Ouverture du reskin EPIC-002 (Projets) + enabler d'accessibilité `Ui:Button`
**Sprint Goal** : Ouvrir le reskin EPIC-002 par un incrément fini — composant bouton accessible du socle, puis reskin liste & fiche projet, et build du dashboard projets — offrir à P2 un parcours de pilotage cohérent, accessible et prêt à s'étendre.
**Date** : 2026-11-06

---

## Atteinte du Sprint Goal

**Verdict : ✅ Atteint**

Les quatre User Stories engagées (13/13 points) sont livrées et mergées sur `main` avec une Definition of Done complète, et les deux chantiers techniques de la rétrospective S22 sont réalisés. Le sprint applique l'action-clé de la rétro S22 : **ouvrir un nouveau front par un incrément fini** (≤ 3 écrans) et **traiter la dette d'accessibilité de façon structurelle** (composant du bundle plutôt qu'au cas par cas).

- **Enabler a11y** : le composant `tsf:Ui:Button` du bundle porte désormais un focus visible (WCAG 2.4.7) — bundle **v1.6.1** publié — et les boutons primaires des écrans reskinnés (absences, complétude, validation, relances) ont un focus explicite. La **dette de focus transverse** relevée en S22 est soldée.
- **EPIC-002 (Projets)** : la **liste** (filtre/recherche), la **fiche** (846 lignes portées, repère de cycle de vie) et un **dashboard de pilotage** (KPI + alertes de dérive) sont sur le socle tailsfadmin.

L'incrément est fini et cohérent : le parcours de pilotage projet P2 est homogène avec le reste de l'application. Le reskin EPIC-002 pourra s'étendre (clients, finance) au Sprint 24.

---

## US du Sprint 23

| ID | Titre | PR | Verdict | Tests |
|---|---|---|---|---|
| US-096 | Composant `Ui:Button` accessible (focus visible) — socle | bundle #41 (v1.6.1) · app #146 | ✅ | Suites écrans reskinnés vertes ; focus sur boutons primaires (absence/complétude/validation ; relances via US-095) |
| US-097 | PG-PRJ-01 — reskin liste des projets | #147 | ✅ | `ProjectPageTest` (contrôles de filtre + reskin + gating `canCreate`) |
| US-098 | PG-PRJ-02 — reskin fiche projet (onglets + cycle de vie) | #148 | ✅ | `ProjectPageTest` (repère cycle de vie `aria-current`, absence de `status-badge`) ; suites pilotage vertes |
| US-099 | DSH-PRJ — dashboard projets (build) | #149 | ✅ | `ProjectDashboardTest` (KPI + état vide, 403 sans habilitation) |
| T-TECH-01 | `php-cs-fixer` au hook pre-commit | #145 | ✅ | Hook = miroir exact de la CI |
| T-TECH-02 | Stabiliser CodeQL `Analyze` | — (OPS) | ✅ | Cache `codeql-overlay-status-*` purgé ; check non-requis (default-setup) |

**Légende** : ✅ Livré DoD complète · ⚠️ Livré avec dette · ❌ Non livré

---

## Métriques Sprint 23

| Indicateur | Valeur |
|---|---|
| Points engagés | 13 pts (Must 8 + Should 5) |
| Points livrés (DoD stricte) | 13 pts |
| Vélocité Sprint 23 | 13 pts |
| Taux de complétion DoD stricte | 100 % |
| US livrées / engagées | 4 / 4 |
| PRs mergées | 5 hotTwos (#145, #146, #147, #148, #149) + 1 bundle (#41, release v1.6.1) |
| Chantiers rétro S22 réalisés | 2 / 2 (T-TECH-01, T-TECH-02) |

Vélocité 13 pts, dans la fourchette récente (S19–22 : 12–16), avec l'absorption du travail cross-repo (bundle) et des chantiers OPS.

---

## Dette & Suivis

### `tsf:Ui:Button` — pass-through des attributs Stimulus (Suivi S24)

Le composant `Ui:Button` ne relaie pas les attributs HTML additionnels (`data-action`, `data-*-target`). Les boutons **câblés Stimulus** des écrans reskinnés ont donc reçu le focus **directement** (au lieu d'être migrés vers le composant). Le composant sert déjà les boutons non câblés (ex. « Nouveau projet »).

**Action Sprint 24** : ajouter le rendu de `{{ attributes }}` au composant du bundle (release mineure), puis migrer les boutons câblés vers `tsf:Ui:Button`.

---

### CodeQL `Analyze` — instabilité d'infrastructure (à reconfirmer)

CodeQL est en **default-setup** (pas de workflow éditable) et **non requis** (échecs `Analyze` = `mergeStateStatus: UNSTABLE`, jamais bloquant). Le cache `codeql-overlay-status-*` (drapeau « analyse incrémentale échouée ») a été purgé pour relancer l'analyse incrémentale.

**Suivi** : reconfirmer la stabilité sur quelques exécutions ; sinon, passer en advanced-setup avec `continue-on-error` documenté.

---

### Reskin d'un gros template — imperfections dark-mode (mineur)

La migration par `sed` d'`show.html.twig` (846 lignes) a laissé quelques `hover:X dark:Y` où le `dark:` a perdu le contexte `hover:` (l'anneau/teinte s'applique en dark hors survol). Cosmétique, dark-mode uniquement, sans impact fonctionnel.

**Suivi** : nettoyage cosmétique opportuniste si on repasse sur la fiche.

---

### Arbitrage PO — base de décompte du solde d'absences (report S22 ACTION-5)

Toujours ouvert : le solde d'impact (US-091b) est en jours ouvrés, le compteur persisté en span calendaire. À trancher (harmoniser vs documenter l'écart).

---

## Démonstration

Parcours de pilotage projet P2, sur le socle tailsfadmin.

### 1. Liste des projets — US-097
- `/projets` (chef de projet). Table/carte tailsfadmin, badges de statut texte+couleur, **filtre par statut + recherche** (client, sans rechargement) ; bouton « Nouveau projet » via `tsf:Ui:Button` (focus visible) ; lien « Tableau de bord ». Collaborateur : pas de bouton de création.

### 2. Fiche projet — US-098
- `/projets/{id}`. Onglets `Tabs` + PageHeader ; **repère de cycle de vie** Client → Projet → Facturation avec étape courante (`aria-current`). Logique de pilotage (statut, avenants, atterrissage, clôture, réouverture) inchangée ; gating finance préservé.

### 3. Dashboard projets — US-099
- `/projets/tableau-de-bord` (habilitation finance). PageHeader + StatCards (projets suivis / en dérive / escalade N+1 / sans budget) + **table des projets en dérive** (charge/marge, lien vers la fiche). État vide clair si aucun projet en dérive ; 403 sans habilitation.

### 4. Accessibilité — US-096
- Focus clavier visible sur les boutons primaires de tout le parcours (composant `Ui:Button` v1.6.1 + boutons câblés) ; job a11y (US-093) sans violation 2.4.7.

---

*Sprint Review rédigée à partir des PRs mergées (#145–#149 + bundle #41) — 2026-11-06.*
