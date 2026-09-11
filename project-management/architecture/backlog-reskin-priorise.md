# Backlog de refonte / reskin priorisé (MoSCoW) — `backlog-reskin-priorise.md`

> **Livrable US-085** (Sprint 19 — EPIC-013). Synthèse actionnable : quels écrans (re)concevoir, dans quel ordre, avec quel effort — ventilé par EPIC module.
> **Sources** : `page-inventory.md` (pages), `parcours-personas.md` (valeur/ruptures), `page-component-mapping.md` (composants/gaps).
> **Statut** : 🟡 Draft — en attente de **validation PO**.
> **Mis à jour** : 2026-09-11.

## Critères de priorisation (pondérés)
1. **Valeur métier / OBJ** — 2. **Fréquence d'usage persona** (P1 = 80 % → prioritaire) — 3. **Effort de reskin** — 4. **Dépendances de gaps composants** (US-083).

> **Reskin** = page existante à porter/affiner sur le socle. **Build** = page « À créer » (nouveau module), construite nativement sur le socle dans son EPIC. Les items *Won't (cette vague)* dépendent de modules non construits (EPIC-006 CRM, EPIC-008 RH).
> Effort en points indicatifs (à affiner par EPIC).

---

## 🔴 Must — reskin/build prioritaire (socle prêt, adoption critique)

| ID | Nom | Type | EPIC cible | Critère de priorisation | Gaps liés | Effort |
|----|-----|------|------------|--------------------------|-----------|--------|
| DSH-COLLAB | Dashboard collaborateur | Build | EPIC-003 | Point d'entrée P1 (80 %), contrepartie visible (adoption OBJ-7) | G1, G4 | 5 |
| PG-TMP-01 | Saisie hebdomadaire | Reskin | EPIC-003 | **P1 critique** — rejet si > 2 min ; mobile | – | 3 |
| PG-TMP-02/03 | Saisie du jour | Reskin | EPIC-003 | P1 quotidien | – | 2 |
| PG-ABS-01 | Mes absences | Reskin | EPIC-003 | P1 — congés en libre-service | – (Calendar) | 2 |
| PG-CPL-01 | Complétude | Reskin | EPIC-003 | Complétude (OBJ-1), managers | G1 | 3 |
| PG-PRJ-02 | Fiche projet (onglets) | Reskin | EPIC-002 | P2 — pilotage dérive (OBJ-2) ; déjà sur `Tabs` | G4, G5 | 3 |
| PG-PRJ-01 | Liste des projets | Reskin | EPIC-002 | P2 — accès quotidien | G3 | 2 |
| PG-VAL-01 | Valorisation | Reskin | EPIC-005 | Valeur direction/prod ; ProgressBar déjà adoptée | G1 | 3 |
| PG-FIN-01 | Tableau de bord financier | Reskin | EPIC-005 | P6/P3 — marge & dérive | G1 | 3 |
| PG-AUTH-01…04 | Écrans d'authentification | Reskin | EPIC-001 | 1ʳᵉ impression ; layout `auth` du socle (demande PO) | – | 3 |
| PG-CMN-01/02/03 | Routing d'accueil + home de repli + Mon compte | Reskin/Build | Transverse | Point d'entrée par profil (convention US-080) | G1, G4 | 3 |

**Sous-total Must ≈ 32 pts.**

## 🟡 Should — après le socle des parcours P1/P2

| ID | Nom | Type | EPIC cible | Critère | Gaps liés | Effort |
|----|-----|------|------------|---------|-----------|--------|
| DSH-PRJ | Dashboard projets | Build | EPIC-002 | Entrée P2 ; alertes dérive | G1, G4 | 5 |
| DSH-STF | Dashboard staffing | Build | EPIC-004 | Entrée P3 | G1, G4 | 5 |
| DSH-AGENCE | Dashboard direction | Build | EPIC-007 | Valeur P6 ; drill-down (pipeline/tension dépendent EPIC-006) | G1, G4 | 5 |
| PG-STF-01/02 | Recherche staffing + plan de charge | Reskin | EPIC-004 | P3 — arbitrage hebdo | G7 | 3 |
| PG-VLD-01 | Validation des temps | Reskin | EPIC-003 | P2 — marges justes | – | 2 |
| PG-CLI-01 | Liste des clients | Reskin | EPIC-002/006 | Base du cycle commercial | G3 | 2 |
| PG-PRC-01 | Profils & taux | Reskin | EPIC-005 | Paramétrage valorisation | – | 3 |
| PG-FIN-02…05 | Configs finance (dérive, devises, FEC) | Reskin | EPIC-005 | Cohérence finance | – | 3 |
| PG-REF-01…06 | Référentiels (fériés, fermetures, compétences, régimes, circuits, audit) | Reskin | EPIC-001 | Paramétrage récurrent | G3 | 3 |
| PG-ORG-01 | Organisation | Reskin | EPIC-001 | Setup org | – | 2 |
| PG-ADM-01 | Périodes | Reskin | EPIC-005 | Clôture | – | 2 |
| PG-REL-01 | Relances | Reskin | EPIC-003 | Complétude | – | 2 |
| — | **Adoption `Layout:Breadcrumb`** (transverse, toutes pages) | Reskin | Transverse | Convention US-080 ; composant existant | – | 2 |

**Sous-total Should ≈ 41 pts.**

## 🟢 Could — valeur moindre / différable

| ID | Nom | Type | EPIC cible | Critère | Gaps liés | Effort |
|----|-----|------|------------|---------|-----------|--------|
| PG-ERR-403/404/500 | Pages d'erreur | Build | Transverse | Robustesse UX | G6 | 2 |
| PG-CMN-04 | Styleguide | Reskin | Interne | Outillage dev/design | – | 1 |
| PG-VLD-02 | Validation des absences | Build | EPIC-003 | Confort circuit | – | 2 |
| DSH-FAC | Dashboard facturation | Build | EPIC-005 | Dépend externalisation factures | G1 | 3 |

**Sous-total Could ≈ 8 pts.**

## ⚪ Won't (cette vague) — dépend de modules non construits

| ID / Domaine | Nom | EPIC cible | Raison / dépendance |
|--------------|-----|------------|---------------------|
| DSH-COM · PG-DEV-* · PG-CLI-02 | **Domaine Commerce / Devis** | EPIC-006 (CRM) | Module non construit — **build natif** sur socle dans EPIC-006 (pas un reskin). Rupture R5. |
| PG-FAC-* | **Factures externalisées** | EPIC-005/006 | Dépend du cycle Devis et de l'externalisation depuis la fiche projet. |
| DSH-REC · DSH-RH · PG-OFF-* · PG-REC-* · PG-EMB-* · PG-CAR-* | **Cycle RH / Recrutement** | EPIC-008 (RH) | Module non construit — build natif dans EPIC-008. **Prérequis composant G2 `Ui:Kanban`.** Rupture R6. HAB-2 (confidentialité entretiens). |
| — | Pré-remplissage IA de la saisie (améliore PG-TMP-01) | EPIC-010 | Dépend IA. Rupture R1. |

> Les items *Won't* restent **conservés** (jamais supprimés) : ils deviendront *Must/Should* dès l'ouverture de leur EPIC.

---

## Ventilation par EPIC module (pour planification des sprints de refonte)

| EPIC | Écrans (Must/Should/Could) | Écrans (Won't — à l'ouverture) |
|------|----------------------------|-------------------------------|
| EPIC-001 Référentiels | Auth, Référentiels, Organisation | – |
| EPIC-002 Projets | Fiche projet, Liste projets, Dashboard projets, Clients | – |
| EPIC-003 Temps | Dashboard collaborateur, Saisie, Absences, Complétude, Validation temps, Relances | Validation absences, pré-remplissage IA (EPIC-010) |
| EPIC-004 Staffing | Dashboard staffing, Recherche + Plan de charge | Simulation d'affaire (dépend EPIC-006) |
| EPIC-005 Finance | Valorisation, Finance, Profils & taux, Configs, Périodes | Dashboard facturation, Factures |
| EPIC-006 CRM | – | Commerce, Devis, opportunités |
| EPIC-007 Pilotage | Dashboard direction | Pipeline pondéré, tension 3 mois |
| EPIC-008 RH | – | Recrutement (kanban), Contrats, Carrières, Entretiens |

## Prérequis bundle (gaps bloquants pour certains écrans)
- **G2 `Ui:Kanban`** → bloquant pour le pipeline recrutement (EPIC-008).
- **G1 `Ui:StatCard`** / **G4 `Layout:PageHeader`** → fortement mobilisés par tous les dashboards (Must) → à prioriser côté bundle.

## Prochaine étape (post-sprint)
Ce backlog **irrigue les EPICs modules** : chaque EPIC crée ses US de refonte à partir de sa colonne. Les **maquettes haute-fidélité** (US-084, reportée — accès `hotones`) préciseront les écrans Must avant dev front.

## Validation PO
- [ ] Priorisation MoSCoW et pondération validées.
- [ ] Ventilation par EPIC validée.
- [ ] Items *Won't (cette vague)* et leurs dépendances validés.
- [ ] Backlog reconnu comme **source de vérité** pour les sprints de refonte.
