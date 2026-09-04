# Note de reprise — Sprint 9 (à lire après un `/clear`)

> Handoff pour **lancer le développement du Sprint 9** dans un contexte frais. Tout le planning est
> committé et mergé dans `main` ; aucun code S9 n'est encore écrit.

## TL;DR
- **Sprint 9 = Finance & rentabilité (EPIC-005)**, premier incrément. Goal : marge réelle par projet à la
  clôture + budget vs réalisé (alerte dérive) + tableau de bord finance consolidé (direction, HAB-1).
- **Décision d'entrée actée (PO)** : *produit facturable = **CA reconnu*** (déjà figé au S8 dans
  `fact_project_revenue`). Marge = CA reconnu − coût valorisé. → **Formaliser l'ADR léger en tout premier
  (T-071-08)** avant de coder US-071.
- **Backlog engagé : 21 pts** — US-071 (8), US-072 (5), US-073 (8) ; US-074 (5) en **réserve**.
- État : `main` à jour, `make ci` vert (470 tests), **aucune PR ouverte**.

## Où tout se trouve
- Sprint goal : `project-management/sprints/sprint-009-finance-rentabilite/sprint-goal.md`
- Tâches : `.../tasks/` (README + US-071..074 + technical-tasks) · Board : `.../task-board.md`
- US détaillées : `project-management/backlog/user-stories/US-071..074-*.md`
- EPIC : `project-management/backlog/epics/EPIC-005-finance-rentabilite.md`

## Point d'entrée dev (ordre conseillé)
1. **T-071-08** `[DOC]` ADR léger « facturable = CA reconnu » (`docs/adr/`).
2. **US-071** (moteur de marge) : T-071-01 entité `ProjectMargin` → 02 migration → 03 `MarginCalculator` →
   04 `ComputeProjectMargins` (à la clôture) → 05 branchement clôture (async) → 06 lecture+gating → 07 tests → 09 revue.
3. **US-072** (budget vs réalisé + dérive), puis **US-073** (dashboard consolidé `/finance`).
4. **T-TECH-01** (recette données peuplées — action rétro, priorité qualité) à caser tôt.
5. `/sprint:dev US-071` pour dérouler en TDD.

## Réutilisation Sprint 8 (ne pas réinventer)
- **CA reconnu par projet** : `fact_project_revenue` (grain tenant/période/projet) ; événement `RevenueRecognized`.
- **Coût/CA par projet** : `DoctrineTimeEntryValuationRepository::projectBreakdownFor()` + DTO `ProjectValuationLine` (join `time_entry_valuation ↔ time_entry`).
- **Clôture** déclencheur : `PeriodClosed` (US-057) ; async via messenger + **coalescence** `AnalyticsRebuildScheduler` (T-060-09) comme modèle.
- **Gating coût/marge** : permission `VIEW_COLLABORATOR_COST` (détail) / `VIEW_PROJECT_FINANCIALS` (accès) ; page 403 habillée ; lecture sensible tracée (HAB-6).
- **Index** : `idx_time_entry_project` (T-060-09) pour les jointures projet.

## Pièges connus (mémoire projet — à re-connaître)
- **TOUT en Docker** : `make ci|test|analyse|migrate|tailwind`. Jamais `php`/`composer` sur l'hôte.
- **PHPStan / cache dev** : `make analyse` exige `var/cache/dev/App_KernelDevDebugContainer.xml` ; un
  `cache:clear` intermédiaire le supprime → régénérer : `docker compose run --rm -e APP_DEBUG=1 app php bin/console cache:warmup`.
- **Tests fonctionnels + `/finance`** : tout test atteignant un écran finance avec des données valorisées
  déclenchera occupation/valorisation → penser au schéma (`AbsenceRequest`, `Project`, `TimeEntry` selon le cas).
- **Migrations Doctrine** : déclarer l'entité (mapping `src/Domain` ou mapping dédié) puis
  `doctrine:migrations:diff` ; vérifier `schema:validate` en sync ; RLS tenant sur les tables métier.
- **Style** : anticiper les allers-retours `cs-fixer`↔`rector` (imports, `new class ()`, ternaires).
- **Env test** : `not_compromised_password` désactivé ; pool `cache.rate_limiter` en `array` (config when@test).
- **Voir aussi** la mémoire projet (`MEMORY.md`) : boucle dev front, `make up`+APP_SECRET, alias curl→curlie.

## Décisions ouvertes à trancher en cours de sprint
- Source « produit facturable » : proxy CA reconnu **acté** (ADR à écrire). Raccord « facturé réel » = tranche EPIC-005 ultérieure.
- Perf dashboard consolidé : éventuelle projection dédiée `fact_project_margin` si nécessaire (ne pas dupliquer le moteur — ARC-6).
- Seuil de dérive : US-018 si dispo, sinon paramètre tenant (défaut 5 pts, OBJ-6).

## Commande de reprise
`cat project-management/sprints/sprint-009-finance-rentabilite/task-board.md` · `gh pr list` (vide) ·
`/sprint:dev US-071` (après l'ADR T-071-08).
