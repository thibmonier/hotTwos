# Sprint Review — Sprint 21

**Thème** : Reskin du parcours de saisie collaborateur P1 — EPIC-003 front
**Sprint Goal** : Porter le parcours de saisie sur le socle tailsfadmin — bundle G1/G4 comblés — accessible WCAG 2.2 AA, conforme aux maquettes validées S20
**Date** : 2026-09-12

---

## Atteinte du Sprint Goal

**Verdict : ⚠️ Partiellement atteint**

Le cœur du Sprint Goal est rempli. Le bundle tailsfadmin v1.6.0 comble les lacunes G1 (StatCard) et G4 (PageHeader), et cinq des six écrans du parcours collaborateur P1 sont portés sur le socle avec des PRs mergées sur `main`. Le dashboard collaborateur (DSH-COLLAB), la saisie hebdomadaire, la saisie du jour et la complétude équipe sont conformes aux maquettes validées en Sprint 20.

L'écran **Mes absences (US-091)** demeure partiellement livré : le reskin tokensé est mergé (PR #130), mais le calendrier des conflits fériés/fermetures/absences (CA-3 / ABS-03) est absent et la mise à jour dynamique du solde projeté à la sélection de dates (CA-2) est différée. Ces deux éléments figuraient explicitement dans les critères d'acceptation. Le Sprint Goal n'est donc pas satisfait dans sa totalité.

La conformité WCAG 2.2 AA est déclarée dans les CHANGELOGs et dans les templates (attributs `role`, `aria-*`, SVG inline), mais n'est pas vérifiable de façon outillée : aucun rapport axe-core ni pa11y n'est présent dans le repo. Ce risque est transversal à l'ensemble des US et devra être adressé avant la mise en production du parcours.

---

## US du Sprint 21

| ID | Titre | PR | Verdict | Tests |
|---|---|---|---|---|
| US-087 | Bundle Ui:StatCard (G1) + Layout:PageHeader (G4) — release v1.6.0 + bump app | Bundle #39, #40 · App #131 | ✅ | `StatCardTest`, `PageHeaderTest` (bundle) · `CollaboratorDashboardTest` (app) |
| US-088 | DSH-COLLAB — Dashboard collaborateur (routage / par profil) | #132 | ✅ | `CollaboratorDashboardTest` (2 cas) · `AuthWebTest` mis à jour |
| US-089 | Reskin saisie hebdomadaire (PG-TMP-01) | #128 | ⚠️ | Tests timesheet existants verts (48) — aucun test ajouté pour CA-2/CA-3/CA-4 |
| US-090 | Reskin saisie du jour (mobile) | #129 | ✅ | `TimesheetDayPageTest` (2 cas) |
| US-091 | Reskin Mes absences | #130 | ❌ | `AbsencePageTest` (2 cas) — CA-3 calendrier conflits absent, CA-2 partiel |
| US-092 | Reskin complétude (F-S5-4 / F-S5-5, StatCards, sticky) | #133 | ⚠️ | `CompletenessPageTest` (5 cas) — CPL-04 relance inline et CPL-05 filtre différés |

**Légende** : ✅ Livré DoD complète · ⚠️ Livré avec dette · ❌ Non livré (CA critiques manquants)

---

## Métriques Sprint 21

| Indicateur | Valeur |
|---|---|
| Points engagés | 18 pts |
| Points livrés (DoD stricte) | ~15 pts ¹ |
| Vélocité Sprint 21 | ~15 pts |
| Taux de complétion DoD stricte | ~83 % |
| US livrées / engagées | 5 / 6 |
| US avec dette légère | 3 (US-089, US-092 ; WCAG transversal US-087/088) |
| PRs mergées sur `main` | 8 (#39, #40 bundle · #128, #129, #130, #131, #132, #133 app) |

¹ En l'absence de grille d'estimation par US, la valeur de 15 pts est calculée sur la base d'une US-091 à ~3 pts non livrés sur 18 pts engagés. À affiner lors de la rétrospective.

---

## Dette & Suivis

### Transversal — Accessibilité WCAG 2.2 AA (toutes US)

La conformité est déclarée mais non attestée par outillage. Aucun test automatisé (axe-core, pa11y) ni rapport d'audit manuel n'est présent dans le repo pour les quatre écrans portés ce sprint.

**Action Sprint 22** : intégrer un job pa11y ou axe-core en CI sur les routes `/`, `/saisie`, `/saisie/jour`, `/absences`, `/completude` avant déploiement en staging.

---

### US-087 / US-088 — Dashboard collaborateur

- `CollaboratorDashboardTest.php` n'asserte pas la présence effective des composants `StatCard` dans le rendu HTML ; la couverture fonctionnelle de ces composants repose uniquement sur les tests unitaires du bundle.
- **Action** : enrichir `CollaboratorDashboardTest` avec une assertion sur le rendu (présence de 4 blocs StatCard et du PageHeader).

---

### US-089 — Reskin saisie hebdomadaire

Trois états introduits par le reskin ne sont couverts par aucun test fonctionnel :

| Critère d'acceptation | Statut test |
|---|---|
| CA-2 — totaux serveur (`grandTotal`, `dayTotals`, `objectivePercent`) | ❌ Aucune assertion dans les tests existants |
| CA-3 — conteneur `errorBanner` (dépassement > 24 h) | ❌ Activé par Stimulus JS côté client, non couvert côté serveur |
| CA-4 — lien « Vue jour (mobile) » vers `timesheet_day` | ❌ Présence non assertée |

**Action Sprint 22** : compléter `TimesheetPageTest.php` avec les 3 cas manquants, notamment un test de rendu du `data-timesheet-target="errorBanner"` dans le HTML.

---

### US-090 — Reskin saisie du jour (mobile)

- `targetMinutes` est câblé à 420 min (7 h) pour tout jour ouvré ; le régime temps-partiel de l'utilisateur (`WorkingDaysCalculator`) n'est pas pris en compte. Ce cas ne figurait pas dans les CA de US-090 mais constitue une limite connue.
- Le frontmatter du fichier `US-090-reskin-saisie-jour.md` indique encore `Statut: 🟢 Ready` (mise à jour documentaire manquée lors du merge).

**Actions** : (1) tracer en backlog le calcul de `targetMinutes` selon le régime utilisateur (dépendance EPIC-001) ; (2) corriger le frontmatter `US-090-reskin-saisie-jour.md` → `✅ Done`.

---

### US-091 — Reskin Mes absences — **Report Sprint 22 requis**

| Élément manquant | Criticité |
|---|---|
| CA-3 / ABS-03 — calendrier des conflits fériés/fermetures/absences avec alternative textuelle | Critique — était dans le scope Sprint 21 |
| CA-2 — mise à jour dynamique du solde projeté à la sélection de dates | Haute |
| ABS-04 — reformulation demi-journées en boutons radio | Basse (différée explicitement) |
| Frontmatter `US-091` encore `Statut: 🟢 Ready` | Documentation |

**Action** : rouvrir US-091 (ou créer US-091b) avec CA-3 et CA-2 comme Must-Have Sprint 22. Décision PO requise sur la priorité du calendrier des conflits (ABS-03) par rapport au reste du backlog EPIC-003.

---

### US-092 — Reskin complétude

| Élément différé | Criticité |
|---|---|
| CPL-04 — Relance inline avec sélection de collaborateurs (POST multi) | Haute — le bouton actuel redirige vers `/relances`, pas d'action inline |
| CPL-05 — Filtre / recherche par statut (JS) | Moyenne |
| Absence de test négatif assertant qu'aucun fragment UUID n'apparaît dans le rendu | Faible |

**Actions** : (1) planifier CPL-04 en Must-Have et CPL-05 en Should-Have Sprint 22/23 selon priorisation PO ; (2) ajouter `assertStringNotContainsString` sur un fragment UUID dans `testDisplayNameShownInsteadOfEmail`.

---

## Démonstration

L'ordre de démo suit le parcours collaborateur P1 de bout en bout, en référence aux maquettes validées lors du Sprint 20 (répertoire `project-management/architecture/design-canvas/`).

---

### 1. Dashboard collaborateur — US-088 (DSH-COLLAB)

- Connexion avec `camille@agence.test` (profil Collaborateur, non habilité `CREATE_PROJECT`).
- Vérifier le routage `/` → `home/dashboard.html.twig` (routage par profil dans `HomeController`).
- Observer : `Layout:PageHeader` affichant l'identité e-mail (sans UUID brut — F-S5-4 soldé), 4 `Ui:StatCard` (Heures cette semaine / En retard / Partielles / Soumises), lien « Saisir mes temps ».
- Connexion anonyme → home historique (régression négative validée).

---

### 2. Saisie hebdomadaire — US-089 (PG-TMP-01)

- Navigation vers `/saisie` (utilisateur connecté).
- Observer : tokens tailsfadmin (couleurs `brand-*`, `gray-*`, `theme-*`), `Ui:ProgressBar` d'objectif semaine, code projet en libellé secondaire, week-ends atténués avec label `sr-only`.
- Vérifier le lien « Vue jour (mobile) » vers `timesheet_day`.
- Vérifier le rendu de l'état vide « Aucun projet actif ».
- **Note démo** : le conteneur `errorBanner` (dépassement 24 h) est activé par Stimulus JS — ne pas démontrer sans jeu de données préparé côté serveur.

---

### 3. Saisie du jour (mobile) — US-090 (PG-TMP-02)

- Navigation vers `/saisie/jour` depuis la vue hebdomadaire ou URL directe, sur viewport mobile (≤ 640 px).
- Observer : `Ui:ProgressBar` objectif jour, `textarea` commentaire, navigation jour (liens prev/next), `offlineBanner` preservée (`data-timesheet-day-target`).
- Vérifier `inputmode="decimal"` sur les champs de saisie numérique.

---

### 4. Mes absences — US-091 ⚠️ Partiel

- Navigation vers `/absences` (profil Collaborateur).
- Observer : reskin tokensé (badges de statut avec SVG inline et couleur sémantique : validée / refusée / en attente — ABS-01), solde projeté affiché statiquement, avertissement RGPD conservé (ABS-06), attributs `aria-required` sur les champs du formulaire de demande (CA-4).
- **Point d'attention PO** : le calendrier des conflits fériés/fermetures/absences (ABS-03) est absent du template. La mise à jour dynamique du solde à la sélection de dates (CA-2) est également différée. Décision de report Sprint 22 à valider.

---

### 5. Complétude équipe — US-092 (F-S5-4 / F-S5-5 / StatCards / sticky)

- Navigation vers `/completude` (profil Manager).
- Observer : 4 `Ui:StatCard` (Total / En retard / Partielles / Soumises), colonne collaborateur `sticky left-0` lors du défilement horizontal, icônes SVG inline par état (`submitted` / `partial` / `empty_late` / `in_progress`) avec `aria-hidden="true"`, noms d'affichage au lieu de fragments UUID (F-S5-4 soldé).
- Vue Collaborateur : périmètre restreint à l'utilisateur connecté (F-S5-3).
- **Note démo** : le bouton « Relancer les retards » redirige vers `/relances` — la relance inline (CPL-04) est différée.

---

*Sprint Review rédigée à partir des verdicts de vérification indépendante — 2026-09-12.*
