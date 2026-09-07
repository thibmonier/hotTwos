# Tâches Techniques Transverses - Sprint 015

## Infrastructure / Process

### T-TECH-01 : Créer la branche de travail avant tout code
- **Type** : [OPS] · **Estimation** : 0.25h

**Description** : convention run-sprint = 1 PR / story, TDD, merge squash. **Créer la branche
AVANT de coder** (piège récurrent S13). Base = `main` (post-clôture S14, PR #79).

```bash
git checkout main && git pull
git checkout -b feature/us-079c-courbe-atterrissage   # puis feature/us-079b-seuil-par-type
```

**Critères** :
- [ ] Une branche par story, partie de `main` à jour
- [ ] Nommage `feature/us-079x-...`

---

## Qualité / Clôture

### T-TECH-02 : Revue de clôture EPIC-002 + `make ci` vert
- **Type** : [REV] · **Estimation** : 2h · **Dépend de** : toutes les tâches US-079c + US-079b

**Description** : à l'issue des deux stories, EPIC-002 est complet à 100 %. Revue de clôture et
gate qualité complet.

**Checklist** :
- [ ] `make ci` vert : PHPStan **max**, **Deptrac** (UI ⇏ Infra), **gitleaks**
- [ ] Couverture **≥ 80 %** (`make coverage`)
- [ ] Invariant US-079c : `ComputeProjectMargins` inchangé (test présent)
- [ ] Invariant US-079b : repli constantes OBJ-2 ; gating HAB-1 préservé
- [ ] Migrations + RLS présentes pour `ChargeLandingSnapshot` et `ChargeDriftThreshold`
- [ ] `sprint-status.yaml` mis à jour (US-079b/c → done) + note clôture EPIC-002

---

## Rappels de pièges (mémoire projet)

| Piège | Mitigation |
|-------|------------|
| Test fonctionnel atteignant le pilotage/fiche projet | Ajouter les nouvelles tables (`ChargeLandingSnapshot`, `ChargeDriftThreshold`) au `SchemaTool` du test |
| UI dépendant de l'Infra | Repli par **constante sur le port du Domaine** (Deptrac) |
| Boucle dev front (debug=false + worker) | `make tailwind` + `cache:clear` + restart app pour voir une modif CSS |
| CSRF | Valider le jeton **avant** toute écriture / `.first()` |
| Re-clôture de période | Snapshot **idempotent** par (tenant, projet, période) : remplacer, pas ajouter |

---

## Notes
- **Solo dev** : cérémonies = jalons documentaires. Pas de tâches Flutter/mobile ni API Platform
  (feature server-rendered Twig).
- **Ordre d'exécution** : US-079c (Must) → US-079b (Should).
- Réserve si capacité restante (~8 pts sous la vélocité ~18) : entamer une story EPIC-001
  (onboarding EF-REF-29) **après affinage** — hors périmètre engagé.
