# US-017: Circuit de validation des absences paramétrable

## Métadonnées
- **ID**: US-017
- **EPIC**: EPIC-001
- **Sprint**: 17
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P-ADMIN (administrateur) / manager valideur
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-07 (affinage S17 — recadrage sur un flux pilote : les absences)

## Traçabilité
- **Implémente**: EF-REF-25 (circuit de validation : nombre d'étapes & validateurs) — **tranche pilote : absences**
- **Dépend de**: US-001 (multi-tenant), US-054 (déclaration/validation des absences)
- **Réutilise / ne re-spécifie pas** : le décideur d'absence existant (`Application\Absence\DecideAbsence`, permission `VALIDATE_ABSENCE`, garde anti-auto-décision) et le pattern de config tenant (`ReminderRule`).
- **Reporté (hors périmètre, US ultérieures)** :
  - **EF-REF-24** — transitions de statut paramétrables (refonte de `ProjectStatus::canTransitionTo`) : chantier distinct.
  - Circuits pour les **temps** (US-055) et les **réouvertures de période** (US-057) : restent mono-étape.
  - Résolution **hiérarchique N+1** réelle (via `OrgUnit`) et **délégation/escalade/seuils** : validateurs désignés **par rôle** ici.

## User Story

**En tant qu'** administrateur tenant,
**je veux** paramétrer le **circuit de validation des absences** (une ou deux étapes, avec le rôle validateur de chaque étape),
**afin d'** adapter le niveau de contrôle des absences aux règles de mon organisation, sans développement.

## Contexte (Conversation)
Aujourd'hui la validation d'absence est **mono-étape** : tout titulaire de `VALIDATE_ABSENCE` décide
(`DecideAbsence`). Cette US rend le circuit **configurable** : 1 étape (comportement actuel) ou 2 étapes
successives, chacune requérant un **rôle validateur** défini. Une absence n'est `VALIDATED` qu'après
approbation de **toutes** les étapes, dans l'ordre ; un refus à n'importe quelle étape la `REJECTED`.
Modèle volontairement borné (≤ 2 étapes, validateurs par rôle) pour tenir le périmètre.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : circuit par défaut à 1 étape (comportement actuel préservé)
```gherkin
GIVEN aucun circuit d'absence n'est configuré (défaut = 1 étape, rôle validateur d'absence)
  AND une demande d'absence en attente
WHEN un validateur habilité l'approuve
THEN l'absence passe directement à « validée »
```

### CA-2 (Nominal) : circuit à 2 étapes — validée seulement après les deux approbations
```gherkin
GIVEN un circuit d'absence configuré en 2 étapes (étape 1 = rôle « Chef de projet », étape 2 = rôle « Dirigeant »)
  AND une demande d'absence en attente
WHEN un utilisateur ayant le rôle de l'étape 1 l'approuve
THEN l'absence reste « en attente » à l'étape 2 (pas encore validée)
WHEN un utilisateur ayant le rôle de l'étape 2 l'approuve à son tour
THEN l'absence passe à « validée »
```

### CA-3 (Alternatif) : l'administrateur configure le circuit
```gherkin
GIVEN un administrateur (MANAGE_ORGANIZATION) sur la page des circuits de validation
WHEN il définit 2 étapes avec un rôle validateur pour chacune
THEN le circuit est enregistré pour le tenant et s'applique aux nouvelles décisions
  AND il peut revenir à 1 étape
```

### CA-4 (Alternatif) : un refus à une étape rejette l'absence
```gherkin
GIVEN un circuit d'absence à 2 étapes et une demande approuvée à l'étape 1
WHEN un validateur de l'étape 2 refuse (motif obligatoire)
THEN l'absence passe à « refusée » (le circuit s'arrête)
```

### CA-5 (Erreur) : validateur au mauvais rôle pour l'étape courante refusé
```gherkin
GIVEN un circuit à 2 étapes, une demande à l'étape 1 (rôle « Chef de projet »)
WHEN un utilisateur sans le rôle requis par l'étape courante tente d'approuver
THEN l'action est refusée (403 / erreur d'autorisation) et l'étape n'avance pas
  AND la garde anti-auto-décision reste appliquée (on ne valide pas sa propre absence)
```

### CA-6 (Erreur) : configuration invalide refusée
```gherkin
GIVEN l'administrateur configure le circuit
WHEN il déclare 0 étape, plus de 2 étapes, ou une étape sans rôle validateur
THEN l'enregistrement est refusé avec un message explicite ; aucune config invalide n'est enregistrée
  AND l'accès à la page de config est réservé à MANAGE_ORGANIZATION (403 sinon)
```

## Notes techniques (pour la décomposition)
- **Entité** `Domain\Validation\AbsenceValidationCircuit` (`TenantOwned`) : `steps` (JSON, liste ordonnée de rôles, 1..2) ; unique (tenant) ; factory `default(TenantId)` (1 étape) ; `reconfigure(steps)` avec gardes (1..2, rôle non vide) ; RLS + migration. (Pattern `ReminderRule`.)
- **État d'avancement** : ajouter à `AbsenceRequest` un `currentStep` (int, défaut 1) ; `approve()` avance l'étape ; `VALIDATED` quand `currentStep > nbÉtapes`. Migration additive.
- **`DecideAbsence`** : résout le circuit du tenant ; à l'approbation, vérifie que l'acteur possède le **rôle de l'étape courante** (via `Authorizer`/rôles), avance l'étape, valide au terme ; refus → `REJECTED` (inchangé). Garde anti-auto-décision conservée.
- **UI** : page `/parametrage/circuits-validation` (Twig, gating `MANAGE_ORGANIZATION`, CSRF) — choix du nb d'étapes + rôle par étape (liste des rôles du tenant).
- **Tests** : unit (avancement d'étapes, refus, mauvais rôle, repli 1 étape) ; fonctionnels (config CRUD, parcours 2 étapes, 403) ; **ajouter `AbsenceValidationCircuit::class` + le champ `currentStep`** aux SchemaTool des tests d'absence.
- **Découpe si dérapage** : livrer d'abord le moteur (config + 2 étapes + décision) sans UI riche ; l'UI ensuite.

## Definition of Ready
- [x] Description INVEST recadrée (flux pilote absences ; EF-REF-24 statuts & autres flux reportés)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Modèle borné arrêté (≤ 2 étapes, validateurs par rôle) ; réutilise `DecideAbsence` + pattern `ReminderRule`
- [x] Estimation 8 pts ; RLS/gating explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] Migration + RLS (circuit) + migration additive (`currentStep`) ; code review

## Notes
Recadrage assumé : « statuts & circuits paramétrables » est vaste. Cette US livre un **moteur de circuit
multi-étapes** sur le flux **absences** (manque le plus criant : la validation ne vérifie aujourd'hui que
la permission, pas d'étapes). Les **transitions de statut paramétrables** (EF-REF-24) et l'extension aux
autres flux feront l'objet d'US dédiées.
