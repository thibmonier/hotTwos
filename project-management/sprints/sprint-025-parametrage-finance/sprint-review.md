# Sprint Review — Sprint 25

**Thème** : Paramétrage EPIC-005 (profils/taux, configs finance, périodes) + harmonisation du solde d'absences
**Sprint Goal** : Solder le paramétrage EPIC-005 sur le socle tailsfadmin — profils & taux, configurations finance (dérive, devises, FEC) et périodes de clôture — et harmoniser définitivement la base de décompte du solde d'absences en jours ouvrés.
**Date** : 2026-12-04

---

## Atteinte du Sprint Goal

**Verdict : ✅ Atteint**

Les quatre User Stories engagées (11/11 points) sont livrées et mergées sur `main` avec une Definition of Done complète. Le sprint **solde le front de paramétrage EPIC-005** (après valorisation + dashboard financier en S24) et **clôt la dette récurrente du solde d'absences** (`US-091-CA2-solde`), ouverte au Sprint 21 et reportée trois sprints d'affilée. Quatrième sprint consécutif à 100 % DoD (S22–S25).

- **Paramétrage finance sur le socle** : profils & taux (`/profils`), 4 écrans de configuration finance (dérive marge/charge, devises, FEC) et périodes de clôture (`/administration/periodes`) sont portés sur les tokens tailsfadmin (PageHeader, StatCards, `tsf:Ui:Button`), sans toucher aux services de calcul (Pricing, finance).
- **Solde d'absences harmonisé** : `AbsenceBalance` compte désormais les **jours ouvrés** de chaque demande (via `WorkingDaysCalculator`, régime du collaborateur inclus), base unique cohérente avec la projection d'impact (US-091b). Un week-end, un férié ou une fermeture ne consomme plus de solde. Solde **dérivé** → aucune migration.

L'incrément est fini et cohérent : le parcours d'administration finance est homogène avec le reste de l'application, et le décompte d'absences est désormais sans ambiguïté.

---

## US du Sprint 25

| ID | Titre | PR | Verdict | Tests |
|---|---|---|---|---|
| US-104 | PG-PRC-01 — reskin Profils & taux | #158 | ✅ | `ProfileAssignmentPageTest` (reskin + pass-through Historique/Désactiver) |
| US-105 | PG-FIN-02…05 — reskin Configs finance (4 templates) | #159 | ✅ | `ChargeDriftThresholdConfigTest` (reskin) ; gating finance + CSRF préservés |
| US-107 | Harmonisation du solde d'absences en jours ouvrés | #160 | ✅ | `AbsenceBalanceTest` (cas week-end RED→GREEN), `AbsenceApiTest` (samedi exclu) ; ADR-0024 |
| US-106 | PG-ADM-01 — reskin Périodes | #161 | ✅ | `PeriodAdminTest` (reskin + flux de clôture inchangé) |

**Légende** : ✅ Livré DoD complète · ⚠️ Livré avec dette · ❌ Non livré

---

## Chantiers rétro / techniques (hors points)

| Chantier | Origine | Statut |
|---|---|---|
| ACTION-1 — statut d'US → `done` au merge | rétro S24 | ✅ Appliquée (#162) — 0 US mergée restée `ready` |
| ACTION-3 — incrément fini ≤ 3 écrans reskin | rétro S24 | ✅ 3 écrans reskin (US-104/105/106) + 1 US domaine (US-107) |
| ACTION-4 — nettoyage du bruit working tree (`.gitignore`) | rétro S24 | ⏳ Non fait — reporté S26 |

---

## Métriques Sprint 25

| Indicateur | Valeur |
|---|---|
| Points engagés | 11 pts (Must 9 + Should 2) |
| Points livrés (DoD stricte) | 11 pts |
| Vélocité Sprint 25 | 11 pts |
| Taux de complétion DoD stricte | 100 % |
| US livrées / engagées | 4 / 4 |
| PRs mergées | 5 (#158, #159, #160, #161, #162) |
| Suite de tests | 724 tests verts (`make ci` : cs · rector · phpstan max · deptrac 0) |

Quatrième sprint consécutif à `goal_met: true` en DoD stricte (S22–S25).

---

## Dette & Suivis

### Finalisation UX/UI des reskins (chantier S26+)
Décision PO (revue des captures S24) : les reskins portés sur le socle sont **fonctionnels mais non finalisés UX/UI** (portage token-only). Un **sprint dédié de finalisation UX/UI** est à planifier, couvrant les écrans déjà reskinnés (EPIC-002/003/005).

### ACTION-4 — nettoyage du bruit working tree (report S26)
Les fichiers `.idea/`, `RESUME-*` et le churn d'images `tailadmin-ref/` restent non suivis dans `git status`. À arbitrer (`.gitignore` vs commit des refs utiles).

### EPIC-005 — paramétrage soldé
Le front de paramétrage EPIC-005 est complet sur le socle. Reste, hors reskin : dashboard facturation (dépend de l'externalisation des factures) et l'ouverture d'EPIC-006 (CRM/devis, build natif).

---

## Démonstration

Parcours d'administration finance + décompte d'absences, sur le socle tailsfadmin.

### 1. Profils & taux — US-104
- `/profils`. PageHeader + StatCards (profils, actifs, collaborateurs affectables) ; affectation, création de profil, définition de tarif et surcharges de taux de vente tokenisées ; boutons Historique/Désactiver (câblés Stimulus) portés par `tsf:Ui:Button`.

### 2. Configs finance — US-105
- `/finance/config-derive`, `/finance/config-derive-charge`, `/finance/config-devises`, `/finance/config-fec`. PageHeader + formulaires/tableaux tokenisés ; gating finance (403) et CSRF préservés.

### 3. Périodes — US-106
- `/administration/periodes`. PageHeader + table des périodes (statuts en badges) ; clôture via `tsf:Ui:Button` (danger), double confirmation conservée.

### 4. Solde d'absences — US-107
- `/absences` et `/api/absences/balance`. Le solde pris/en attente est compté en **jours ouvrés** : une demande franchissant un week-end ne consomme que ses jours ouvrés, cohérent avec la projection d'impact.

---

*Sprint Review rédigée à partir des PRs mergées (#158–#162) — 2026-12-04.*
