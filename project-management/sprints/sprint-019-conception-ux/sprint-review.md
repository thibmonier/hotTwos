# Sprint Review — Sprint 19 (Conception UX — EPIC-013)

## Informations

| Attribut | Valeur |
|----------|--------|
| Sprint | 19 — Conception UX |
| EPIC | EPIC-013 (Recensement, parcours & conception UX) |
| Points engagés / livrés | 16 / 16 |
| Stories | 4 / 4 (US-080, US-081, US-083, US-085) |
| Nature | Sprint de **conception** (livrables documentaires, aucun code produit) |
| Date de clôture | 2026-09-11 |
| PRs | #116 (US-080), #117 (US-081), #118 (US-083), #119 (US-085) |

## 🎯 Atteinte du Sprint Goal

> « Cartographier le produit sur le socle : référentiel des pages cibles, parcours par persona, mapping pages ↔ composants, backlog de reskin priorisé. »

**✅ Atteint (16/16 pts).** Les quatre livrables sont produits, validés PO en continu (une PR par story, gates séquencés), et versionnés dans `project-management/architecture/`. La chaîne US-080 → US-081 → US-083 → US-085 a été respectée.

## 📦 Livré

| US | Livrable | Contenu clé |
|----|----------|-------------|
| US-080 | `architecture/page-inventory.md` | 34 pages existantes + ~30 à créer, ventilées par module ; **8 dashboards par profil** + home de repli ; menu par **cycles de vie** (`Client→Devis→Projet→Facture` ; RH) ; conventions transverses (breadcrumb, en-tête contextuel, tabs). |
| US-081 | `architecture/parcours-personas.md` | 6 parcours principaux (Mermaid) P1–P6, référençant les IDs de pages ; **7 ruptures** (R1 IA saisie, R4 simulation staffing, R5 commerce, R6 RH, R7 dashboard dirigeant…) ; pages orphelines. |
| US-083 | `architecture/page-component-mapping.md` | Mapping **par type de page** → layout + composants `tsf:*` réels (v1.4.2) ; **7 gaps** bundle (G1 StatCard, **G2 Kanban**, G3 FilterBar, G4 PageHeader, G5 Timeline, G6 EmptyState, G7 Heatmap). |
| US-085 | `architecture/backlog-reskin-priorise.md` | Backlog **MoSCoW pondéré** ventilé par EPIC : Must ~32 pts, Should ~41, Could ~8, Won't (cette vague) conservés. |

## 📈 Métriques

- **Stories** : 4/4 · **Points** : 16/16 · **Tâches** : 17/17 · **Effort réel** : ~23h.
- **Qualité** : sprint sans code → `make ci` reste vert sur `main` ; pre-commit (PHPStan, Deptrac, 697 tests, gitleaks) vert à chaque PR.
- **Reports assumés** : US-082 (audit UX) et US-084 (maquettes HF) — dépendance externe `hotones`.

## 🎬 Démonstration

Les 4 livrables sont dans `project-management/architecture/` (diagrammes Mermaid rendus nativement sur GitHub). Parcours de démo : `page-inventory` (le périmètre) → `parcours-personas` (les usages + ruptures) → `page-component-mapping` (le comment, sur le socle) → `backlog-reskin-priorise` (le plan d'action).

## 💬 Feedback PO (recueilli en cours de sprint)

- **Enrichissement majeur d'US-080 (v2)** : dashboards par typologie de profil, cycles de vie comme colonne vertébrale du menu, home+dashboard avec repli, RH en 2 dashboards, Devis/Factures en domaines propres, conventions transverses.
- « D'autres scénarios existeront mais graviteront autour de cette première base » (US-081).
- « Le contenu détaillé des dashboards et pages sera clarifié au fil de l'eau » → à traiter dans les maquettes (US-084) et le design de détail.

## Impact backlog

- **Backlog bundle** : 7 gaps (G1–G7) à créer comme issues `tailsfadmin` — **G2 `Ui:Kanban`** et G1/G4 (dashboards) prioritaires.
- **Backlog reskin** : ventilé par EPIC module — entrée des sprints de refonte front.
- **US-082 / US-084** restent reportés (accès `hotones` requis — captures d'écran).

## Prochaines étapes

1. **Rétrospective** Sprint 19.
2. Décider le **Sprint 20** : reskin d'un EPIC module (EPIC-003 saisie P1 = Must, socle prêt) **ou** création des gaps bundle prioritaires (G1/G2/G4) **ou** US-082/084 dès l'accès `hotones`.
3. Créer les **issues bundle** pour G1–G7.
