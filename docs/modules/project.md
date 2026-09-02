# Module Projets & delivery (EPIC-002 — US-030/031/034/037/038)

Ouverture du module PRJ : l'agrégat `Project`, minimal jusqu'au Sprint 5, devient un **agrégat
métier** avec cycle de vie, structure (lots/jalons), affectations, engagements externes et clôture.
Le cycle de vie et l'affectation **conditionnent l'imputation** de temps ; la clôture réutilise le
pattern de clôture/réouverture d'US-057.

## Modèle

| Élément | Rôle |
|---------|------|
| `Domain\Project\Project` | Agrégat : client, responsable, budget, contractualisation, dates, `ProjectStatus`, clôture. |
| `ProjectStatus` / `ContractType` | Cycle de vie 7 statuts (transitions + politique imputation/facturation) / Forfait,Régie. |
| `ProjectLot` / `ProjectMilestone` | Lots (arbre 2 niveaux, budget charge j + montant €) / jalons (statut, déclencheur facturation). |
| `ProjectAssignment` / `ExceptionalImputationOpening` | Affectation (rôle, période, charge prév.) / ouverture d'imputation à la semaine. |
| `ExternalCommitment` | Engagement externe (type, montant € HT, fournisseur, statut, lot opt.). |
| `ProjectReopening` | Réouverture d'un projet clôturé (4-eyes, fenêtre). |
| `Application\Project\*` | `CreateProject`, `ChangeProjectStatus`, `ManageProjectLots`, `ManageMilestones`, `ManageAssignments`, `ManageExternalCommitments`, `ManageProjectClosure`. |

## Cycle de vie (US-030)

7 statuts : En préparation → En cours → (En attente client) → Livré – en attente de réception →
Réceptionné → Clôturé ; Annulé. Transitions par défaut (`ProjectStatus::canTransitionTo`, EF-PRJ-4).
L'imputation n'est ouverte qu'**En cours** (CA-2) ; la facturation dès l'exécution (CA-3, autorisation
seulement — émission EPIC-005). Création : client + responsable + budget obligatoires (RG-PRJ-1), code
`PRJ-XXXX`, statut initial « En préparation ». **Rétro-compatibilité** : le constructeur minimal reste
« En cours » (projet système « Absence » et jeux de test imputables).

## Chaîne de gardes d'imputation (`RecordTimeEntry`)

À chaque imputation, dans l'ordre : période comptable clôturée (US-057, 423) → absence validée
(US-054, 422) → projet actif → **statut « En cours »** (US-030) / **projet clôturé** → refus sauf
**réouverture active** (US-038, CA-7) → **affectation** : si le projet a des affectations, seul un
affecté ou une **ouverture exceptionnelle** de la semaine impute (US-037, CA-1). Un projet **sans
affectation** reste ouvert (rétro-compat ; le projet « Absence » inclus).

## Structure (US-031)

Lots hiérarchiques (2 niveaux), budget **bidimensionnel** (jours + €). La somme des **lots racines**
est comparée au budget projet : tout dépassement exige une **confirmation** (CA-6) ; l'écart est
signalé. Réallocation avec **motif** tracée (CA-3). Jalons : date dans la période projet (CA-7),
statut, déclencheur de facturation **idempotent** (une seule fois — CA-7).

## Affectation (US-037)

Affectation (rôle, période, charge prévisionnelle) ; la charge alimentera le **plan de charge**
(EPIC-004, dégradé). Restriction d'imputation aux affectés dès la première affectation (CA-1).
Ouverture exceptionnelle d'imputation bornée à une semaine, motif obligatoire, tracée (CA-2).

## Engagements externes (US-034)

CRUD d'engagements (montant + fournisseur obligatoires — CA-6), refus sur projet clôturé (CA-5,
RG-PRJ-5), agrégation des coûts externes. **Marge complète** (budget de vente, marge par lot) dégradée
tant qu'US-033 n'est pas livrée.

## Clôture (US-038)

Clôture bloquée s'il reste des **imputations non validées** (CA-6, RG-PRJ-5) ; jalons non atteints /
engagements non soldés = **avertissements** à confirmer (CA-4). Projet clôturé = lecture seule, ferme
les imputations. **Réouverture 4-eyes** : demande (chef de projet) puis approbation par un **ADMIN
distinct** (`MANAGE_ORGANIZATION`), fenêtre bornée `openUntil` — l'imputation redevient possible sur
la fenêtre (CA-3/CA-7).

## Habilitations & sécurité

`VIEW_PROJECT` (liste/détail), `CREATE_PROJECT` (création), `EDIT_PROJECT` (statut, structure,
affectation, engagements, clôture), `MANAGE_ORGANIZATION` (approbation de réouverture). Toutes les
tables du module portent la **RLS** (double barrière) avec test d'intrusion runtime. Erreurs métier →
**422** (`ProjectExceptionListener`). Écran `/projets` : PRG + CSRF, onglets **ARIA** (WAI-ARIA APG).

## Web

`/projets` (liste), `/projets/nouveau` (création), `/projets/{id}` (détail à onglets : Cycle de vie,
Structure, Équipe, Engagements, Clôture). Conception UX/UI préalable (ux-ergonome + ui-designer +
accessibility-expert). Statut = badge sobre (jamais la couleur seule).

## Tests

- Unit : `ProjectTest`, `ProjectLifecycleTest`, `ProjectStructureTest`, `ExternalCommitmentTest`,
  `ProjectClosureTest` ; gate d'imputation dans `RecordTimeEntryTest` (statut, affectation, clôture).
- Fonctionnel : `ProjectPageTest` (liste, création PRG, RG-PRJ-1, transitions).
- Intrusion RLS : `ProjectStructureRlsRuntimeTest`, `ProjectAssignmentRlsRuntimeTest`,
  `ExternalCommitmentRlsRuntimeTest`, `ProjectReopeningRlsRuntimeTest` (+ `project`/`time_entry` via
  `TimesheetRlsRuntimeTest`).

## Limites connues / suite

- **Client en texte libre** — référentiel clients US-014 non livré.
- **Facturation** (jalon → facture) : intention tracée, émission EPIC-005.
- **Budget de vente / marge par lot** : US-033 non livrée (marge partielle : coûts internes + externes).
- **Plan de charge** : charge d'affectation stockée, agrégation EPIC-004.
- **Transitions de statut configurables** (EF-PRJ-4) : défaut hardcodé, paramétrage ADMIN ultérieur.
- **Écran onglet mobile** : le détail projet est desktop-first ; adaptation mobile ultérieure.

## Revue (module EPIC-002) — findings traités

Revues `security-auditor` + `symfony-reviewer` — **GO** (module 85/100 ; RLS fail-closed sur les
5 tables, 4-eyes appliqué, ordre des gardes correct, habilitations serveur OK).

- **[Low — corrigé] Approbation de réouverture non liée au projet de l'URL** (`security-auditor`) :
  `approveReopening` vérifie désormais `reopening.projectId === {id}`.
- **[Low — corrigé] Borne basse de durée** (`security-auditor`) : `RecordTimeEntry` rejette
  `minutes <= 0` (anti-contournement du plafond journalier).
- **[Mineur — corrigé] `structureView` en O(n²)** (`symfony-reviewer`) : sous-lots pré-groupés par
  parent en une passe.
- **[Info — confirmé conforme] Branche de réouverture** (`security-auditor` I5) : `findActive` filtre
  sur `active=true` (pas sur le statut) et `close()` conserve `active=true` → le bypass de réouverture
  est bien atteignable (couvert par `RecordTimeEntryTest::testClosedProjectBlocksImputationUnlessReopened`).
- **[Mineurs — rejetés]** statut d'engagement non transitionnable (fixé à la création, aucun CA ne le
  requiert — YAGNI) ; isolation tenant de `find` (déjà par `tenantId` + RLS).
- **[Dette suivie — documentée]** M1 `userId` non ancré à l'acteur dans `RecordTimeEntry` (pré-existant
  US-050 ; tous les contrôleurs passent `user->id()` authentifié — pas de chemin exploitable ;
  refactor vers un objet `User` à planifier) ; M2 fenêtre de réouverture sans borne basse (ajout d'un
  `openFrom` = migration ; le verrou de période limite déjà l'abus) ; `ProjectPageController` à 10
  dépendances (contrôleur d'agrégation de lecture ; extraction d'un presenter en raffinement).

**Conclusion : GO** — corrections de durcissement appliquées (L3/L4 + O(n²)), dettes de defense-in-depth
documentées et non bloquantes.
