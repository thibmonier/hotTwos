# Tâches Techniques Transverses — Sprint 24

> Chantiers issus de la **rétrospective S23** (hors points de story). Intégrés au sprint pour fiabiliser le flux et solder la dette de process.

## Flux git / discipline

### T-TECH-01 : « Branche d'abord » (ACTION-2)
- **Type** : [OPS] · **Estimation** : 1h · **Quand** : J1 (immédiat)
- **Raison** : incident S23 — un commit d'US-097 a atterri sur `main` (rattrapé).

**Description** : garde-fou pour ne jamais committer sur `main`. Deux niveaux :
1. **Discipline** : créer `feature/us-XXX-…` avant toute édition ; vérifier `git branch --show-current` avant le 1er commit.
2. **Enforcement (recommandé, règle 11 : hooks = requirements)** : ajouter au hook `.githooks/pre-commit` un refus (`exit 2`) si `git branch --show-current` = `main`.

**Critères** :
- [ ] Hook refuse un commit sur `main` (message explicite, `exit 2`)
- [ ] Testé (commit tenté sur `main` bloqué ; sur `feature/*` autorisé)
- [ ] **Critère sprint** : 0 commit sur `main` en local pendant S24

---

### T-TECH-02 : Suivi CodeQL `Analyze` (report rétro S23)
- **Type** : [OPS] · **Estimation** : 1h · **Quand** : mi/fin de sprint
- **Raison** : `Analyze` (default-setup) fluctue (infra) ; cache `codeql-overlay-status-*` purgé en S23.

**Critères** :
- [ ] Reconfirmer la stabilité sur quelques exécutions
- [ ] Si toujours instable → basculer en **advanced-setup** avec `continue-on-error` documenté, ou confirmer explicitement le check comme non-requis (documenté)

---

## Arbitrage produit

### T-TECH-03 : Base de décompte du solde d'absences (ACTION-4, report S22)
- **Type** : [DOC] · **Estimation** : 1h · **Quand** : affinage mi-sprint
- **Raison** : le solde d'impact (US-091b) est en **jours ouvrés**, le compteur persisté en **span calendaire** — écart d'affichage non tranché.

**Description** : décision PO — **harmoniser** en jours ouvrés partout **ou** **documenter** l'écart (note/ADR). Créer une US de correction le cas échéant.

**Critères** :
- [ ] Décision tracée (ADR ou note dans la fiche absences)
- [ ] Story créée si harmonisation retenue

---

## Résumé

| ID | Type | Chantier | Heures | Priorité |
|----|------|----------|--------|----------|
| T-TECH-01 | [OPS] | Branche d'abord (garde-fou pre-commit) | 1h | 🔴 Must (J1) |
| T-TECH-02 | [OPS] | Suivi stabilité CodeQL | 1h | 🟡 Should |
| T-TECH-03 | [DOC] | Arbitrage PO solde absences | 1h | 🟢 Could |
| **TOTAL** | | | **~3h** | |
