# Sprint Review — Sprint 4 : Valorisation automatique du temps validé

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Base git | `feature/sprint-4-planning` (Sprints 1-3 mergés) |
| Animateur | Scrum Master |
| Contexte | Développement piloté IA (revues croisées `security-auditor` + `symfony-reviewer`) |

## Sprint Goal

> « Dès qu'un temps est validé, il est automatiquement valorisé — coût de revient et taux de vente
> en vigueur à la période — et le modèle analytique reflète la marge du projet. »

**Atteint : ✅ OUI**

Justification : la chaîne `saisie → validation → valorisation → projection` est bouclée. Les faits
`fact_project_revenue`, jusqu'ici alimentés par une sonde, sont désormais **réels** (temps validé ×
taux figé). L'invariant `INV-2` (snapshot figé) et la non-divergence `ARC-113` sont prouvés par
tests ; la performance `ENF-PERF-5` est couverte par un smoke de débit.

## User Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| US-010 | Structure organisationnelle et rattachements historisés | 5 | ✅ | ✅ Livré |
| US-011 | Référentiel de profils : coûts & taux de vente historisés | 8 | ✅ | ✅ Livré |
| US-060 | Valorisation automatique après validation (≤ 15 min) | 8 | ✅ | ✅ Livré |

**Livré : 21/21 points (100 %)**

### Enablers techniques (hors points)

| ID | Titre | Statut |
|----|-------|--------|
| T-TECH-01 | Socle Messenger async (transport Doctrine, middleware tenant) | ✅ Livré |
| T-TECH-02 | RLS sur `project` et `time_entry` + test d'intrusion | ✅ Livré |

## User Stories non terminées

Aucune. Le périmètre engagé est intégralement livré.

## Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés | 21 | - |
| Points livrés | 21 | - |
| Vélocité | 21 | ↘️ légère (S1=29, S2=20, S3=23) — dans la cible 20-40 |
| Taux de complétion | 100 % | ↗️ |
| Tests (suite) | 257 | ↗️ (~220 → 257 sur US-060) |
| PHPStan (max) / Deptrac | 0 / 0 | ➡️ stable |
| Vulnérabilité détectée & corrigée | 1 [Élevé] | — (isolation multi-tenant, chemin worker) |

## Démonstration (scénario de bout en bout)

```gherkin
Given un collaborateur a saisi des temps sur un projet
  And un profil tarifaire est défini (coût 450 €/j, vente 780 €/j)
When le chef de projet valide les imputations
Then TimeEntriesValidated est publié (async) et consommé ≤ 15 min
  And chaque imputation est valorisée avec le taux figé (snapshot INV-2/3)
  And un RevenueRecognized réel alimente fact_project_revenue (marge visible)
  And le dashboard /valorisation affiche fraîcheur, avancement, CA et alerte

Given un taux manquant pour un profil
When l'administrateur renseigne le tarif
Then les imputations « missing_rate » sont re-valorisées automatiquement (CA-4)

Given une période comptable clôturée
When un rôle habilité tente POST /api/valorisation/recompute?period=…
Then l'API renvoie 423 Locked, aucun recalcul n'est effectué (CA-5)
```

Ordre de démo suggéré : (1) chaîne validation→valorisation→dashboard, (2) re-déclenchement auto sur
ajout de tarif, (3) verrou 423 sur période clôturée, (4) audit trail du taux (rôle contrôle de gestion).

## Feedback (revues croisées)

### Positif
- Non-divergence **par construction** : projecteur et `DivergenceChecker` partagent un réducteur unique.
- Snapshot figé rigoureux : aucune réécriture rétroactive (CA-2 prouvé).
- Conception jugée **production-ready** par les deux revues.

### À améliorer (détecté en revue, traité)
- **[Élevé] Isolation multi-tenant dans le worker async** : `app.current_tenant` (pivot RLS) n'était
  posé qu'en HTTP → écriture worker sans contexte RLS. **Corrigé** (`TenantContextMiddleware`) +
  couvert par un test d'intrusion via consume. Bénéfice **socle**.

### Suivi (non bloquant)
- Uniformiser `sprintf`→`set_config` (paramètre lié) sur les 3 points de pose du tenant.
- Clôture de période **par tenant** (dépend d'US-057) : le stub actuel est global.
- Coût/marge dans le modèle en étoile (nouveau fait) : aujourd'hui le coût est porté par
  `TimeEntryValuation` + dashboard.

## Impact sur le Backlog

| Action | US | Description |
|--------|-----|-------------|
| Dépendance confirmée | US-057 | Clôture/réouverture de période par tenant (stub à remplacer) |
| Extension à arbitrer | US-005+ | Fait « coût/marge » dans l'étoile analytique |
| Dette de suivi | — | Lot `set_config` (hardening transverse socle) |

## Prochaines étapes

1. Rétrospective du sprint (`/workflow:retro`).
2. Traiter le reliquat de suivi (lot `set_config`) en début de sprint suivant ou en tech-debt.
3. Planifier le sprint 5 (`/project:decompose-tasks 005`), en gardant US-057 en vue pour lever le stub de clôture.
