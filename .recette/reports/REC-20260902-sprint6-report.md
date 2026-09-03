# Rapport de recette — Sprint 6 (EPIC-002 : Projets & delivery)

| Attribut | Valeur |
|----------|--------|
| Session | REC-20260902-sprint6 |
| Date | 2026-09-02 |
| Périmètre | Sprint 6 complet — US-030/031/034/037/038 |
| Base | http://localhost:8080 (FrankenPHP dev), tenant démo `app:demo:seed` |
| Comptes | marc@demo.test (chef de projet), admin@demo.test (admin, 4-eyes) — mdp `demo-1234-solide` |
| Auth | `POST /api/login` (json_login — pas de formulaire HTML) |
| Projet de recette | PRJ-0004 « Portail Client Acme » (budget 120 000 €, 2026-09-01 → 2027-03-31) |

## Amorçage

- `make up` échouait : l'entrypoint (`docker/start.sh`) exige `APP_SECRET` en variable d'environnement
  (il tourne avant le boot Symfony, ne lit pas `.env.dev`). Ajout de `APP_SECRET` (valeur dev) au
  service `app` dans `compose.override.yaml` (local, non versionné) → app up sur :8080.
- `app:demo:seed` : tenant + 3 comptes + projets ALPHA/BETA. Login CLI et navigateur validés (200).

## Synthèse

| Verdict | Nb | Détail |
|---------|----|--------|
| ✅ PASS (navigateur) | 12 | création + validations RG-PRJ-1, transitions par statut, lots/arbre/écart, refus dépassement, jalon hors période, jalon sans déclencheur (idempotent), affectation, ouverture exceptionnelle, engagement + agrégation coûts externes, refus engagement incomplet, prérequis de clôture, clôture, réouverture 4-eyes |
| 🟡 Partiel / non exposé UI | 6 | gate imputation par statut, réallocation (pas d'UI), non-affecté en /saisie, affectation hors période / inactif, filtres engagements + lien lot (pas d'UI), clôture bloquée par imputations non validées |
| ⚪ Hors-navigateur | 3 | facturation (EPIC-005), plan de charge (EPIC-004), marge de vente complète (US-033) — non livrés |
| ❌ Anomalie | 3 | voir findings F1/F2/F3 |

## Détail des cas ✅

- **US-030** — CA-1 création (code auto **PRJ-0004**, statut **En préparation**, apparaît en liste) ; CA-4 refus sans client (« Le client est obligatoire (RG-PRJ-1) », HTML5 **et** serveur) ; CA-5 refus sans budget ; CA-4(cycle) transitions **conditionnées par le statut** (En préparation → En cours/Annulé ; En cours → En attente client/Livré/Annulé).
- **US-031** — CA-1 arbre 2 niveaux (Développement › Module Auth/Reporting), **Total lots 92 000 € vs budget 120 000 €, écart −28 000 €** ; CA-6 refus dépassement sans confirmation (« Confirmation requise … dépasse le budget projet (EF-PRJ-2) », non persisté) ; CA-4 dépassement sauvegardé avec confirmation (écart **+12 000 €**) ; CA-5 jalon sans déclencheur → **Atteint** (date d'atteinte, **aucune facture**, bouton retiré = idempotent) ; CA-7 jalon hors période refusé (message période).
- **US-037** — CA-4 affectation (rôle, 60 j, 01/10/2026 → 31/01/2027) visible dans l'onglet Équipe ; CA-2 ouverture exceptionnelle tracée (semaine normalisée au lundi 12/10/2026, motif + auteur).
- **US-034** — CA-2 engagement créé + **coûts externes : 6 000 €** agrégés (l'UI signale la dépendance US-033 pour la marge complète) ; CA-6 refus sans montant / sans fournisseur (non persisté).
- **US-038** — CA-4 prérequis de clôture (« 1 engagement(s) non soldé(s) », confirmation requise) ; CA-1 clôture confirmée (statut **Clôturé**, badge) ; CA-3 **réouverture 4-eyes** : marc demande, **admin distinct** approuve avec fenêtre (« ouverte jusqu'au 31/05/2027 ») — bouton d'approbation visible pour le seul ADMIN.

## Anomalies (Règle d'Or → registre `.recette/regression/`)

### F3 — MAJEUR — projet clôturé non en lecture seule (US-038/CA-2, RG-PRJ-5)
Après clôture, `POST /lots`, `/jalons`, `/affectations`, `/ouvertures` **créent toujours** les entités
(HTTP 200, persistées). Seuls l'imputation (garde `RecordTimeEntry`) et les engagements (formulaire
retiré + garde domaine) sont bloqués. Cause : `ManageProjectLots::addLot`, `ManageMilestones::addMilestone`,
`ManageAssignments::assign`/`grantOpening` n'appellent pas `Project::isClosed()`.
→ REG-S6-001 (`ClosedProjectReadOnlyRegressionTest`).

### F2 — MOYEN — affectation au-delà de la fin du projet acceptée (US-037/CA-6, EF-PRJ-20)
Affectation `endDate=2027-06-30` créée sur un projet finissant le 2027-03-31, sans refus ni avertissement.
Cause : `ManageAssignments::assign` ne compare pas `endDate` à la date de fin du projet. (Le scénario
« collaborateur inactif » de CA-6 n'est par ailleurs pas modélisé : `app_user` n'a pas de statut actif/inactif.)
→ REG-S6-002 (`AssignmentWithinProjectPeriodRegressionTest`).

### F1 — FAIBLE (UX) — identifiants utilisateur bruts, échec silencieux
Le champ « Responsable » attend un **UUID** ; y saisir un nom fait échouer la création **sans message
explicite** (marqueur rouge discret). Les collaborateurs affectés sont affichés par préfixe d'UUID.
Lié à l'absence de référentiel utilisateur/clients (US-014 non livrée). → REG-S6-003 (différé, avec US-014).

## Observations d'outillage (non-imputables à l'app)

- **CSRF par formulaire** : chaque formulaire porte un `_token` propre à son intention. Réutiliser le
  token d'un autre formulaire → rejet « Jeton de sécurité invalide » (constaté pendant la mise au point).
- **Clic CDP vs Turbo** : `computer.left_click` ne déclenchait pas la soumission des formulaires Turbo ;
  `.click()`/`requestSubmit()` DOM et les POST directs fonctionnent (les boutons de l'app sont OK).
- **Checkbox** décochée = clé absente (envoyer `champ=''` est interprété « coché »).

## Suite

- **`/qa:fix`** (ou `/qa:tdd`) : porter REG-S6-001/002 dans `tests/Unit/Application/Project/`, ajouter
  la garde `isClosed()` aux use cases lots/jalons/affectations/ouvertures (F3) et la garde de période
  d'affectation (F2), boucle TDD rouge→vert, puis `make ci`.
- Nettoyage : le projet PRJ-0004 est pollué de données post-clôture (créées pour prouver F3).
