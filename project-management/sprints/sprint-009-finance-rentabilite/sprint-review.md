# Sprint Review — Sprint 9 (Finance & rentabilité, EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-04 |
| Durée | 2h (cérémonie) |
| Sprint Goal | « La **marge réelle par projet** (produit facturable − charge valorisée) est **calculée à la clôture**, **comparée au budget avec alerte de dérive**, et **consolidée dans un tableau de bord finance** réservé à la direction (coûts gated HAB-1). » |
| EPIC | EPIC-005 (Finance & rentabilité, Lot 2) |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (périmètre engagé livré à 100 %)**

Justification :
- **Marge réelle figée à la clôture (US-071)** — moteur de marge unique (`MarginCalculator`, ARC-6), figée par (tenant, période, projet) dans `project_margin` (RLS tenant), non-rétroactive (INV-2), gated HAB-1. ADR-0020 acte « produit facturable = CA reconnu ».
- **Budget vs réalisé + alerte de dérive (US-072)** — suivi budgétaire sur la fiche projet (coût/CA cible vs réalisé à date), consommation budgétaire, **alerte de dérive du taux de marge** au-delà d'un seuil (défaut 5 pts, OBJ-6), distincte de la dérive de charge (US-036). Ajout du CA cible (`revenue_budget_cents`).
- **Tableau de bord finance consolidé (US-073)** — `/finance` : totaux tenant + ventilation **par client et par projet** + nombre de projets en dérive ; réservé finance/direction (403 deny-by-default), coût/marge gated HAB-1 + trace HAB-6.

## 📦 User Stories livrées

| ID | Titre | Points | Priorité | Demo | Statut |
|----|-------|--------|----------|------|--------|
| US-071 | Moteur de marge réelle par projet à la clôture (INV-2) | 8 | 🔴 Must | ✅ | ✅ Livré |
| US-072 | Budget vs réalisé + alerte de dérive financière | 5 | 🔴 Must | ✅ | ✅ Livré |
| US-073 | Tableau de bord finance consolidé (direction, HAB-1) | 8 | 🟡 Should | ✅ | ✅ Livré |

**Livré : 21/21 points (100 %)** — US-074 (export comptable, 5 pts) laissée en réserve (non prise).

## ❌ User Stories non terminées

Aucune sur le périmètre engagé. Les tâches transverses (hors points) restent ouvertes :
- **T-TECH-01** Recette navigateur sur données peuplées (action rétro reconduite depuis S7/S8) — non faite.
- **T-TECH-02** `MAILER_DSN` staging + e2e reset — non faite (dépend de creds SMTP staging).
- **T-TECH-03** Warmup cache dev après `cache:clear` — documenté (mémoire projet), non automatisé.

## 📈 Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés | 21 | - |
| Points livrés | 21 | - |
| Vélocité | 21 | ↔️ (moy. récente ~24) |
| Taux de complétion | 100 % | ↔️ |
| Tâches board | 21/24 terminées (les 3 restantes = T-TECH hors points) | - |
| PR mergées (sprint) | #41, #42, #43 (3 PR) | - |
| `make ci` | vert à chaque merge (515 tests en fin de sprint, +45) | ↗️ |
| Dette technique ajoutée | 0 (3 revues `symfony-reviewer` : US-071 approuvé+réserves traitées, US-072 approuvé, US-073 approuvé — gating HAB-1 impeccable) | ↔️ |

Historique vélocité (S1→S9) : 29 / 20 / 23 / 21 / 22 / 21 / 33 / 22 / **21**.

## 🎬 Démonstration

1. **Marge à la clôture (US-071)** (~6 min)
   - Clôture d'une période (US-057) → figeage async de la marge par projet ; lecture gated (CA visible / coût+marge réservés finance).
   - Non-rétroactivité : une révision de taux ultérieure ne réécrit pas une marge figée.
2. **Suivi budgétaire (US-072)** (~6 min)
   - Fiche projet → onglet « Suivi budgétaire » : budget (coût/CA cible) vs réalisé, consommation %, **badge de dérive** ; chef de projet voit le CA sans le coût.
3. **Dashboard consolidé (US-073)** (~6 min)
   - `/finance` : totaux tenant, ventilation par client (triable) et par projet, nb projets en dérive ; sélecteur de période, filtre client ; 403 pour un non-habilité.

### Scénario de démo (Gherkin)

```gherkin
Given une période clôturée avec des projets valorisés rattachés à des clients
When le directeur financier ouvre /finance
Then il voit CA/coût/marge consolidés, la ventilation par client et par projet, et le nombre de projets en dérive

Given un chef de projet (VIEW_PROJECT_FINANCIALS sans VIEW_COLLABORATOR_COST)
When il ouvre le suivi budgétaire d'un projet
Then il voit le CA cible et réalisé mais ni le coût ni la marge ni la dérive
```

## 💬 Feedback à collecter (stakeholders)

1. La consolidation par client (via `Project.clientName`) suffit-elle, ou faut-il l'entité client structurée (US-014) dès maintenant ?
2. Le seuil de dérive (5 pts par défaut) doit-il être paramétrable par tenant dès le prochain incrément (US-018) ?
3. Le proxy « facturable = CA reconnu » (ADR-0020) tient-il jusqu'à l'arrivée d'un module de facturation ?
4. Faut-il une projection dédiée `fact_project_margin` pour la perf sur gros historique, ou les marges figées suffisent-elles ?

## 📝 Notes de session

Décisions prises pendant le sprint :
- **ADR-0020** : produit facturable = CA reconnu (proxy, en l'absence de module de facturation).
- **US-072** : modéliser un **CA cible** (`revenue_budget_cents`) pour couvrir coût/CA/marge cible (décision PO), marge cible **dérivée** (ARC-6), non stockée.
- **Consolidation US-073** depuis les marges figées (pas de projection dédiée) — ARC-6, perf O(projets).
- **Politique de merge** : merge après CI verte (squash), branche suivante depuis `main`.

## Impact sur le Backlog

| Action | ID | Description |
|--------|-----|-------------|
| Suivi | T-TECH-01 | Recette navigateur sur données peuplées (report chronique — escalade) |
| Config | T-TECH-02 | `MAILER_DSN` SMTP staging + e2e reset |
| Perf | US-073 | Profiler `/finance` sur 5 ans d'historique (ENF-PERF-3) sur données peuplées |
| EPIC-005 | — | Module de facturation (facturé réel) supersède le proxy CA reconnu ; export comptable US-074 (réserve) |

## Prochaines étapes

1. Rétrospective Sprint 9 (`sprint-retro.md`).
2. Traiter les tâches transverses T-TECH (recette peuplée en priorité).
3. Planification Sprint 10 (suite EPIC-005 : facturation / export, ou nouvel EPIC).
