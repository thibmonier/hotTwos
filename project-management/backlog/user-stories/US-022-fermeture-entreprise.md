# US-022: Périodes de fermeture entreprise

## Métadonnées
- **ID**: US-022
- **EPIC**: EPIC-001
- **Sprint**: 17
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P-ADMIN (administrateur tenant)
- **Créé le**: 2026-09-07
- **Mis à jour**: 2026-09-07 (affinage S17)

## Traçabilité
- **Implémente**: EF-REF-9 (fermeture entreprise)
- **Dépend de**: US-012 (jours fériés & `Domain\Calendar\WorkingDaysCalculator`)
- **Réutilise / ne re-spécifie pas** : le calcul unifié des jours ouvrés (US-012) et les absences (US-054).

## User Story

**En tant qu'** administrateur tenant,
**je veux** déclarer des **périodes de fermeture** de l'entreprise (plage de dates + libellé),
**afin que** ces jours soient exclus des jours ouvrés (capacité, occupation, complétude) pour l'ensemble des collaborateurs, sans saisir chaque jour.

## Contexte (Conversation)
S16 a livré les jours fériés (`Holiday`) et le calcul unifié `WorkingDaysCalculator` (week-end +
fériés). Cette US ajoute une notion de **plage** (fermeture) traitée comme jours non ouvrés
supplémentaires à l'échelle du tenant. Une fermeture prime sur les calendriers individuels (US-021).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : les jours d'une fermeture sont exclus des jours ouvrés
```gherkin
GIVEN une fermeture entreprise déclarée du 23/12/2027 au 31/12/2027 inclus
WHEN le système calcule les jours ouvrés de décembre 2027
THEN chaque jour ouvré (hors week-end/férié) de cette plage est retiré du total
  AND aucun de ces jours n'est compté comme ouvré sur les écrans (occupation, complétude, activité)
```

### CA-2 (Nominal) : la fermeture réduit la capacité de tous les collaborateurs
```gherkin
GIVEN une semaine contenant 3 jours de fermeture entreprise
  AND un collaborateur sans absence
WHEN le tableau d'occupation calcule sa capacité pour cette semaine
THEN la capacité est réduite des jours ouvrables tombant dans la fermeture
  AND cela s'applique identiquement à tous les collaborateurs du tenant
```

### CA-3 (Alternatif) : l'administrateur gère les fermetures
```gherkin
GIVEN un administrateur (MANAGE_ORGANIZATION) sur la page des fermetures
WHEN il ajoute une fermeture (début, fin, libellé) puis consulte la liste
THEN la fermeture apparaît, triée par date de début
  AND il peut la supprimer ; elle n'impacte alors plus les jours ouvrés futurs
```

### CA-4 (Alternatif) : isolation multi-tenant
```gherkin
GIVEN le tenant A a déclaré une fermeture et le tenant B non
WHEN on calcule les jours ouvrés de la période pour chacun
THEN la plage est exclue pour A mais pas pour B (RLS)
```

### CA-5 (Erreur) : fin antérieure au début refusée
```gherkin
GIVEN l'administrateur crée une fermeture
WHEN la date de fin est antérieure à la date de début
THEN l'enregistrement est refusé avec un message explicite
  AND aucune fermeture n'est créée
```

### CA-6 (Erreur) : accès refusé sans habilitation
```gherkin
GIVEN un utilisateur sans MANAGE_ORGANIZATION
WHEN il tente d'accéder à la page des fermetures (GET ou POST)
THEN l'accès est refusé (403)
```

## Notes techniques (pour la décomposition)
- **Entité** `Domain\Calendar\ClosurePeriod` (`TenantOwned`) : `startDate`, `endDate` (date_immutable, bornes incluses), `label` ; garde `endDate >= startDate` ; RLS + migration.
- **Port** `ClosurePeriodRepository` : `save`, `delete`, `find`, `findForTenant` (tri début), `allForTenant` (pour le calculateur).
- **`WorkingDaysCalculator`** : `isWorkingDay()` renvoie aussi `false` si le jour tombe dans une fermeture ; charger les fermetures une fois (cache par tenant, comme les fériés).
- **UI** : page `/parametrage/fermetures` (Twig, gating `MANAGE_ORGANIZATION`, CSRF).
- **Tests** : unit `WorkingDaysCalculator` (plage exclue) ; fonctionnels CRUD + 403 + impact occupation ; **ajouter `ClosurePeriod::class` aux SchemaTool** des tests occupation/complétude/activité (piège récurrent).
- **Hors périmètre** (noter) : blocage de la saisie productive sur jours fermés (interaction timesheet) — à traiter séparément si nécessaire.

## Definition of Ready
- [x] Description INVEST + Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Réutilise `WorkingDaysCalculator` (pas de duplication)
- [x] Estimation 5 pts ; RLS/gating explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] Migration + RLS ; code review
