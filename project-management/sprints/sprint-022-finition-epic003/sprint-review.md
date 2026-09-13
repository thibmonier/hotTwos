# Sprint Review — Sprint 22

**Thème** : Finition & durcissement du parcours de saisie (EPIC-003) + WCAG en CI
**Sprint Goal** : Solder l'engagement du parcours de saisie — calendrier de conflits d'absences, relance inline de complétude, accessibilité WCAG 2.2 AA attestée en CI, reskin de la validation des temps et des relances — refermer proprement EPIC-003 avant d'ouvrir le reskin EPIC-002.
**Date** : 2026-10-23

---

## Atteinte du Sprint Goal

**Verdict : ✅ Atteint**

Les cinq User Stories engagées (12/12 points) sont livrées et mergées sur `main` avec une Definition of Done complète. Le sprint applique directement les actions de la rétrospective S21 : la dette engagée est soldée **avant** l'ouverture d'un nouveau front.

- La dette *Must* du Sprint 21 est résorbée : le **calendrier des conflits d'absences** (ABS-03) et le **solde projeté dynamique** (CA-2) sont livrés (US-091b), et l'**accessibilité WCAG 2.2 AA est désormais attestée en CI** — plus seulement déclarée — via un job axe/pa11y en mode rapport (US-093).
- La **relance inline de complétude** (CPL-04) et le **filtre/recherche** (CPL-05) referment l'écran de complétude (US-092b).
- Les écrans de **validation des temps** (US-094) et de **relances** (US-095) sont portés sur le socle tailsfadmin, complétant la cohérence visuelle du parcours.

**EPIC-003 front est fonctionnellement soldé.** `US-091` repasse `done` (sa DoD est complétée par US-091b). Le reskin d'EPIC-002 peut s'ouvrir au Sprint 23.

Deux réserves, sans impact sur le Goal, sont portées en rétrospective (voir Dette & Suivis) : une dette d'accessibilité transverse (focus non visible sur les boutons primaires des écrans reskinnés antérieurs) et l'instabilité du job CodeQL `Analyze` en CI (infrastructure, non bloquant).

---

## US du Sprint 22

| ID | Titre | PR | Verdict | Tests |
|---|---|---|---|---|
| US-093 | Qualité — WCAG axe/pa11y en CI + tests d'états saisie hebdo | #137 | ✅ | Job a11y CI (mode rapport : `A11yHtmlExportTest` → pa11y/axe, `bin/a11y-gate.php` + `tests/a11y/baseline.json`) · `TimesheetPageTest` CA-2/3/4 |
| US-091b | Absences — calendrier de conflits (ABS-03) + solde projeté dynamique (CA-2) | #138 (feature) · #139 (durcissements) | ✅ | `AbsencePageTest` (calendrier + alternatives textuelles + mois vide) · `AbsenceApiTest` (impact jours ouvrés / conflit / plage invalide / 401) |
| US-092b | Complétude — relance inline (CPL-04) + filtre/recherche (CPL-05) | #140 | ✅ | `CompletenessReminderTest` (contrôles manager vs collaborateur, relance sélectionnée, sélection vide 422, non-manager 403, ID forgé écarté) |
| US-094 | Reskin validation des temps (PG-VLD-01) | #141 | ✅ | `ValidationPageTest` (dont état vide ajouté) · `ValidationPageAccessTest` (403 / accès chef de projet) |
| US-095 | Reskin relances (PG-REL-01) | #142 | ✅ | `ReminderPageTest` (403, écran config, POST-Redirect-Get, borne invalide non persistée) |

**Légende** : ✅ Livré DoD complète · ⚠️ Livré avec dette · ❌ Non livré

---

## Métriques Sprint 22

| Indicateur | Valeur |
|---|---|
| Points engagés | 12 pts (Must 6 + Should 6) |
| Points livrés (DoD stricte) | 12 pts |
| Vélocité Sprint 22 | 12 pts |
| Taux de complétion DoD stricte | 100 % |
| US livrées / engagées | 5 / 5 |
| US avec dette bloquante | 0 |
| PRs mergées sur `main` | 6 (#137, #138, #139, #140, #141, #142) |
| Suite de tests | ~715+ tests verts (hook pre-commit complet à chaque merge) |

La vélocité (12 pts) est sous la fourchette récente (S19–21 : 13–16 pts), conformément à la marge intentionnelle prévue au planning pour absorber la dette de qualité. `goal_met: true` reflète une DoD stricte réellement satisfaite (contraste avec S21, où l'écart mesure/livraison avait été relevé en rétro).

---

## Dette & Suivis

### Transversal — Accessibilité : focus non visible sur les boutons primaires

La revue adversariale d'US-095 a confirmé un manque de focus visible (WCAG 2.4.7 AA) sur le bouton primaire plein (`bg-brand-500`) : Tailwind v4 (Preflight) supprime l'outline par défaut, et le style `focus:ring` n'était pas appliqué. **Corrigé sur l'écran relances (US-095).** Le même motif existe sur les boutons primaires des écrans reskinnés **antérieurs** (absence « Soumettre », complétude « Relancer la sélection », validation « Valider/Refuser »).

**Action Sprint 23** : story a11y dédiée pour harmoniser `focus:ring` sur tous les boutons du parcours (idéalement au niveau d'un composant bouton du bundle tailsfadmin).

---

### Transversal — CodeQL `Analyze` instable en CI (infrastructure)

Le job CodeQL `Analyze (javascript-typescript | actions)` échoue par intermittence (`javascript` sur #141, `actions` sur #142, tout en réussissant sur d'autres exécutions). L'analyse s'exécute correctement (0 alerte, SARIF exporté) ; l'échec survient à l'upload/finalisation (note runner « improved incremental analysis skipped… disk space »). Le check est **non requis** (`mergeStateStatus: UNSTABLE`, PR mergeable) et sans lien avec les diffs (reproduit sur des PR ne touchant ni JS ni workflows).

**Action Sprint 23** : tâche OPS pour stabiliser (augmenter l'espace disque du runner / purger le cache `codeql-overlay-status-*`) ou rendre les jobs `Analyze` explicitement non bloquants et documenter le comportement.

---

### US-091b — Base de décompte du solde d'impact (arbitrage PO)

Le solde projeté affiché à la sélection de dates (`/api/absences/impact`) est décompté en **jours ouvrés** (voulu par CA-2 : « exclut week-ends, fériés et fermetures »), alors que le compteur d'absences persisté (`AbsenceCounters`) reste en **span calendaire** (demi-journées de bord). Écart d'affichage assumé et documenté.

**Suivi** : arbitrage PO — harmoniser le modèle de solde (jours ouvrés partout) relèverait d'une story dédiée EPIC-003/absences.

---

### Process — Perte de commit au squash (git push + RTK)

Le durcissement d'US-091b (commit `3b61858`) a été perdu au squash de la PR #138 : sur cette machine, `git push -u` (proxifié par RTK) ne configure pas l'upstream, et le `git push` suivant échouait silencieusement (masqué par un `| tail`). Récupéré par `cherry-pick` → PR #139.

**Parade en place** : push systématique avec refspec explicite (`git push origin <branche>`) + vérification `origin/<branche> == HEAD` avant tout `gh pr merge`. Documenté en mémoire de session.

---

### Environnement — incident GitHub API (jour de clôture)

Le jour de la clôture, l'API GitHub a connu un incident intermittent (502/504/GraphQL) ralentissant la création de PR et les merges (#141, #142). Contourné par des relances « vérifie-puis-crée » (anti-doublon). Sans impact sur le code ; mentionné pour l'archéologie.

---

## Démonstration

L'ordre de démo suit le parcours P1/P2 de bout en bout, en cohérence avec les maquettes validées au Sprint 20 (`design-canvas/lot2-saisie/`).

### 1. Accessibilité attestée en CI — US-093
- Montrer le job a11y en CI (mode rapport) : dump HTML des écrans du parcours → analyse pa11y/axe, `bin/a11y-gate.php` comparant à `tests/a11y/baseline.json`.
- Montrer les tests d'états ajoutés à `TimesheetPageTest` (totaux serveur CA-2, `errorBanner` CA-3, lien vue jour CA-4) — dette S21 soldée.

### 2. Mes absences — calendrier de conflits + solde dynamique — US-091b
- Navigation `/absences?month=2026-09` (profil Collaborateur, `camille@demo.test`).
- Observer : **calendrier du mois** distinguant fériés / fermetures d'entreprise / absences déjà posées / week-ends, chaque marquage portant un `aria-label` + légende (jamais la couleur seule — WCAG 1.4.1).
- Sélectionner des dates dans le formulaire → **impact dynamique** : nombre de jours ouvrés concernés + solde projeté après la demande ; **avertissement de conflit** si chevauchement d'une fermeture ou d'une absence posée.

### 3. Complétude — relance inline + filtre — US-092b
- Navigation `/completude` (profil Chef de projet, `marc@demo.test`).
- Observer : **filtre par statut** (en retard / partiel / soumis) + **recherche par nom** (client, sans rechargement) ; **cases de sélection** + bouton **« Relancer la sélection »** → POST inline `/completude/relances`, retour accessible (`aria-live`). Sélection vide → message ; non-manager → contrôles masqués (403 côté serveur).

### 4. Validation des temps — reskin — US-094
- Navigation `/validation` (Chef de projet).
- Observer : tokens tailsfadmin (tables/cartes, boutons Valider vert / Refuser rouge, `sr-only`), durée en `4h00`, repli « Projet indisponible ». État vide clair si aucune imputation en attente. 403 sans `VALIDATE_TIME`.

### 5. Relances — reskin — US-095
- Navigation `/relances` (Chef de projet).
- Observer : 3 sections tokensées (règle de relance, aperçu « qui serait relancé », historique), flash en encarts tailsfadmin, badge « Escaladé N+1 » en warning (texte + bordure, pas la couleur seule), focus visible sur le bouton « Enregistrer ». Cohérence visuelle avec `/completude` (cible du bouton « Relancer les retards »).

---

*Sprint Review rédigée à partir des PRs mergées et des verdicts de vérification (revues adversariales US-091b / US-092b / US-095) — 2026-10-23.*
