# Rétrospective Sprint 21 — Reskin parcours saisie P1 (EPIC-003)

## Informations

| Champ | Valeur |
|---|---|
| Date | 2026-09-12 |
| Format | Starfish (Continuer / Commencer / Arrêter / Plus de / Moins de) |
| Sprint | 21 — Reskin parcours saisie collaborateur P1 |
| Participants | PO (Product Owner), Développeur |
| Facilitation | Scrum Master |
| Durée | 2 semaines |

---

## Directive Fondamentale de Norman Kerth

> « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à l'époque, de ses compétences et aptitudes, des ressources disponibles et de la situation du moment. »

---

## Rappel du Sprint

**Sprint Goal :** Reskin du parcours de saisie collaborateur P1 sur le socle tailsfadmin — offrir à Camille une interface cohérente, lisible et conforme aux maquettes validées au Sprint 20, du dashboard jusqu'à la complétude.

**Résultat : ⚠️ Partiellement atteint**

6 User Stories embarquées :

| US | Titre | Statut | Points |
|---|---|---|---|
| US-087 | Bundle StatCard/PageHeader + release v1.6.0 | ✅ Livré | 3 pts |
| US-088 | Dashboard collaborateur | ✅ Livré | 3 pts |
| US-089 | Reskin saisie hebdo | ✅ Livré (tests partiels) | 3 pts |
| US-090 | Reskin saisie jour | ✅ Livré | 2 pts |
| US-091 | Reskin absences | ⚠️ Partielle (ABS-03 + CA-2 différés) | 4 pts / 3 livrés |
| US-092 | Reskin complétude | ⚠️ Partielle (CPL-04/05 différés) | 3 pts / 2 livrés |

**Vélocité déclarée :** 18 pts (`goal_met: true` dans sprint-status.yaml)
**Vélocité en DoD stricte :** ~15 pts (ABS-03, CA-2, CPL-04/05, tests états US-089 manquants)

---

## Observations Starfish

### 🟢 Continuer — Ce qui fonctionne, à préserver

**Historique Git propre et lisible**
Les squash merge fast-forward ont été tenus sur les 8 PRs applicatives et les 2 PRs bundle tailsfadmin. L'historique `main` est linéaire, sans commit ORT ni octopus. Les Conventional Commits (`type(scope): message (#N)`) ont été respectés à 100 %.

**Séquençage cross-repo maîtrisé**
Les PRs bundle (#39/#40) ont été mergées avant le bump Composer dans hotTwos (#131), lui-même mergé avant les US consommatrices (US-088 #132, US-092 #133). Le séquençage des dépendances est intégré au workflow sans procédure formelle.

**Traçabilité maquette → code**
Chaque US référence explicitement la maquette `.dc.html` validée au Sprint 20 et les recommandations d'audit (ABS-01→06, TMP1-01→07). Cela a permis une implémentation ciblée et une revue rapide.

**Pattern dev-dans-le-bundle**
La création de StatCard/PageHeader dans tailsfadmin avant consommation dans hotTwos a évité toute duplication de composant. Le bundle v1.6.0 est utilisable par d'autres contextes.

**Honnêteté du sprint-review**
La `sprint-review.md` documente fidèlement les lacunes (ABS-03, CA-2, CPL-04/05, tests manquants) sans les masquer. Cette transparence alimente directement le backlog S22.

**Tests de non-régression timesheet verts**
Les 48 tests timesheet existants sont restés verts malgré le reskin — la logique métier n'a pas été perturbée par les modifications de templates et de contrôleurs.

**`make ci` comme proxy CI local complet**
La cible Makefile (`tailwind cs rector analyse deptrac test`) couvre les mêmes étapes que la CI. Lorsqu'elle est exécutée avant le push, elle prévient les blocages en PR.

**`make cache-dev` et `compose.override.yaml` opérationnels**
Les deux parades aux problèmes récurrents (bris de container XML post-clear, crash APP_SECRET) sont en place et documentées depuis S8.

---

### 🟡 Commencer — Nouvelles pratiques à introduire

**Job CI d'accessibilité (axe-core ou pa11y)**
La conformité WCAG 2.2 AA est déclarée dans les CHANGELOGs et attributs Twig de 6 écrans du parcours P1, mais aucun rapport automatisé n'existe dans le repo (`find .github -name '*a11y*' → 0 résultat`). L'outillage doit précéder tout déploiement en staging.

**`php-cs-fixer --dry-run` dans le hook `.githooks/pre-commit`**
Le hook lance PHPStan, Deptrac, PHPUnit et Gitleaks, mais pas CS. La CI bloque dessus (ci.yml L70-71). US-088 a été bloquée en PR sur la règle `phpdoc_to_comment` alors que le hook était vert. Un seul ajout ferme ce gap.

**Résoudre la collision de port `:8080`**
`compose.yaml:35` (hotTwos) et `/Users/tmonier/projects/hottwos/compose.yaml` (hotones legacy) exposent tous deux le port 8080. Arrêter hotones manuellement avant `make up` est un workaround non documenté. Remapper l'un des deux projets (ex. `"8081:8080"` pour hotones) élimine la friction.

**Activer la branch protection sur `main` (ARC-89)**
Référencée dans un commentaire de `ci.yml` depuis S7 mais jamais configurée : 7 des 8 branches Sprint 21 ont mergé sans gate CI vert. Sans protection, le rollback est le seul filet si le build casse.

**Rouvrir US-091 avec ABS-03 en Must S22 — décision PO requise**
ABS-03 (calendrier des conflits d'absence avec alternative textuelle) était classé Must-Have dans les critères d'acceptation de US-091, pas Should. Son report n'est tracé dans aucun ADR ni note de sprint planning. Une décision formelle est nécessaire.

**Corriger les frontmatter des US-087 à US-092 post-merge**
Les 6 fichiers affichent encore `Statut: 🟢 Ready` au moment de la clôture du sprint. Ce drift rend les statuts fichiers inexploitables pour la planification.

---

### 🔴 Arrêter — Pratiques à abandonner

**Marquer `goal_met: true` en cas de sprint partiellement atteint**
`sprint-status.yaml` indique `goal_met: true` alors que `sprint-review.md` conclut `⚠️ Partiellement atteint`. La contradiction fausse les métriques de vélocité (~18 pts déclarés contre ~15 pts en DoD stricte) et calibrera incorrectement la capacité des sprints suivants.

**Mettre des US en `status: done` avec des DoD items non cochés**
US-091 : "Impact solde contextualisé ; calendrier des conflits avec alternative textuelle" = non livré. US-092 : "Relance inline (≤ 3 clics) + filtre" = non livré. US-089 : "Tests des nouveaux états" = non livré. Un statut `done` doit être conditionnel à la DoD complète.

**Reporter un Must-Have sans décision PO tracée**
ABS-03 est un critère d'acceptation explicite de US-091 (pas un Should). Son abandon en cours de sprint sans ADR ni note de décision crée une zone grise dans la responsabilité produit.

**Commits batch en rafale sur des US distinctes**
4 commits créés en 16 secondes (11:00:33 → 11:00:49) couvrant sprint-start + US-089 + US-090 + US-091 : la granularité temporelle est perdue pour l'archéologie git. Chaque US mérite un push indépendant.

**Préfixer `docs/` des branches portant du code fonctionnel**
`docs/us-089-reskin-saisie-hebdo` modifie `TimesheetPageController.php` (+33 lignes). Ce mauvais préfixe explique aussi pourquoi les triggers CI `feature/**` ne se déclenchent pas en push.

**Enchaîner `composer update` → commit sans `make cache-dev`**
`composer update` efface `var/cache/dev/App_KernelDevDebugContainer.xml`. PHPStan-Symfony exige ce fichier. Le piège est documenté mais récurrent. Il réécrit aussi `assets/controllers.json` sans auto-commit — confirmé sur PR #113, contrôleurs Stimulus cassés sur main.

---

### ⬆️ Plus de — Ce qui existe et mérite d'être amplifié

**Tests fonctionnels couvrant les nouveaux états UI**
Les tests existants vérifient le rendu HTML mais pas les nouveaux états introduits au Sprint 21 : totaux serveur CA-2, conteneur `errorBanner` DOM, présence des 4 StatCards dans le dashboard, UUID de collaborateur dans les liens de relance. Plus de tests d'états = moins de régressions silencieuses.

**Décisions PO tracées dans les artefacts de sprint**
Quand un critère Must-Have est requalifié ou différé en cours de sprint, tracer la décision dans un ADR ou une note de planning évite l'ambiguïté en rétrospective et en audit de backlog.

**`make ci` avant chaque push de code PHP**
La pratique existe et est connue (mémoire S8). Davantage de discipline pour la systématiser — surtout sur les branches portant du code PHP sous un préfixe non `feature/` — réduirait les blocages PR.

---

### ⬇️ Moins de — Ce qui génère du bruit ou de la friction

**Dépendances cross-repo implicites sans vérification CI**
La CI applicative ne vérifie pas l'intégrité de la release bundle. Une CI rouge dans tailsfadmin après le bump aurait gelé US-088 et US-092 sans avertissement. Moins de confiance implicite, plus de vérification automatisée.

**Rebase + amend post-push**
Le reflog de US-088 montre un cycle rebase+amend consécutif à un merge concurrent de US-092. Ce pattern expose à un `--force-with-lease` et à des commits orphelins. Moins de merges concurrents sur des branches issues du même commit base.

---

## Thèmes priorisés

### 🥇 Thème 1 — Intégrité de la DoD et véracité des métriques

La contradiction `goal_met: true` vs `⚠️ Partiellement atteint`, les DoD items non cochés comptabilisés comme `done`, et le report de Must-Have sans décision tracée forment un même problème : la vélocité mesurée ne reflète pas la vélocité livrée. Ce drift se cumule et rend la planification des capacités futures incorrecte. C'est le thème prioritaire car il affecte la confiance du PO dans les données de pilotage.

### 🥈 Thème 2 — CI aveugle avant merge et accessibilité non attestée

7 branches sur 8 ont mergé sans gate CI vert (triggers manquants + branch protection absente). La conformité WCAG est déclarée mais non vérifiée par outillage. Ce double angle mort laisse des régressions potentielles — techniques ou réglementaires — passer silencieusement en production.

### 🥉 Thème 3 — Gap hook local / CI et friction d'environnement

L'absence de `php-cs-fixer` dans le pre-commit hook a causé un blocage PR confirmé (US-088). La collision de port `:8080` est un workaround manuel non documenté. Ces frictions récurrentes s'accumulent en coût de rebase et en temps de débogage sans valeur ajoutée.

---

## Actions SMART pour le Sprint 22

### ACTION-1 — Embarquer US-091b (ABS-03 + CA-2) en Must S22

**Description :** Créer la US US-091b couvrant les deux items différés de US-091 : ABS-03 (calendrier des conflits d'absence avec alternative textuelle — Twig `aria-label`) et CA-2 (solde dynamique contextualisé calculé côté serveur). Ajouter au sprint planning S22 avec priorité Must, en lien avec la décision PO documentée dans un ADR ou une note de planning.
**Responsable :** Développeur (arbitrage PO requis en début de S22)
**Deadline :** Sprint Planning S22 (J1) pour la décision, livraison avant J5
**Critère mesurable :** `AbsencePageTest.php` contient au minimum une assertion sur la présence du marqueur de conflit calendrier (`data-conflict` ou équivalent) et une sur l'affichage du solde dynamique ; DoD US-091b 100 % cochée au moment du merge
**Priorité :** 🔴 Must

---

### ACTION-2 — Tests d'états manquants US-089 + US-088 + US-092

**Description :** Compléter les trois suites de tests fonctionnels identifiées comme insuffisantes : (a) `TimesheetPageTest.php` — ajouter assertions sur CA-2 (totaux serveur), CA-3 (`errorBanner` DOM présent), CA-4 (lien `timesheet_day` généré) ; (b) `CollaboratorDashboardTest.php` — ajouter assertion sur la présence des 4 StatCards dans le HTML rendu ; (c) `CompletenessPageTest.php` — ajouter assertion sur CPL-04 (lien de relance avec UUID collaborateur) dès que CPL-04 est implémenté.
**Responsable :** Développeur
**Deadline :** J3 du Sprint 22 (avant merge de toute PR portant du code sur ces routes)
**Critère mesurable :** `grep 'errorBanner\|grandTotal\|StatCard\|CPL-04' tests/Functional/**/*.php` retourne au moins 3 résultats distincts ; `make test` vert
**Priorité :** 🟠 Should (Must pour (a) et (b), Should pour (c) conditionnel à ACTION-1)

---

### ACTION-3 — Job CI accessibilité (axe-core ou pa11y) sur parcours P1

**Description :** Ajouter une étape CI dans `.github/workflows/ci.yml` exécutant pa11y-ci (ou axe-core via `@axe-core/cli`) sur les routes `/saisie`, `/saisie/jour`, `/absences`, `/completude` en environnement de test. Le job s'exécute après le démarrage du serveur de test Symfony et produit un rapport JUnit archivé comme artefact CI. La conformité WCAG 2.2 AA est le seuil requis.
**Responsable :** Développeur
**Deadline :** J5 du Sprint 22 (avant toute mise en staging du parcours P1)
**Critère mesurable :** `find .github -name 'ci.yml' | xargs grep -c 'pa11y\|axe'` retourne ≥ 1 ; le job passe en vert sur `main` avec 0 violation WCAG 2.2 AA sur les 4 routes ciblées
**Priorité :** 🔴 Must

---

### ACTION-4 — Ajouter `php-cs-fixer --dry-run` au hook pre-commit

**Description :** Ajouter une ligne dans `.githooks/pre-commit` lançant `docker compose run --rm -T -e PHP_CS_FIXER_IGNORE_ENV=1 app php vendor/bin/php-cs-fixer fix --dry-run --diff` après la vérification PHPStan existante. Documenter dans `README.md` (section "Développement local") que le hook doit être activé via `git config core.hooksPath .githooks`.
**Responsable :** Développeur
**Deadline :** J1 du Sprint 22 (avant le premier push PHP du sprint)
**Critère mesurable :** Un commit contenant un fichier PHP avec un bloc `/** @var */` est bloqué par le hook avec un message d'erreur CS explicite ; `make ci` et le hook produisent le même résultat sur le même fichier
**Priorité :** 🔴 Must

---

### ACTION-5 — Corriger `sprint-status.yaml` et les frontmatter US-087→US-092

**Description :** (a) Corriger `sprint-021` dans `.bmad/sprint-status.yaml` : `goal_met: false` (ou `goal_met: partial`) et points livrés en DoD stricte = 15. (b) Mettre à jour le frontmatter des 6 fichiers US (`Statut: ✅ Done`) avec note sur les items différés pour US-091 et US-092. (c) Établir une règle d'équipe : le `goal_met` de sprint-status.yaml doit être cohérent avec la conclusion de sprint-review.md avant merge de la PR de clôture.
**Responsable :** Développeur (validation PO sur le `goal_met`)
**Deadline :** J1 du Sprint 22 (correction immédiate avant le sprint planning)
**Critère mesurable :** `grep 'goal_met' .bmad/sprint-status.yaml` = `goal_met: false` pour sprint-021 ; `grep 'Statut' project-management/sprints/sprint-021-*/user-stories/*.md` = `✅ Done` pour les 6 fichiers
**Priorité :** 🟠 Should

---

### ACTION-6 — `targetMinutes` selon régime de travail (US-090 dette)

**Description :** Dans la vue saisie jour (`TimesheetDayController` / template `saisie_jour.html.twig`), remplacer la constante `420` (7h) par une valeur calculée depuis `WorkingDaysCalculator` en fonction du régime de l'utilisateur connecté (80 %, 60 %, temps partiel). Ajouter un test fonctionnel vérifiant que la barre de progression est correcte pour un utilisateur à 80 % (cible = 336 min).
**Responsable :** Développeur
**Deadline :** J7 du Sprint 22
**Critère mesurable :** `grep -r '420' src/UI/Http/Controller/Timesheet\|templates/timesheet` retourne 0 résultat après la modification ; test fonctionnel `TimesheetDayPageTest::testProgressBarReflectsPartTimeRegime` vert ; `make test` vert
**Priorité :** 🟡 Could (risque UX confirmé pour les profils temps partiel)

---

## Suivi des actions du Sprint 20

| Action | Description | Statut |
|---|---|---|
| Livrer US-082 (audit UX accès hotones) + US-084 (maquettes parcours saisie) | Produire les maquettes `.dc.html` validées servant de référentiel au Sprint 21 | ✅ **Faite** — US-082 et US-084 livrées, maquettes validées, sprint-review S19 concluante. Les 6 US du Sprint 21 ont été implémentées sur la base de ces maquettes. |

---

## Check-out

**Ce sprint nous a appris que la transparence dans la sprint-review est une force — nous la documentons bien. Notre prochain défi est d'aligner nos outils de mesure (sprint-status.yaml) sur cette même transparence, et de fermer les angles morts CI avant que le parcours P1 n'atteigne un environnement de staging.**

Un mot chacun pour clore la rétrospective :

- **PO :** _(à compléter en séance)_
- **Développeur :** _(à compléter en séance)_

---

*Rétro facilitée le 2026-09-12 — Sprint 21 hotTwos*
---

## Corrections appliquées immédiatement à la clôture (suite à cette rétro)

La rétrospective a relevé une incohérence dans les artefacts de clôture ; elle a été **corrigée dans le même commit** (la rétro produit de l'action, pas seulement des constats) :

- `sprint-status.yaml` : `sprint-021 goal_met: true` → **`false`** (alignement avec `sprint-review.md` « ⚠️ partiellement atteint ») ; ajout de `points_delivered: 15`.
- **US-091** : `status: done` → **`in-progress`** (Must CA `ABS-03` calendrier de conflits + `CA-2` solde dynamique non livrés → DoD incomplète, reste à finir S22 via **US-091b**).
- Compteurs : `done` 66 → 65, `in_progress` 0 → 1.
- `task-board` : US-091 déplacée en « En cours (→ S22) ».

> Reste comme dette explicite (voir `debt:` dans `sprint-status.yaml`) : US-091 ABS-03/CA-2, US-092 CPL-04/CPL-05, US-089 tests d'états, WCAG en CI. Les frontmatter des fichiers US restent secondaires (source de vérité = `sprint-status.yaml`).
