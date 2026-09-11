# Sprint Review — Sprint 20

## Informations

| Attribut | Valeur |
|----------|--------|
| Sprint | 20 — Conception UX du parcours de saisie collaborateur (EPIC-013, bouclage) |
| Date de la review | 2026-09-25 (fin de sprint nominale) |
| Fenêtre | 2026-09-14 → 2026-09-25 (livraison effective : 2026-09-11) |
| Animateur | Scrum Master |
| Product Owner | Thibaut |

## Sprint Goal

> **« Auditer et concevoir — en maquettes haute-fidélité validées PO — le parcours de saisie
> collaborateur (EPIC-003, persona P1), prêtes à développer, pour engager le reskin au Sprint 21. »**

**Atteint : ✅ OUI**

Justification : les deux stories engagées (audit + maquettes) sont livrées, **validées PO à 100 %** et
mergées sur `main`. Le gate `design-canvas/lot2-saisie/VALIDATION.md` est franchi → le dev reskin (Sprint 21)
est débloqué. La livraison respecte la règle produit permanente : **maquettes validées avant tout dev front.**

---

## 🎯 Atteinte du Sprint Goal

| Objectif du sprint | État |
|--------------------|------|
| Auditer les écrans existants du parcours P1 | ✅ 4 écrans audités (saisie hebdo/jour, absences, complétude) |
| Concevoir les maquettes HF des écrans prioritaires | ✅ 7 artboards (5 écrans + états + mobile) |
| Validation PO formalisée (gate) | ✅ 100 % « Validé PO » |
| Maquettes 100 % sur le bundle tailsfadmin, gaps notés | ✅ G1/G4 matérialisés + notés, non développés |
| Clôturer EPIC-013 | ✅ 6/6 US livrées |

---

## 📦 User Stories livrées

| ID | Titre | Points | Démo | PR | Statut |
|----|-------|--------|------|----|--------|
| US-082 | Audit et critique UX de l'existant (inspiré hotones) | 5 | ✅ | #122 | ✅ Livré |
| US-084 | Maquettes haute-fidélité des écrans prioritaires (design-canvas) | 8 | ✅ | #124 | ✅ Livré |

**Livré : 13/13 points (100 %)**

> Note : la PR empilée #123 (US-084) a été fermée automatiquement à la suppression de sa base après merge
> de #122 ; contenu rebasé sur `main` et re-soumis en #124.

---

## ❌ User Stories non terminées

Aucune. Les 13 points engagés sont livrés.

---

## 📈 Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés | 13 | — |
| Points livrés | 13 | — |
| Vélocité | 13 | ↘️ vs moyenne (~16–21) — **volontaire** (sprint de conception, marge qualité) |
| Taux de complétion | 100 % | ↗️ |
| PR mergées | 2 (#122, #124) | — |
| Fichiers livrés | 32 (audit + 7 artboards + gate + captures hotones) | — |
| Bloquants accessibilité | 6 détectés → 6 corrigés | ✅ 0 restant |
| Suite qualité (CI) | verte (PHPStan max, Deptrac, 697 tests, secrets) | ✅ |

### Burndown (13 pts / réel)
```
Pts |
 13 |●
  8 |      ●            ← US-082 livrée (audit)
  0 |            ●      ← US-084 validée PO + mergée → EPIC-013 bouclé
    +--------------------------
     J1 …            J-fin
```

---

## 🎬 Démonstration

Sprint de **conception** : la démo porte sur les livrables documentaires et le canevas de maquettes
(pas d'incrément logiciel exécutable — le dev suit au Sprint 21).

1. **US-082 — Audit UX** (~10 min)
   - Parcourir `architecture/audit-ux-existant.md` : grille (Nielsen + charge cognitive + WCAG 2.2 AA).
   - Mettre en avant les findings : **F-S5-4** (repli `userId[:8]` → e-mail, bloquant), **F-S5-5**
     (régression des icônes de badge sur `/completude`), et la comparaison hotones sur la saisie hebdo.

2. **US-084 — Maquettes HF** (~20 min)
   - Ouvrir le canevas : 🎨 https://claude.ai/code/artifact/4b74613a-6890-428b-bb2a-950f751b17ba
   - Dérouler les 7 artboards : DSH-COLLAB (contrepartie visible), saisie hebdo (rempli + états + mobile),
     saisie du jour (offline), absences (calendrier conflits), complétude (StatCards + relance inline).
   - Montrer la traçabilité audit → maquette et les gaps G1/G4 matérialisés + notés.

### Scénario de démo (parcours P1)
```gherkin
Given je suis Camille (collaboratrice, persona P1)
When j'ouvre mon tableau de bord
Then je vois ma contrepartie (complétude, solde congés, avancement projet) et un accès saisie en 1 clic
When je saisis ma semaine
Then les totaux sont visibles, l'objectif est affiché, une erreur > 24h est signalée et bloque l'envoi
When je pose un congé
Then je vois l'impact sur mon solde projeté et les conflits (fériés/fermetures) dans un calendrier
```

---

## 💬 Feedback Product Owner

### Positif
- Conception livrée conforme à la règle « maquettes validées avant dev front ».
- Recommandations d'audit actionnables et directement traduites en choix de maquette.
- Alignement 100 % sur le bundle tailsfadmin ; gaps clairement isolés pour le lot bundle S21.

### Validation
- **Gate PO : 100 % des 7 artboards « Validé »** (2026-09-14) → dev reskin S21 débloqué.

### Décisions enregistrées
- **Maquettes sur tokens tailsfadmin** (Outfit, brand #465fff), pas l'ancien Skote/Poppins du lot 1.
- **Gaps G1 `Ui:StatCard` / G4 `Layout:PageHeader`** : à développer en **premier** au Sprint 21 (lot bundle).
- Audit basé sur la **lecture des templates Twig** (recette live non requise).

---

## Impact sur le Backlog

| Action | Élément | Description |
|--------|---------|-------------|
| Clôturé | EPIC-013 | 6/6 US livrées (recensement → parcours → mapping → backlog reskin → audit → maquettes) |
| À planifier | Sprint 21 | Dev reskin EPIC-003 : lot bundle G1/G4 d'abord, puis portage des écrans validés |
| Corrections tracées | F-S5-4, F-S5-5 | À appliquer dans le reskin (e-mail sur `/completude`, icônes de badge) |

---

## Prochaines étapes

1. **Rétrospective Sprint 20** → `/workflow:retro 020`.
2. **Sprint 21** : dev reskin EPIC-003 sur maquettes validées (lot bundle G1/G4 en tête).
3. Reporter au reskin les corrections d'audit bloquantes/majeures (F-S5-4, F-S5-5, totaux serveur, états d'erreur).
