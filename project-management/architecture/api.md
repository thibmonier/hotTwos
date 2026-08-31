> **Backend for Frontend / Adaptateurs :** cette API interne alimente aussi bien les Live Components Turbo (rendu serveur) que les intégrations tierces et le futur portail public (lot 3). C'est le **seul** point d'accès aux cas d'usage — aucune logique métier n'est dupliquée entre le rendu web et l'API (`ARC-15/16/17`).
# Conception API — HotOnes (Lot 1)

**Périmètre :** Lot 1 — Socle (EPIC-000), Référentiels (EPIC-001 / `REF`), Projets (EPIC-002 / `PRJ`), Temps (EPIC-003 / `TMP`).
**Stack :** API Platform 4.3.x, mode **DTO strict** (`ADR-4`) — providers/processors sur mesure, **aucune exposition directe d'entité Doctrine** (`ARC-18`).
**Statut :** conception initiale, à valider en revue technique avant `/project:decompose-tasks`.

---

## 1. Principes

| Principe | Détail |
|---|---|
| **API unique, deux consommateurs** | Une seule API interne cohérente sert le rendu serveur Twig/Stimulus/Turbo **et** les intégrations tierces (`ARC-7`). Pas de front découplé, pas de duplication de règles métier côté client (`ARC-27`). |
| **DTO strict (`ADR-4`)** | Chaque ressource API Platform est un DTO (input/output), jamais une entité Doctrine. Chaque opération est portée par un **provider** (lecture) ou un **processor** (écriture) sur mesure qui invoque un cas d'usage de la couche applicative. Aucune règle métier, validation ou habilitation dans l'adaptateur (`ARC-18/19`). |
| **API interne dès le lot 1, API publique au lot 3** | Le périmètre ci-dessous est l'API interne : elle n'est pas encore un contrat public versionné et documenté pour des tiers externes. L'API publique contractuelle (SLA, clés API partenaires, quotas dédiés) est planifiée au lot 3, en s'appuyant sur cette même base (`ARC-7`, `ADR-4`). |
| **Cas d'usage invocables sans HTTP** | Tout provider/processor délègue à un cas d'usage (`UseCase`/`Handler`) testable indépendamment du transport HTTP — également invocable en CLI ou depuis le bus de messages asynchrones (`ARC-15/16/17`). |
| **Versionnement** | Préfixe `/api/v1/...` dès le lot 1 (anticipation du lot 3). Un changement cassant = nouvelle version de ressource (`/api/v1/`→`/api/v2/`), jamais de rupture silencieuse. Dépréciation via en-têtes `Deprecation` / `Sunset` (cf. `.claude/rules/` conventions API). |
| **Cohérence chiffrée** | Aucun calcul financier ou capacitaire n'est effectué dans un contrôleur, un processor ou un gabarit Twig : tout passe par le moteur de calcul unique testé (`ARC-6`). Les DTO de sortie exposent des valeurs déjà calculées par ce moteur. |
| **Multi-tenant** | Le tenant est résolu et posé en tout début de requête (middleware/subscriber), jamais porté par le client (`ARC-61`). Toute requête sans tenant résolu est rejetée avant d'atteindre un provider/processor. |

---

## 2. Authentification & autorisation

### 2.1 Deux mécanismes d'authentification

| Consommateur | Mécanisme | Détail |
|---|---|---|
| **UI web (Turbo)** | **Session serveur** | Cookie de session `httpOnly`, `secure`, `sameSite=strict` (cf. `11-security.md`). Pas de JWT côté navigateur : le rendu Turbo réutilise directement le contexte de session posé par le firewall Symfony. Expiration 15-30 min d'inactivité, renouvelée après connexion. |
| **Intégrations / API publique (lot 3)** | **Jeton porteur (Bearer token)** | Jeton opaque ou JWT signé **EdDSA (Ed25519)** émis par tenant, scope explicite (`read:timesheets`, `write:projects`, ...), expiration courte + refresh. Jamais de HS256. Un jeton n'appartient qu'à **un seul tenant** (`HAB-4`) — aucune traversée inter-tenant possible même en cas de vol de jeton. |

Les deux mécanismes convergent vers le **même contexte applicatif** (`AuthenticatedUser` + `TenantContext`) avant d'atteindre la couche applicative — aucune branche de code métier différenciée par mécanisme d'authentification.

### 2.2 Résolution du tenant (`ARC-61`)

1. Le tenant est déterminé en tout début de requête (sous-domaine, en-tête `X-Tenant-Id` pour les intégrations, ou session pour l'UI).
2. Le discriminant tenant est posé sur la connexion (filtre ORM `ARC-33` + RLS PostgreSQL `ARC-34`, double barrière `ADR-6`).
3. En fin de requête (mode worker `ARC-47/50`), le contexte tenant est explicitement effacé — aucun état résiduel entre deux requêtes traitées par le même worker.

### 2.3 Habilitation dans la couche applicative, pas dans l'adaptateur (`ARC-19`, `ENF-SEC-5`)

- Un **voter applicatif** (pas un simple contrôle d'affichage) est invoqué par chaque cas d'usage, **avant** l'accès aux données — jamais après, jamais uniquement pour masquer un champ dans la réponse.
- Exemple : `HAB-1` (coût jamais visible d'un CP) est appliqué en filtrant la **requête** de lecture du coût, pas en retirant le champ `coutJournalier` du DTO de sortie a posteriori.
- Les endpoints IA appliquent la même règle **à la source du contexte transmis au modèle** (`HAB-5`, `ARC-9`) — jamais un filtre sur la réponse générée.
- Toute lecture de donnée RH sensible ou de coût est tracée (`HAB-6`) via un événement de journal d'audit émis par le cas d'usage, pas par l'adaptateur HTTP.

### 2.4 Réponses d'erreur d'authentification/autorisation

| Code | Cas |
|---|---|
| `401 Unauthorized` | Session expirée / jeton absent ou invalide |
| `403 Forbidden` | Utilisateur authentifié mais non habilité pour la ressource/l'action demandée (y compris filtrage HAB-5 à la source pour l'IA) |

---

## 3. Endpoints REST — Lot 1

Convention de chemin : `/api/v1/{ressource}`. DTO nommés `{Ressource}Input` (écriture) / `{Ressource}Output` (lecture). Habilitation exprimée en rôle/voter logique (le détail RBAC est porté par US-003, hors périmètre de ce document).

### 3.1 Module Référentiels (`REF`)

#### Unités organisationnelles & rattachements (US-010)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/org-units` | — (query: `level`, `active`) | `OrgUnitOutput[]` (paginé) | `ROLE_ADMIN`, lecture large pour tous les rôles authentifiés |
| `POST` | `/api/v1/org-units` | `OrgUnitInput` | `OrgUnitOutput` | `ROLE_ADMIN` |
| `PATCH` | `/api/v1/org-units/{id}` | `OrgUnitPatchInput` | `OrgUnitOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/org-units/{id}/deactivate` | `DeactivateInput` (motif) | `OrgUnitOutput` | `ROLE_ADMIN` |
| `GET` | `/api/v1/collaborators/{id}/org-memberships` | — | `OrgMembershipOutput[]` (historisé, `effective_from`/`effective_to`) | `ROLE_ADMIN`, `ROLE_MANAGER` (périmètre) |
| `POST` | `/api/v1/collaborators/{id}/org-memberships` | `OrgMembershipInput` (unité, `effective_from`) | `OrgMembershipOutput` | `ROLE_ADMIN` |

#### Profils, coûts et taux historisés (US-011)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/profiles` | — | `ProfileOutput[]` | tous rôles authentifiés (le **coût** n'est jamais dans ce DTO pour un CP — `HAB-1`) |
| `POST` | `/api/v1/profiles` | `ProfileInput` | `ProfileOutput` | `ROLE_ADMIN` |
| `GET` | `/api/v1/profiles/{id}/rate-history` | — | `ProfileRateHistoryOutput[]` | `ROLE_ADMIN` (coûts inclus) ; `ROLE_MANAGER` reçoit une projection sans `coutRevient` |
| `POST` | `/api/v1/profiles/{id}/rate-history` | `ProfileRateEntryInput` (`effectiveFrom`, `sellingPrice`, `costPrice?`, `calculationMode`) | `ProfileRateHistoryOutput` | `ROLE_ADMIN` |
| `PATCH` | `/api/v1/profiles/{id}/rate-history/{entryId}` | `ProfileRateEntryPatchInput` (rétroactif, avec `confirmRetroactive: true`) | `ProfileRateHistoryOutput` | `ROLE_ADMIN` |

#### Calendriers & absences (US-012)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/calendars` | — | `CalendarOutput[]` | tous rôles authentifiés |
| `POST` | `/api/v1/calendars` | `CalendarInput` | `CalendarOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/calendars/company-closures` | `CompanyClosureInput` (dates) | `CompanyClosureOutput` | `ROLE_ADMIN` |
| `GET` | `/api/v1/absence-types` | — | `AbsenceTypeOutput[]` | tous rôles authentifiés |
| `POST` | `/api/v1/absence-types` | `AbsenceTypeInput` (impact capacité, circuit) | `AbsenceTypeOutput` | `ROLE_ADMIN` |
| `DELETE` | `/api/v1/absence-types/{id}` | — | `409` si référencé (proposition de désactivation) | `ROLE_ADMIN` |

#### Compétences (US-013)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/skills` | query: `category`, `q` | `SkillOutput[]` | tous rôles authentifiés |
| `POST` | `/api/v1/skills` | `SkillInput` | `SkillOutput` | `ROLE_ADMIN`, `ROLE_RESOURCE_MANAGER` |
| `POST` | `/api/v1/collaborators/{id}/skills` | `CollaboratorSkillInput` (niveau) | `CollaboratorSkillOutput` | `ROLE_RESOURCE_MANAGER`, `ROLE_ADMIN` |
| `GET` | `/api/v1/skills/search` | query: critères multiples | `CollaboratorMatchOutput[]` | `ROLE_RESOURCE_MANAGER` |

#### Comptes clients & contacts (US-014)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/client-accounts` | query: `q` (nom/SIREN) | `ClientAccountOutput[]` (paginé) | `ROLE_COMMERCIAL`, `ROLE_ADMIN`, `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/client-accounts` | `ClientAccountInput` (type Groupe/Filiale, `parentAccountId?`) | `ClientAccountOutput` | `ROLE_COMMERCIAL`, `ROLE_ADMIN` |
| `POST` | `/api/v1/client-accounts/{id}/contacts` | `ContactInput` (rôle, statut) | `ContactOutput` | `ROLE_COMMERCIAL`, `ROLE_ADMIN` |
| `POST` | `/api/v1/client-accounts/{id}/merge` | `MergeAccountInput` (compteSourceId) | `ClientAccountOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/client-accounts/{id}/deactivate` | `DeactivateInput` (motif) | `ClientAccountOutput` | `ROLE_ADMIN` |

#### Taux de vente & priorité (US-015)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/rates/resolve` | query: `profileId`, `clientId?`, `projectId?`, `date?` | `RateResolutionOutput` (taux effectif + les 3 niveaux + priorité appliquée) | `ROLE_COMMERCIAL`, `ROLE_ADMIN`, `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/client-accounts/{id}/rates` | `ClientRateInput` | `ClientRateOutput` | `ROLE_ADMIN`, `ROLE_COMMERCIAL` |
| `POST` | `/api/v1/projects/{id}/rates` | `ProjectRateInput` | `ProjectRateOutput` | `ROLE_ADMIN`, `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/rates/simulate` | `RateSimulationInput` (nouveau taux, périmètre) | `RateSimulationOutput` (impact chiffré, non persisté) | `ROLE_ADMIN` |

#### Devises & taux de change (US-016)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/currencies` | — | `CurrencyOutput[]` | tous rôles authentifiés |
| `POST` | `/api/v1/currencies` | `CurrencyInput` (activation) | `CurrencyOutput` | `ROLE_ADMIN` |
| `GET` | `/api/v1/exchange-rates` | query: `from`, `to`, `date?` | `ExchangeRateOutput[]` (historisé) | tous rôles authentifiés |
| `POST` | `/api/v1/exchange-rates` | `ExchangeRateInput` (`effectiveFrom`) | `ExchangeRateOutput` | `ROLE_ADMIN` |

#### Statuts & circuits de validation (US-017)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/workflows/{objectType}` | — (`objectType`: devis, projet, temps, ...) | `WorkflowDefinitionOutput` (états, transitions) | `ROLE_ADMIN` |
| `POST` | `/api/v1/workflows/{objectType}/states` | `WorkflowStateInput` | `WorkflowStateOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/workflows/{objectType}/transitions` | `WorkflowTransitionInput` | `WorkflowTransitionOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/validation-circuits` | `ValidationCircuitInput` (valideurs, seuils) | `ValidationCircuitOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/validation-delegations` | `DelegationInput` (délégataire, période) | `DelegationOutput` | `ROLE_MANAGER`, `ROLE_ADMIN` |

#### Seuils d'alerte (US-018)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/alert-thresholds` | — | `AlertThresholdOutput[]` | `ROLE_ADMIN`, `ROLE_CHEF_PROJET` (lecture) |
| `POST` | `/api/v1/alert-thresholds` | `AlertThresholdInput` (type, valeur, cibles) | `AlertThresholdOutput` | `ROLE_ADMIN` |
| `GET` | `/api/v1/alerts` | query: `status=active` | `AlertOutput[]` | `ROLE_ADMIN`, `ROLE_CHEF_PROJET` (périmètre) |
| `POST` | `/api/v1/alerts/{id}/acknowledge` | `AcknowledgeInput` (note obligatoire) | `AlertOutput` | `ROLE_ADMIN` |

#### Onboarding tenant (US-019)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `POST` | `/api/v1/tenants` | `TenantCreationInput` (email admin) | `TenantOutput` | anonyme (endpoint public d'inscription, rate-limité fortement) |
| `GET` | `/api/v1/tenants/current/defaults` | — | `TenantDefaultsOutput` | `ROLE_ADMIN` |
| `POST` | `/api/v1/tenants/current/onboarding/reset` | `OnboardingResetInput` (confirmation) | `TenantOutput` | `ROLE_ADMIN` |

#### Journal d'audit (US-020)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/audit-logs` | query: `author`, `period`, `objectType` | `AuditLogOutput[]` (paginé, tri date desc) | `ROLE_ADMIN`, `ROLE_DIRIGEANT` (`HAB-6`) |
| `GET` | `/api/v1/audit-logs/export` | mêmes filtres | fichier CSV | `ROLE_ADMIN`, `ROLE_DIRIGEANT` |
| `DELETE`/`PATCH` | `/api/v1/audit-logs/{id}` | — | **toujours `403`** — endpoint volontairement non implémenté en écriture (`INV-7`) | aucun rôle (immuabilité by design) |

### 3.2 Module Projets (`PRJ`)

#### Projets & cycle de vie (US-030)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects` | query: `status`, `clientId`, `q` | `ProjectOutput[]` (paginé) | `ROLE_CHEF_PROJET` (périmètre), `ROLE_ADMIN`, `ROLE_DIRIGEANT` |
| `POST` | `/api/v1/projects` | `ProjectInput` (client, responsable, budget requis — `RG-PRJ-1`) | `ProjectOutput` | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |
| `GET` | `/api/v1/projects/{id}` | — | `ProjectDetailOutput` | `ROLE_CHEF_PROJET` (affecté), `ROLE_ADMIN` |
| `POST` | `/api/v1/projects/{id}/status-transitions` | `StatusTransitionInput` (statut cible) | `ProjectOutput` | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |

#### Lots & jalons (US-031)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/work-packages` | — | `WorkPackageOutput[]` (arborescence) | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |
| `POST` | `/api/v1/projects/{id}/work-packages` | `WorkPackageInput` (parent optionnel, budget j/€) | `WorkPackageOutput` | `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/work-packages/{id}/reallocate` | `ReallocationInput` (cible, montant, motif) | `WorkPackageOutput[]` (source+cible) | `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/projects/{id}/milestones` | `MilestoneInput` (date, déclencheur facturation?) | `MilestoneOutput` | `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/milestones/{id}/reach` | `MilestoneReachInput` (date atteinte) | `MilestoneOutput` (+ facture créée si déclencheur) | `ROLE_CHEF_PROJET` |

#### Projets internes (US-032)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `POST` | `/api/v1/projects/{id}/requalify` | `RequalifyInput` (interne↔client, motif) | `ProjectOutput` | `ROLE_ADMIN` |

#### Budget & avenants (US-033)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/budget` | — | `ProjectBudgetOutput` (charge/montant par profil, marge calculée) | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` — `coutPrevisionnel` masqué si voter refuse `HAB-1` |
| `PUT` | `/api/v1/projects/{id}/budget` | `ProjectBudgetInput` (lignes par profil) | `ProjectBudgetOutput` | `ROLE_CHEF_PROJET` (motif requis si projet actif — `RG-PRJ-4`) |
| `GET` | `/api/v1/projects/{id}/amendments` | — | `AmendmentOutput[]` (chronologie) | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |
| `POST` | `/api/v1/projects/{id}/amendments` | `AmendmentInput` (montant, motif, date) | `AmendmentOutput` | `ROLE_CHEF_PROJET` (validation direction requise) |

#### Engagements externes (US-034)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/external-commitments` | query: `type`, `status` | `ExternalCommitmentOutput[]` | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |
| `POST` | `/api/v1/projects/{id}/external-commitments` | `ExternalCommitmentInput` (type, montant, fournisseur, statut, `workPackageId?`) | `ExternalCommitmentOutput` | `ROLE_CHEF_PROJET` (refusé si projet clôturé — `409`) |

#### Avancement & RAF (US-035)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/work-packages/{id}/progress` | — | `ProgressOutput` (avancement %, RAF, historique) | `ROLE_CHEF_PROJET`, `ROLE_ADMIN` |
| `POST` | `/api/v1/work-packages/{id}/progress` | `ProgressInput` (avancement %, date) | `ProgressOutput` | `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/work-packages/{id}/estimate-to-complete` | `EtcInput` (RAF réestimé) | `ProgressOutput` | `ROLE_CHEF_PROJET` |

#### Vue d'atterrissage (US-036)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/landing-view` | query: `groupBy=work-package?` | `LandingViewOutput` (budget/consommé/RAF/atterrissage/écart) | `ROLE_CHEF_PROJET`, `ROLE_DIRIGEANT`, `ROLE_ADMIN` |
| `GET` | `/api/v1/projects/{id}/landing-view/history` | query: `workPackageId?` | `LandingHistoryOutput[]` (courbe des projections) | `ROLE_CHEF_PROJET`, `ROLE_DIRIGEANT` |
| `GET` | `/api/v1/projects/{id}/landing-view/export` | — | fichier CSV/XLSX | `ROLE_CHEF_PROJET`, `ROLE_DIRIGEANT` |

#### Affectations (US-037)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/assignments` | — | `AssignmentOutput[]` | `ROLE_CHEF_PROJET`, `ROLE_RESOURCE_MANAGER` |
| `POST` | `/api/v1/projects/{id}/assignments` | `AssignmentInput` (collaborateur, rôle, période, charge prév.) | `AssignmentOutput` | `ROLE_CHEF_PROJET`, `ROLE_RESOURCE_MANAGER` |
| `POST` | `/api/v1/projects/{id}/assignments/exceptional-access` | `ExceptionalAccessInput` (collaborateur, période, motif) | `ExceptionalAccessOutput` | `ROLE_CHEF_PROJET` |

#### Clôture (US-038)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `POST` | `/api/v1/projects/{id}/close` | `ProjectClosureInput` (confirmation, accepte points bloquants?) | `ProjectOutput` | `ROLE_CHEF_PROJET` |
| `POST` | `/api/v1/projects/{id}/reopen` | `ProjectReopenInput` (motif, fenêtre) | `ProjectOutput` | `ROLE_ADMIN` (validation) |

### 3.3 Module Temps (`TMP`)

#### Imputations — saisie (US-050, US-051, US-052)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/timesheets/weeks/{isoWeek}` | — | `TimesheetWeekOutput` (grille jours×projets, filtrée sur affectations `HAB-5`) | collaborateur authentifié (soi-même) |
| `PUT` | `/api/v1/timesheets/weeks/{isoWeek}/entries` | `TimesheetEntryBatchInput[]` (projet, date, durée, commentaire?) | `TimesheetWeekOutput` | collaborateur authentifié (soi-même, sur projets affectés uniquement — `403` sinon, `RG-TMP-1`) |
| `POST` | `/api/v1/timesheets/weeks/{isoWeek}/duplicate-previous` | `DuplicateWeekInput` | `TimesheetWeekOutput` (non soumis) | collaborateur authentifié |
| `GET` | `/api/v1/timesheets/days/{date}` | — | `TimesheetDayOutput` | collaborateur authentifié (soi-même) — vue mobile (US-052) |
| `POST` | `/api/v1/timesheets/weeks/{isoWeek}/submit` | `TimesheetSubmitInput` | `TimesheetWeekOutput` (statut `SOUMIS`) | collaborateur authentifié |

#### Pré-remplissage IA (US-053)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/timesheets/weeks/{isoWeek}/proposal` | — | `TimesheetProposalOutput` (statut `PROPOSITION`, sources d'affectation citées — `ENF-IA-1`) | collaborateur authentifié (soi-même) |
| `POST` | `/api/v1/timesheets/weeks/{isoWeek}/proposal/confirm` | `ProposalConfirmInput` (accepter tel quel ou avec modifications) | `TimesheetWeekOutput` (statut `SOUMIS`, jamais créé sans confirmation — `EF-TMP-11`) | collaborateur authentifié |

#### Absences (US-054)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `POST` | `/api/v1/absence-requests` | `AbsenceRequestInput` (type, dates, maille — **jamais de motif médical**, `HAB-3`) | `AbsenceRequestOutput` | collaborateur authentifié |
| `GET` | `/api/v1/absence-requests` | query: `status`, `collaboratorId?` | `AbsenceRequestOutput[]` | collaborateur (soi-même) / manager (périmètre) |
| `POST` | `/api/v1/absence-requests/{id}/decision` | `AbsenceDecisionInput` (approuver/refuser, motif si refus) | `AbsenceRequestOutput` | `ROLE_MANAGER` (valideur désigné) |
| `GET` | `/api/v1/collaborators/{id}/absence-counters` | — | `AbsenceCountersOutput` (acquis/pris/en attente/solde/projeté) | collaborateur (soi-même), `ROLE_MANAGER`, `ROLE_ADMIN` |

#### Validation par lot (US-055)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/timesheets/validation-queue` | query: `projectId?`, `isoWeek` | `TimesheetValidationItemOutput[]` (filtré aux collaborateurs affectés au périmètre du valideur) | `ROLE_VALIDEUR_TEMPS` |
| `POST` | `/api/v1/timesheets/batch-validate` | `BatchValidateInput` (liste d'ids) | `BatchValidationResultOutput` | `ROLE_VALIDEUR_TEMPS` |
| `POST` | `/api/v1/timesheets/batch-reject` | `BatchRejectInput` (liste d'ids, motif obligatoire) | `BatchValidationResultOutput` | `ROLE_VALIDEUR_TEMPS` |

#### Clôture de période (US-057)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `POST` | `/api/v1/periods/{period}/close` | `PeriodCloseInput` (confirmation) | `PeriodOutput` (verrouille, déclenche valorisation async) | `ROLE_ADMIN` |
| `POST` | `/api/v1/periods/{period}/reopening-requests` | `ReopeningRequestInput` (motif) | `ReopeningRequestOutput` | `ROLE_GESTIONNAIRE_PERIODES` (`403` sinon) |
| `GET` | `/api/v1/timesheets/{id}/history` | — | `TimesheetAuditEntryOutput[]` (avant/après/motif) | `ROLE_ADMIN`, `ROLE_CHEF_PROJET` (périmètre) |

#### Complétude (US-058)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/completeness` | query: `scope=self\|team\|bu`, `weeks` | `CompletenessGridOutput` | filtré RBAC (`scope=team` → `403` pour un collaborateur simple) |
| `GET` | `/api/v1/completeness/export` | mêmes filtres | fichier CSV (anti-injection) | `ROLE_MANAGER`, `ROLE_ADMIN` |
| `POST` | `/api/v1/completeness/{collaboratorId}/{isoWeek}/remind` | — | `202 Accepted` | `ROLE_MANAGER` |

#### Synthèse & planning (US-059)

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/activity-summary` | query: `weeks` | `ActivitySummaryOutput` (répartition par projet/type, uniquement les données de l'appelant) | collaborateur authentifié (soi-même — `user_id` d'un tiers → `403`) |
| `GET` | `/api/v1/my-upcoming-schedule` | query: `weeks` | `UpcomingScheduleOutput` | collaborateur authentifié (soi-même) |

#### Valorisation (US-060) — endpoints de **lecture** uniquement

| Méthode | Chemin | DTO entrée | DTO sortie | Habilitation |
|---|---|---|---|---|
| `GET` | `/api/v1/projects/{id}/financials` | — | `ProjectFinancialsOutput` (coût réel, marge, taux d'occupation, horodatage `lastValorizedAt`) | `ROLE_CHEF_PROJET`, `ROLE_DIRIGEANT` (coûts masqués selon `HAB-1`) |
| `GET` | `/api/v1/timesheets/{id}/valuation-audit` | — | `ValuationAuditOutput` (taux snapshotté appliqué, date) | `ROLE_ADMIN`, `ROLE_DIRIGEANT` |
| `POST` | `/api/v1/valorisation/recompute` | `RecomputeInput` (period) | `202 Accepted` | `ROLE_ADMIN` — `423` si période clôturée sans réouverture |

> Ces endpoints ne déclenchent **jamais** de calcul synchrone bloquant : la valorisation est pilotée par événement de domaine (`TempsValidés` → bus de messages) après validation ou clôture (`ENF-PERF-5`, `ADR-7`).

---

## 4. Exemples requête/réponse

### 4.1 Saisie d'une imputation (POST)

```http
PUT /api/v1/timesheets/weeks/2026-W35/entries HTTP/1.1
Authorization: Bearer <session-or-token>
Content-Type: application/json

[
  { "projectId": "PRJ-0042", "date": "2026-08-24", "durationHours": 8, "comment": null },
  { "projectId": "PRJ-0042", "date": "2026-08-25", "durationHours": 8, "comment": null },
  { "projectId": "PRJ-0055", "date": "2026-08-26", "durationHours": 4, "comment": "Réunion de lancement avec le client" }
]
```

```json
HTTP/1.1 200 OK
{
  "isoWeek": "2026-W35",
  "status": "BROUILLON",
  "entries": [
    { "id": "TS-000123", "projectId": "PRJ-0042", "projectName": "Refonte SI", "date": "2026-08-24", "durationHours": 8, "comment": null },
    { "id": "TS-000124", "projectId": "PRJ-0042", "projectName": "Refonte SI", "date": "2026-08-25", "durationHours": 8, "comment": null },
    { "id": "TS-000125", "projectId": "PRJ-0055", "projectName": "Support Client X", "date": "2026-08-26", "durationHours": 4, "comment": "Réunion de lancement avec le client" }
  ],
  "totalHours": 20,
  "dailyCapCheck": { "exceeded": false }
}
```

### 4.2 Confirmation d'un pré-remplissage (POST)

```http
POST /api/v1/timesheets/weeks/2026-W35/proposal/confirm HTTP/1.1
Authorization: Bearer <session-or-token>
Content-Type: application/json

{ "mode": "AS_IS" }
```

```json
HTTP/1.1 200 OK
{
  "isoWeek": "2026-W35",
  "status": "SOUMIS",
  "source": "PROPOSITION_IA",
  "entries": [
    { "id": "TS-000201", "projectId": "PRJ-0001", "date": "2026-08-24", "durationHours": 8,
      "origin": { "assignmentId": "ASG-0091", "explanation": "Basé sur votre planning semaine 35 — affectation P-Alpha 24-26/08" } },
    { "id": "TS-000202", "projectId": "PRJ-0001", "date": "2026-08-25", "durationHours": 8,
      "origin": { "assignmentId": "ASG-0091", "explanation": "Basé sur votre planning semaine 35 — affectation P-Alpha 24-26/08" } }
  ],
  "submittedAt": "2026-08-24T09:12:03Z"
}
```

> `origin.explanation` répond à l'exigence d'explicabilité (`ENF-IA-1`) : aucun chiffre n'est généré par le modèle (`ARC-11`), seule l'affectation planifiée (donnée déterministe) est proposée puis confirmée par un geste humain explicite (`ENF-IA-2`).

### 4.3 Validation de temps par lot (POST)

```http
POST /api/v1/timesheets/batch-validate HTTP/1.1
Authorization: Bearer <session-or-token>
Content-Type: application/json

{ "timesheetIds": ["TS-WK35-C001", "TS-WK35-C002", "TS-WK35-C003", "TS-WK35-C004", "TS-WK35-C005",
                    "TS-WK35-C006", "TS-WK35-C007", "TS-WK35-C008", "TS-WK35-C009", "TS-WK35-C010"] }
```

```json
HTTP/1.1 200 OK
{
  "validated": 10,
  "rejected": 0,
  "failed": 0,
  "results": [
    { "timesheetId": "TS-WK35-C001", "collaboratorId": "U-041", "status": "VALIDÉ" },
    { "timesheetId": "TS-WK35-C002", "collaboratorId": "U-042", "status": "VALIDÉ" }
  ],
  "processedAt": "2026-09-01T10:04:22Z"
}
```

### 4.4 Lecture de la vue d'atterrissage projet (GET)

```http
GET /api/v1/projects/PRJ-0042/landing-view HTTP/1.1
Authorization: Bearer <session-or-token>
```

```json
HTTP/1.1 200 OK
{
  "projectId": "PRJ-0042",
  "projectName": "Refonte SI",
  "currency": "EUR",
  "budget": { "days": 200, "amount": 56000.00 },
  "consumed": { "days": 110, "amount": 30800.00 },
  "estimateToComplete": { "days": 105, "amount": 29400.00 },
  "landing": { "days": 215, "amount": 60200.00 },
  "variance": { "days": 15, "amount": 4200.00, "percent": 7.5 },
  "alertLevel": "CP_THRESHOLD_REACHED",
  "lastValorizedAt": "2026-09-01T10:04:22Z",
  "byWorkPackage": [
    { "workPackageId": "WP-L1", "name": "Analyse", "variancePercent": 0, "alertLevel": "NONE" },
    { "workPackageId": "WP-L2", "name": "Développement", "variancePercent": 20, "alertLevel": "DIRECTION_THRESHOLD_REACHED" }
  ]
}
```

> `coutRevient`/coûts internes détaillés sont **absents** de ce DTO pour un rôle `ROLE_CHEF_PROJET` (`HAB-1`) : seule la marge agrégée est exposée. Le provider applique le voter avant la lecture, pas un filtrage de champ post-hoc.

---

## 5. Codes d'erreur normalisés

Format d'erreur générique (RFC 7807-like, jamais de stack trace — cf. `11-security.md` § Mishandling Exceptional Conditions) :

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Le budget est obligatoire (RG-PRJ-1)",
    "details": [
      { "field": "budget", "code": "REQUIRED", "message": "Le champ budget est obligatoire" }
    ],
    "requestId": "req_9f3c2a1b",
    "timestamp": "2026-09-01T10:04:22Z"
  }
}
```

| Code HTTP | Usage dans HotOnes |
|---|---|
| `400 Bad Request` | Payload malformé, type invalide, seuil hors bornes (US-018 CA-5) |
| `401 Unauthorized` | Session expirée, jeton absent/invalide |
| `403 Forbidden` | Habilitation refusée : projet non affecté (US-050 CA-4), rôle valideur manquant (US-055 CA-5), scope `team` refusé à un collaborateur (US-058 CA-5), accès à un `user_id` tiers (US-059 CA-4), réouverture par rôle non habilité (US-057 CA-5) |
| `404 Not Found` | Ressource inexistante ou hors périmètre tenant (jamais de distinction entre "inexistant" et "appartient à un autre tenant" — anti-énumération `ENF-SEC-4`) |
| `409 Conflict` | Chevauchement de périodes tarifaires (US-011 CA-5), doublon de compétence (US-013 CA-5), engagement sur projet clôturé (US-034 CA-5) |
| `422 Unprocessable Entity` | Violation de règle métier valide syntaxiquement : imputation sur période d'absence validée (US-054 CA-2, `RG-TMP-3`), transition de statut circulaire (US-017 CA-5) |
| `423 Locked` | Ressource verrouillée par clôture de période — modification impossible sans réouverture formelle (US-057 CA-4, US-060 CA-5) |
| `429 Too Many Requests` | Rate limit dépassé (cf. § 6) |
| `500 Internal Server Error` | Erreur non anticipée — message générique côté client, détail complet uniquement dans les logs serveur (jamais dans la réponse HTTP) |

**Règle non négociable :** aucune réponse d'erreur, quel que soit le code, ne contient de trace d'exécution, de nom de classe interne, de requête SQL ou de chemin de fichier serveur.

---

## 6. Rate limiting & pagination

### 6.1 Rate limiting

| Contexte | Limite indicative | Motif |
|---|---|---|
| `POST /api/v1/tenants` (inscription) | 5 req/heure/IP | Endpoint anonyme, cible privilégiée d'abus |
| Session UI (Turbo) | Limite large, alignée sur usage humain (protège des scripts, pas de l'usage normal) | `ENF-PERF-2` : ne doit jamais dégrader la saisie < 500 ms P95 |
| Jetons d'intégration (lot 3) | Quota par tenant/scope, configurable, avec palier de dégradation gracieuse avant blocage total | `ENF-SAAS-5` (supervision conso par tenant) |
| Endpoints IA (`/proposal`, futurs endpoints conversationnels) | Plafond par tenant en cohérence avec le budget d'inférence (`CTR-4`, `ENF-IA-5`) | Maîtrise du coût — dégradation gracieuse plutôt que blocage brutal (`ENF-DISPO-5`) |

Réponse `429` avec en-têtes `Retry-After` et `X-RateLimit-Remaining`.

### 6.2 Pagination

- **Cursor-based** par défaut sur toutes les listes volumineuses (`ENF-PERF-1/3`, tenant de référence : 150 collaborateurs, pointe ×5) : `GET /api/v1/audit-logs?limit=50&cursor=...`.
- Réponse enveloppée :

```json
{
  "data": [ /* ... */ ],
  "pagination": { "nextCursor": "eyJpZCI6MTIwfQ", "hasMore": true }
}
```

- Exception assumée : les grilles de saisie hebdomadaire (`/timesheets/weeks/{isoWeek}`) et la vue de complétude (`/completeness`) sont bornées par construction (une semaine, un périmètre managérial) — pas de pagination nécessaire, mais requêtes servies par vue matérialisée/cache si le volume l'exige (US-058, rafraîchi ≤ 15 min).
- Limite par défaut : 20 ; maximum : 100 (`ENF-PERF-6`, tenu sous charge ×5).

---

## 7. Séparation calcul/texte pour l'IA (`ARC-11`)

- Aucun endpoint IA (pré-remplissage US-053, futures fonctions conversationnelles du Pilotage) ne renvoie de **valeur chiffrée calculée par le modèle**. Les chiffres proviennent toujours du moteur de calcul unique testé (`ARC-6`) ; le modèle ne fait que **proposer une structure** (quels projets, quelles dates) reprise depuis des données déterministes (affectations planifiées).
- Le DTO de sortie sépare structurellement les deux natures de contenu : `origin.explanation` (texte, généré ou déterministe) ne contient jamais de montant, durée ou taux — ces champs sont portés séparément par des propriétés numériques calculées côté serveur, jamais interpolées dans une chaîne générée.
- Dégradation gracieuse : si le socle IA (`EPIC-010`) est indisponible, le pré-remplissage bascule sur une copie déterministe des affectations du planning (US-053, note) — le contrat DTO de sortie ne change pas, seul `origin.source` passe de `AI_SUGGESTION` à `DETERMINISTIC_RULE` (`ARC-80`, `ENF-IA-9`).
- Citation systématique des sources (`ARC-10`) : chaque proposition porte l'identifiant de l'enregistrement source (`assignmentId`), consultable par un rôle habilité.

---

## Résumé — nombre d'endpoints par module

| Module | US couvertes | Endpoints (routes distinctes) |
|---|---|---|
| Référentiels (`REF`) | US-010 à US-020 | 35 |
| Projets (`PRJ`) | US-030 à US-038 | 24 |
| Temps (`TMP`) | US-050 à US-060 | 24 |
| **Total lot 1** | 30 US | **83** |

Document à valider en revue technique avant `/project:decompose-tasks 001` — en particulier le nommage définitif des DTO et le détail RBAC (porté par US-003, non encore livré).
