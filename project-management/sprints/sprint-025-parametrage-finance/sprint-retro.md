# Rétrospective Sprint 25 — Paramétrage EPIC-005 + harmonisation solde absences

## Informations

| Champ | Valeur |
|---|---|
| Date | 2026-12-04 |
| Format | Starfish (Continuer / Commencer / Arrêter / Plus de / Moins de) |
| Sprint | 25 — Paramétrage finance + solde d'absences en jours ouvrés |
| Participants | PO, Développeur |
| Facilitation | Scrum Master |
| Durée | 2 semaines |

---

## Directive Fondamentale de Norman Kerth

> « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à l'époque, de ses compétences et aptitudes, des ressources disponibles et de la situation du moment. »

---

## Rappel du Sprint

**Sprint Goal :** solder le paramétrage EPIC-005 sur le socle (profils/taux, configs finance, périodes) et harmoniser le solde d'absences en jours ouvrés.

**Résultat : ✅ Atteint** — 4/4 US, 11/11 pts (DoD stricte).

| US | Titre | Statut | Points |
|---|---|---|---|
| US-104 | Reskin Profils & taux | ✅ Livré | 3 pts |
| US-105 | Reskin Configs finance (4 templates) | ✅ Livré | 3 pts |
| US-107 | Harmonisation solde absences (jours ouvrés) | ✅ Livré | 3 pts |
| US-106 | Reskin Périodes | ✅ Livré | 2 pts |

---

## Observations Starfish

### 🟢 Continuer

**Incrément fini + dette à la racine (4e sprint consécutif à 100 % DoD)**
S22–S23–S24–S25 : quatre sprints d'affilée à `goal_met: true` en DoD stricte. Borner à ≤ 3 écrans reskin + traiter la dette dans le composant / le domaine tient toujours.

**Solder une dette de fond par le domaine (US-107)**
La dette du solde d'absences (`US-091-CA2-solde`), ouverte depuis le Sprint 21 et reportée 3 fois, a été soldée à la racine : source unique de décompte (jours ouvrés) via `WorkingDaysCalculator`, sans dupliquer la logique. Le solde étant dérivé, aucune migration — risque minimal.

**TDD sur le changement de domaine**
US-107 a démarré par un test RED (plage franchissant un week-end → 2 jours ouvrés vs 4 calendaires) avant le refactor : la correction du décompte est prouvée, pas supposée.

**`make ci` complet avant chaque push**
Le miroir CI local (cs · rector · phpstan max · deptrac · 724 tests) a détecté en local les régressions d'US-107 (assertions calendaires figées) avant la CI distante — PRs vertes du premier coup côté GitHub.

**ACTION-1 appliquée (statut au merge)**
Les statuts d'US ont été basculés à `done` au fil des merges et persistés (#162) : `sprint-status.yaml` est resté fiable, contrairement à S24 où les statuts avaient dérivé jusqu'à la clôture.

**Discipline de push explicite (aucun commit perdu)**
`git push origin <branche>` + vérification `origin == HEAD` avant merge : tenu sur les 5 PRs.

---

### 🟡 Commencer

**Planifier le sprint de finalisation UX/UI**
Le PO juge les reskins fonctionnels mais **non finalisés UX/UI** (portage token-only). Cadrer un sprint dédié (hiérarchie visuelle, densité, états, micro-interactions) sur les écrans déjà reskinnés, idéalement adossé aux maquettes HF et aux agents `ui-designer`/`ux-ergonome`.

**Anticiper la cascade SchemaTool quand un service gagne une dépendance**
US-107 a fait échouer un test fonctionnel (500) car `AbsenceBalance` interroge désormais les tables calendrier absentes du schéma. Réflexe à systématiser : quand un service applicatif gagne une dépendance de repo, recenser les tests fonctionnels qui l'atteignent et compléter leur `SchemaTool`.

---

### 🔴 Arrêter

**Figer un comportement métier douteux dans un test**
`AbsenceApiTest` asseyait un solde sur une base calendaire (samedi compté comme congé) — un test « vert » masquait une incohérence métier. Un test doit encoder l'exigence, pas l'implémentation du moment.

---

### ⬆️ Plus de

**Documenter les décisions de domaine par ADR**
ADR-0024 (jours ouvrés = base unique) tranche et trace une décision restée ouverte 4 sprints. À reproduire pour toute décision de domaine récurrente.

**Réutilisation d'un patron commun sur des écrans jumeaux**
US-105 (4 templates de config) a appliqué un patron unique (PageHeader + carte formulaire tokenisée) : cohérence et rapidité. À généraliser aux familles d'écrans.

---

### ⬇️ Moins de

**Dépendance à la stabilité de l'environnement Docker local**
Le daemon OrbStack s'est arrêté en cours de run (socket disparu), interrompant `make ci`. Parade connue (`orb start` + `up -d` + `make cache-dev`), mais à surveiller ; envisager un check de disponibilité Docker en préambule des runs longs.

**Bruit non suivi dans le working tree (report récurrent)**
`.idea/`, `RESUME-*`, churn `tailadmin-ref/` traînent toujours dans `git status` (ACTION-4 non faite). À solder pour de bon.

---

## Thèmes priorisés

### 🥇 Thème 1 — Finaliser l'UX/UI des écrans reskinnés
Le socle est en place partout ; la finition UX/UI reste à faire. C'est le prochain jalon de valeur perçue — un sprint dédié.

### 🥈 Thème 2 — Robustesse des tests face aux changements de domaine
Cascade SchemaTool et tests figeant un comportement douteux : anticiper l'impact d'un changement de domaine sur les schémas de test et l'exigence encodée.

### 🥉 Thème 3 — Capitaliser la recette (incrément fini, dette à la racine)
Quatre sprints à 100 % DoD : reconduire pour la suite (finalisation UX/UI, puis EPIC-006 CRM), sans sur-ouvrir.

---

## Actions SMART pour le Sprint 26

### ACTION-1 — Cadrer et lancer le sprint de finalisation UX/UI
**Description :** définir le périmètre d'un sprint de finalisation UX/UI sur les écrans reskinnés (EPIC-002/003/005) : critères de « fini » UX/UI, écrans prioritaires, appui maquettes HF + agents UX.
**Responsable :** PO (priorisation) + Développeur · **Deadline :** Sprint Planning S26 (J1)
**Critère mesurable :** ≥ 1 US de finalisation « Ready » avec critères UX/UI explicites ; périmètre borné.
**Priorité :** 🔴 Must

### ACTION-2 — Compléter les schémas de test au fil des changements de domaine
**Description :** quand un service applicatif gagne une dépendance de repo, recenser les tests fonctionnels l'atteignant et compléter leur `SchemaTool` dans la même PR.
**Responsable :** Développeur · **Deadline :** continu S26
**Critère mesurable :** 0 échec fonctionnel « table manquante » sur les PRs S26.
**Priorité :** 🟡 Should

### ACTION-3 — Nettoyer le bruit du working tree (report S25)
**Description :** arbitrer le sort des fichiers non suivis (`.idea/`, `RESUME-*`, churn `tailadmin-ref/`) : commiter les refs utiles, ajouter le reste au `.gitignore`.
**Responsable :** Développeur · **Deadline :** J2 S26
**Critère mesurable :** `git status` propre hors travail en cours.
**Priorité :** 🟢 Could

---

## Suivi des actions du Sprint 24

| Action S24 | Description | Statut |
|---|---|---|
| ACTION-1 | Statut d'US → `done` au merge | ✅ **Faite** — appliqué au fil des merges, persisté #162 |
| ACTION-2 | Trancher la base de décompte du solde d'absences | ✅ **Faite** — US-107, harmonisation jours ouvrés (ADR-0024) |
| ACTION-3 | Poursuivre EPIC-005 par un incrément fini | ✅ **Faite** — US-104/105/106 (paramétrage) + US-107 |
| ACTION-4 | Nettoyer le bruit du working tree (`.gitignore`) | ⏳ **Non faite** — reportée (ACTION-3 S26) |

---

## Check-out

**Ce sprint solde le paramétrage EPIC-005 sur le socle et clôt enfin la dette du solde d'absences ouverte depuis le Sprint 21 — quatre sprints d'affilée à 100 % DoD. Notre prochain jalon n'est plus l'extension du socle mais sa finition : un sprint dédié à l'UX/UI des écrans reskinnés, pour transformer une cohérence technique en qualité perçue.**

- **PO :** _(à compléter en séance)_
- **Développeur :** _(à compléter en séance)_

---

*Rétro facilitée le 2026-12-04 — Sprint 25 hotTwos. Actions capturées pour le Sprint Planning S26.*
