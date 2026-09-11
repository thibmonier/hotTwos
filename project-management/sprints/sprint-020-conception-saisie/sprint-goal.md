# Sprint 20 : Conception UX du parcours de saisie collaborateur (EPIC-003)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 20 |
| Début | 2026-09-14 |
| Fin | 2026-09-25 |
| Durée | 10 jours ouvrés |
| Capacité engagée | 13 points |
| EPIC | EPIC-013 (Conception UX) — **bouclage** |
| Thème arbitré (PO) | Reskin EPIC-003 (saisie P1) — abordé par sa **conception** |

## Sprint Goal

> **« Auditer et concevoir — en maquettes haute-fidélité validées PO — le parcours de saisie
> collaborateur (EPIC-003, persona P1), prêtes à développer, pour engager le reskin au Sprint 21. »**

Ce sprint respecte la règle produit permanente : **les maquettes UX/UI validées précèdent tout dev front.**
L'accès `hotones` ayant été débloqué par le PO (2026-09-11), les deux dernières stories d'EPIC-013
(US-082 audit, US-084 maquettes) deviennent réalisables — leur livraison **clôt EPIC-013 à 100 %**.

## Definition of Done (rappel — adaptée conception UX)

- [ ] Livrables documentaires/maquettes créés et versionnés
- [ ] Validation PO formalisée (revue PR + gate `design-canvas/VALIDATION.md` pour US-084)
- [ ] Accessibilité WCAG 2.2 AA intégrée dès la conception
- [ ] Maquettes 100 % sur composants/tokens du bundle `tailsfadmin` (gaps notés, non développés)
- [ ] Traçabilité audit → maquette assurée
- [ ] EPIC-013 mis à jour (progression, clôture)

## Sprint Backlog

| ID | Titre | Points | Priorité | Status | Séquence |
|----|-------|--------|----------|--------|----------|
| US-082 | Audit et critique UX de l'existant (inspiré hotones) | 5 | 🔴 Must | 🔵 To Do | 1 (amont) |
| US-084 | Maquettes haute-fidélité des écrans prioritaires (design-canvas) | 8 | 🔴 Must | 🔵 To Do | 2 (après US-082) |

**Total engagé : 13 points** (sous la vélocité moyenne ~16–21 → marge pour la qualité de conception).

### Écrans prioritaires cadrés (parcours saisie EPIC-003, bloc Must US-085)
| Écran | Régime | Audit US-082 | Maquette US-084 |
|-------|--------|--------------|-----------------|
| **DSH-COLLAB** — Dashboard collaborateur (gaps G1/G4) | **Création** (pas de dashboard produit ; home hotones = réf. faible) | ❌ hors audit produit | ✅ conçu depuis parcours P1 (+ home hotones en réf. faible) |
| **PG-TMP-01** — Saisie hebdomadaire (≤ 2 min, responsive) | Reskin amélioré | ✅ | ✅ (+ états + mobile) |
| **PG-TMP-02/03** — Saisie du jour (mobile) | Reskin amélioré | ✅ | ✅ |
| **PG-ABS-01** — Mes absences | Reskin amélioré | ✅ | ✅ |
| **PG-CPL-01** — Complétude (manager, gap G1) | Reskin amélioré | ✅ | ✅ |

> **DSH-COLLAB** : pas de dashboard produit à auditer ; hotones n'offre qu'une **home collaborateur** (réf. faible)
> → maquetté depuis le parcours P1. Les 4 autres : audit + maquette guidés par hotones **là où une capture comparable existe**.
> **Références hotones plus larges** (homes par profil, dashboards de cycle de vie : commerce/projet/pilotage/staffing/finance/agence, RH à venir)
> sont conservées dans `hotones-ref/` pour les **sprints de reskin ultérieurs** (EPIC-002/004/005/006/007/008) — hors périmètre S20.

## Dépendances

| Élément | Dépend de | Status |
|---------|-----------|--------|
| US-082 | US-080/081 (référentiel + parcours) | ✅ Livrés (S19) |
| US-082 | Captures **hotones** déposées dans `design-canvas/hotones-ref/` | ⏳ **Dépôt en cours (PO)** — renommage/complétion ; convention dans `hotones-ref/README.md`. Home collaborateur présente (réf. **faible** DSH-COLLAB) ; homes par profil + dashboards cycle de vie (RH à venir) = réf. **sprints ultérieurs** |
| US-084 | US-082 (recommandations) + US-083 (mapping) | US-083 ✅ ; US-082 séquencée en amont |
| Dev reskin (S21) | US-084 maquettes validées PO | 🔜 conditionne le Sprint 21 |

## Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Captures hotones absentes/partielles/mal nommées | Moyenne | Moyen | Déposer les captures dispo **avant J1** ; normaliser le nommage au dépôt ; auditer l'existant seul quand un écran comparable manque ; PO refait des captures si un écran est jugé nécessaire. **DSH-COLLAB non concerné** (conçu ex nihilo) |
| US-084 sous-estimée (8 pts, 5 écrans HF + états + gate) | Moyenne | Moyen | Prioriser saisie hebdo + dashboard ; états mobile en premier ; absences/complétude repli si besoin |
| Gaps composants (G1/G4) tentent un dev prématuré | Faible | Moyen | Règle stricte : **matérialiser + noter** les gaps, dev bundle au S21 |
| Dérive « conception → dev » dans le même sprint | Faible | Moyen | Périmètre = conception uniquement ; dev reskin = Sprint 21 |

## Cérémonies

| Cérémonie | Quand | Notes |
|-----------|-------|-------|
| Sprint Planning | J1 (2026-09-14) | Sprint Goal + engagement 13 pts + dépôt captures hotones |
| Daily | Quotidien | `daily-notes/YYYY-MM-DD.md` |
| Affinage (préparation S21) | mi-sprint | Cadrer les US de dev reskin EPIC-003 + lot bundle G1/G4 |
| Sprint Review | J10 (2026-09-25) | Démo maquettes validées + audit ; clôture EPIC-013 |
| Rétrospective | J10 (2026-09-25) | Directive fondamentale incluse |

## Suite (Sprint 21 — pressenti)
Dev **reskin EPIC-003** sur base des maquettes validées : lot bundle **G1 `Ui:StatCard` + G4 `Layout:PageHeader`**
d'abord, puis portage des écrans (dashboard collaborateur, saisies, absences, complétude).

## Notes
- Arbitrage PO du 2026-09-11 : thème « Reskin EPIC-003 (saisie P1) » + accès hotones fourni.
- Cadrage retenu : le Sprint 20 fait la **conception** (audit + maquettes validées) ; le dev suit au S21,
  conformément à la règle « maquettes avant dev front ».
- EPIC-013 après ce sprint : **6/6 US livrées → EPIC bouclé.**
