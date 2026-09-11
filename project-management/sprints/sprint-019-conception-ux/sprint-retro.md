# Rétrospective — Sprint 19 (Conception UX — EPIC-013)

## Informations

| Attribut | Valeur |
|----------|--------|
| Sprint | 19 — Conception UX |
| Points livrés | 16 / 16 (4/4 stories) |
| Date | 2026-09-11 |
| Participants | PO, dev (agent) |

## Directive Fondamentale

> « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait le meilleur travail possible, compte tenu de ce qui était connu à ce moment-là, de ses compétences et capacités, des ressources disponibles et de la situation. »

## Rappel du Sprint

Premier incrément d'EPIC-013 : cartographier le produit sur le socle tailsfadmin (US-086). Sprint **documentaire**, exécution séquencée avec **gates de validation PO** entre stories.

## ⭐ Observations

### 🟢 CONTINUER
- **Gates de validation PO intermédiaires** (1 PR par story) : le retour PO mi-parcours a **doublé la valeur** du référentiel (dashboards, cycles de vie) avant de partir sur les stories suivantes.
- **Séquencement strict** US-080 → US-081 → US-083 → US-085 : chaque livrable s'appuie sur l'amont validé, zéro rework en cascade.
- **Mapping par type de page** (US-083) plutôt que page par page : concis, sans répétition, robuste au changement.
- **Livrables versionnés** dans `architecture/` (Mermaid rendu sur GitHub) : revue facile.

### 🟡 COMMENCER
- **Créer les issues bundle** pour les 7 gaps (G1–G7) dès maintenant, pour ne pas bloquer les sprints de refonte (surtout **G2 Kanban**).
- **Adopter `Layout:Breadcrumb`** dans l'app (composant existant, convention non encore appliquée).
- **Alimenter les EPICs modules** depuis le backlog reskin (chaque EPIC pioche sa colonne).

### 🔴 ARRÊTER
- Rien de majeur. (En v1 d'US-080, des noms de composants avaient été **devinés** — corrigé en affinage par inspection réelle du bundle ; à ne pas reproduire.)

### ⬆️ PLUS DE
- Retours PO en cours de sprint (ils ont structuré la conception : cycles de vie, dashboards par profil).

### ⬇️ MOINS DE
- Hypothèses non vérifiées dans les livrables (toujours ancrer sur le code/bundle réel).

## Thème & analyse

**Le retour PO à mi-US-080 a été l'événement structurant.** La v1 recensait l'existant ; la direction PO (dashboards par profil + cycles de vie `Client→Devis→Projet→Facture` et RH + conventions transverses) a transformé un inventaire en **modèle de navigation cible**. La leçon : sur un sprint de conception, **provoquer le retour PO tôt** (dès le premier livrable) est le plus fort levier de valeur.

## 🎯 Actions Sprint 20

1. **Créer les issues bundle G1–G7** (prioriser G2 Kanban, G1 StatCard, G4 PageHeader). — *Resp. : dev/bundle.*
2. **Arbitrer le Sprint 20** (PO) : EPIC-003 reskin saisie P1 (Must, socle prêt) / gaps bundle d'abord / US-082-084 dès accès `hotones`.
3. **Fournir l'accès `hotones`** (captures d'écran) pour débloquer US-082 (audit UX) + US-084 (maquettes HF).

## Suivi actions précédentes
- Socle tailsfadmin (US-086) : livré et adopté → a permis de cibler la conception sur le thème définitif. ✅
- MAILER staging (reconduit des sprints précédents) : toujours à exécuter.

## Vélocité
- 16 pts livrés (sprint de conception). Vélocité de référence des sprints « code » : ~20-22 pts. Non directement comparable (nature documentaire).

## ROTI (Return On Time Invested)
- À recueillir auprès du PO (échelle 1–5).

## Prochaine étape
`/workflow:status` puis démarrage du Sprint 20 selon l'arbitrage PO (action #2).
