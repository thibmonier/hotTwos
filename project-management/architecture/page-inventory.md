# Référentiel des pages cibles — `page-inventory.md`

> **Livrable US-080** (Sprint 19 — EPIC-013). Source de vérité du périmètre écrans.
> **Source** : `php bin/console debug:router` sur `main` (post-US-086) + scan `templates/`.
> **Statut** : 🟡 Draft — en attente de **validation PO**.
> **Mis à jour** : 2026-09-10.

## Personas (rappel)
P1 Camille (collaboratrice/dev) · P2 Marc (chef de projet) · P3 Sophie (resource manager / dir. production) · P4 Yann (commercial) · P5 Nadia (RH) · P6 Élodie (dirigeante).

## Résumé par module

| Module | Pages existantes | Pages à créer |
|--------|------------------|---------------|
| Auth / Common | 6 | 3 (erreurs 403/404/500) |
| Temps | 4 | – |
| Complétude | 1 | – |
| Valorisation | 1 | – |
| Finance | 6 | – |
| Projets | 4 | – |
| Clients | 1 | 1 (fiche client) |
| Staffing / Planification | 2 | – |
| Absences | 1 | 1 (validation absences) |
| Relances | 1 | – |
| Organisation | 1 | – |
| Profils (pricing) | 1 | – |
| Référentiels (paramétrage) | 6 | – |
| Administration | 1 | – |
| CRM (EPIC-006) | 0 | 4 |
| RH / Recrutement (EPIC-008) | 0 | 4 |
| **Total** | **36** | **~17** |

> Statut légende : **Existant** (route implémentée) · **À créer** (page cible non implémentée). Layout : `admin` (sidebar + header) ou `auth` (centré).

---

## Auth / Common

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-AUTH-01 | Connexion | `/login` (`login`) | Existant · auth | Tous | S'authentifier | Formulaire email/mdp, lien mdp oublié, bascule thème | Se connecter | Security (User) |
| PG-AUTH-02 | Mot de passe oublié | `/mot-de-passe-oublie` (`forgot_password_request`) | Existant · auth | Tous | Demander une réinitialisation | Formulaire email | Envoyer le lien | reset-password-bundle |
| PG-AUTH-03 | Vérifiez vos emails | `/mot-de-passe-oublie/verification` (`check_email`) | Existant · auth | Tous | Confirmer l'envoi du mail | Message de confirmation | – | reset-password-bundle |
| PG-AUTH-04 | Réinitialisation mot de passe | `/reinitialiser/{token}` (`reset_password`) | Existant · auth | Tous | Définir un nouveau mdp | Formulaire nouveau mdp | Réinitialiser | reset-password-bundle |
| PG-CMN-01 | Tableau de bord d'accueil | `/` (`home`) | Existant · admin | P1 (tous) | Point d'entrée après connexion | Synthèse personnelle (saisie du jour, raccourcis) | Naviguer | Imputations, absences |
| PG-CMN-02 | Mon compte | `/mon-compte` (`account`) | Existant · admin | Tous | Gérer son profil / mot de passe | Infos utilisateur, formulaire profil, formulaire mdp | Modifier profil (`account_profile`), changer mdp (`account_password`) | User |
| PG-CMN-03 | Styleguide | `/styleguide` (`styleguide`) | Existant · admin | Interne (dev/design) | Vitrine des composants du socle | Composants tailsfadmin de démo | – | – (statique) |
| PG-CMN-04 | Erreur 403 | – | **À créer** · admin | Tous | Accès refusé lisible | Message générique + retour | Retour accueil | – |
| PG-CMN-05 | Erreur 404 | – | **À créer** · admin | Tous | Page introuvable lisible | Message générique + retour | Retour accueil | – |
| PG-CMN-06 | Erreur 500 | – | **À créer** · admin | Tous | Erreur serveur sans stack (prod) | Message générique | Retour accueil | – (le socle fournit un gabarit 500) |

## Temps

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-TMP-01 | Saisie hebdomadaire | `/saisie` (`timesheet_week`) | Existant · admin | P1 Camille | Saisir son temps de la semaine (< 2 min) | Grille jours × projets/lots, totaux | Saisir/éditer imputations, soumettre | Imputation, Project/Lot |
| PG-TMP-02 | Saisie du jour | `/saisie/jour/{date}` (`timesheet_day`) | Existant · admin | P1 Camille | Saisir/éditer une journée | Imputations du jour | Ajouter/modifier imputation | Imputation |
| PG-TMP-03 | Saisie — aujourd'hui | `/saisie/jour` (`timesheet_day_today`) | Existant · admin | P1 Camille | Raccourci saisie du jour courant | idem PG-TMP-02 | idem | Imputation |
| PG-TMP-04 | Validation des temps | `/validation` (`timesheet_validation`) | Existant · admin | P2 Marc (`validate:time`) | Valider/refuser les temps de l'équipe | File de validation par collaborateur/période | Valider, refuser | Imputation, circuit validation |

## Complétude

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-CPL-01 | Complétude | `/completude` (`completeness_page`) | Existant · admin | P2 / P3 | Suivre la complétude de saisie de l'équipe | Grille collaborateurs × jours ouvrés, taux | Filtrer période, relancer | CompletenessGrid, absences, jours ouvrés |

## Valorisation

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-VAL-01 | Valorisation du temps validé | `/valorisation` (`valuation_dashboard`) | Existant · admin | P3 / P6 (`view:project_financials`) | Voir le CA reconnu, ventilation, occupation | KPI CA reconnu, avancement (ProgressBar), ventilation projet, occupation/collaborateur | Filtrer période | ProjectMargin, imputations valorisées |

## Finance

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-FIN-01 | Tableau de bord financier | `/finance` (`finance_dashboard`) | Existant · admin | P6 / P3 (`view:project_financials`) | Piloter marge/rentabilité | KPI marge, revenu retenu, dérive | Filtrer période, exporter | ProjectMargin, budgets |
| PG-FIN-02 | Config seuil de dérive marge | `/finance/config-derive` (`margin_drift_config`) | Existant · admin | P6 (direction) | Régler le seuil d'alerte marge tenant | Formulaire seuil | Enregistrer | MarginDriftThreshold |
| PG-FIN-03 | Config seuil de dérive charge | `/finance/config-derive-charge` (`charge_drift_config`) | Existant · admin | P6 / P3 | Régler le seuil de dérive de charge | Formulaire seuil par type | Enregistrer | ChargeDriftThreshold |
| PG-FIN-04 | Config devises | `/finance/config-devises` (`currency_config`) | Existant · admin | P6 | Définir devise de référence + taux | Devise référence, table de taux | Définir référence, ajouter taux | Currency |
| PG-FIN-05 | Config FEC | `/finance/config-fec` (`fec_config`) | Existant · admin | P6 (compta) | Paramétrer le mapping de comptes FEC | Config FEC (comptes) | Enregistrer | FecConfiguration |
| PG-FIN-06 | Export FEC | `/finance/export/fec` (`finance_export_fec`) | Existant · admin | P6 (compta, `ExportFec`) | Exporter le FEC légal | Période, écritures équilibrées | Télécharger | FecGenerator (facturé réel) |

## Projets

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-PRJ-01 | Liste des projets | `/projets` (`project_index`) | Existant · admin | P2 Marc | Retrouver/filtrer les projets | Table (code, nom, client, statut, budget) | Ouvrir, créer | Project |
| PG-PRJ-02 | Nouveau projet | `/projets/nouveau` (`project_new`) | Existant · admin | P2 Marc | Créer un projet | Formulaire création | Créer | Project, Client |
| PG-PRJ-03 | Fiche projet | `/projets/{id}` (`project_show`) | Existant · admin | P2 Marc | Piloter un projet (onglets) | Onglets Cycle de vie / Structure / Équipe / Engagements / Clôture / Suivi budgétaire (`tsf:Ui:Tabs`) | Changer statut, lots, affectations, avenants, facturer, copier le code (`tsf:clipboard`) | Project, Lot, ProjectMargin, budgets, factures |
| PG-PRJ-04 | Export pilotage | `/projets/{id}/pilotage/export` (`project_pilotage_export`) | Existant · admin | P2 / P6 (`view:project_financials`) | Exporter le pilotage (CSV) | – (téléchargement) | Télécharger | Pilotage projet |

## Clients

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-CLI-01 | Liste des clients | `/clients` (`client_index`) | Existant · admin | P4 Yann (`manage:organization`) | Gérer les clients | Table clients | Créer (`client_create`) | Client |
| PG-CLI-02 | Fiche client | – | **À créer** · admin | P4 Yann | Vue 360 client (projets, CA, contacts) | Détail client, projets liés, CA | Éditer | Client, Project (EPIC-006 amont) |

## Staffing / Planification

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-STF-01 | Recherche de staffing | `/planification/recherche` (`staffing_search`) | Existant · admin | P3 Sophie | Trouver des collaborateurs par compétence + dispo | Résultats filtrés (compétence, niveau, dispo) | Rechercher | Skill, SkillAssignment, dispo |
| PG-STF-02 | Plan de charge | `/planification/charge` (`workload_plan`) | Existant · admin | P3 Sophie | Visualiser capacité vs charge ferme | Grille collaborateur × semaines, capacité/charge | Filtrer horizon | Affectations, capacité |

## Absences

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-ABS-01 | Mes absences | `/absences` (`absence_page`) | Existant · admin | P1 Camille | Poser/suivre ses absences | Solde, historique, calendrier | Demander une absence | AbsenceRequest |
| PG-ABS-02 | Validation des absences | – | **À créer** · admin | P2 / P5 | Circuit de validation des absences (UI dédiée) | File des demandes à décider | Approuver/refuser | AbsenceRequest, circuit |

## Relances

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-REL-01 | Relances | `/relances` (`reminders_page`) | Existant · admin | P2 / P3 (`manage:reminders`) | Relancer les saisies manquantes | Liste des relances/destinataires | Déclencher relance | Complétude, notifications |

## Organisation

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-ORG-01 | Organisation | `/organisation` (`organization_admin`) | Existant · admin | P6 / admin (`manage:organization`) | Gérer unités et rattachements | Arbre org-units, memberships | Éditer | OrgUnit, OrgMembership |

## Profils (pricing)

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-PRC-01 | Profils & taux | `/profils` (`pricing_admin`) | Existant · admin | P6 (`manage:pricing`) | Gérer profils, taux (coût/vente) | Table profils, taux, affectations | Affecter (`pricing_assign`), définir taux vente (`pricing_selling_rate_define`) | Profile, ProfileRate, SellingRate |

## Référentiels (paramétrage)

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-REF-01 | Jours fériés | `/parametrage/jours-feries` (`holiday_index`) | Existant · admin | P6 / admin | Gérer les jours fériés | Liste fériés | Ajouter (`holiday_add`), supprimer (`holiday_delete`) | Holiday |
| PG-REF-02 | Fermetures | `/parametrage/fermetures` (`closure_index`) | Existant · admin | P6 / admin | Gérer les fermetures d'entreprise | Liste fermetures | Ajouter (`closure_add`), supprimer (`closure_delete`) | Closure |
| PG-REF-03 | Compétences | `/parametrage/competences` (`skill_index`) | Existant · admin | P3 / RH | Gérer le référentiel de compétences | Liste compétences/niveaux | Éditer | Skill |
| PG-REF-04 | Régimes de travail | `/parametrage/regimes-travail` (`work_schedule_index`) | Existant · admin | P5 / admin | Gérer temps partiel / régimes | Liste régimes | Éditer | WorkSchedule |
| PG-REF-05 | Circuits de validation | `/parametrage/circuits-validation` (`validation_circuit_index`) | Existant · admin | P6 / admin | Configurer les circuits de validation | Liste circuits/étapes | Éditer | ValidationCircuit |
| PG-REF-06 | Journal d'audit | `/parametrage/audit` (`audit_log_index`) | Existant · admin | P6 / admin (`VIEW_AUDIT_LOG`) | Consulter l'audit append-only | Journal des événements config | Filtrer | AuditLog |

## Administration

| ID | Nom | Route | Statut | Persona | Objectif | Infos affichées | Actions | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|---------|-----------------|
| PG-ADM-01 | Périodes | `/administration/periodes` (`period_admin`) | Existant · admin | P6 (`manage:periods`) | Clôturer/gérer les périodes | Liste périodes, statuts | Clôturer (`period_close`) | Period |

## CRM (EPIC-006 — à construire)

| ID | Nom | Route | Statut | Persona | Objectif | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|
| PG-CRM-01 | Pipeline commercial | – | **À créer** · admin | P4 Yann | Suivre les opportunités par étape | Opportunité |
| PG-CRM-02 | Fiche opportunité | – | **À créer** · admin | P4 Yann | Détailler/qualifier une affaire | Opportunité, Client |
| PG-CRM-03 | Prévision (charge probable) | – | **À créer** · admin | P3 / P4 | Charge probable pondérée (INV-5) | Opportunité, staffing |
| PG-CRM-04 | Contacts client | – | **À créer** · admin | P4 Yann | Gérer les contacts | Contact, Client |

## RH / Recrutement (EPIC-008 — à construire)

| ID | Nom | Route | Statut | Persona | Objectif | Données sources |
|----|-----|-------|--------|---------|----------|-----------------|
| PG-RH-01 | Annuaire collaborateurs | – | **À créer** · admin | P5 Nadia | Gérer les fiches collaborateurs | User, contrat |
| PG-RH-02 | Fiche collaborateur | – | **À créer** · admin | P5 Nadia | Détail RH (contrat, compétences, congés) | User, Skill, AbsenceRequest |
| PG-RH-03 | Entretiens / évaluations | – | **À créer** · admin | P5 Nadia | Suivre les entretiens | Entretien |
| PG-RH-04 | Recrutement (candidats/postes) | – | **À créer** · admin | P5 Nadia | Suivre candidats et postes ouverts | Candidat, Poste |

---

## Notes d'accessibilité (WCAG 2.2 AA — écrans à fort enjeu)
- **PG-TMP-01/02/03 (saisie P1)** : cibles ≥ 44 px, focus visible, labels explicites, **aucune action critique au survol seul**, pas de captcha temporel. Critère de rejet P1 : saisie > 2 min.
- **Pages auth** : contraste AA, navigation clavier complète, messages d'erreur explicites.
- **Tableaux (projets, finance, staffing)** : en-têtes de colonnes associés, tri accessible, pagination clavier.

## Hors périmètre
- Routes internes Symfony (`_profiler`, `_wdt`, `_error`) et techniques (`health`, `metrics`, `_probe`).
- API Platform (`/api/*`) — documentée séparément via OpenAPI (non des « pages »).

## Validation PO
- [ ] Couverture 100 % des pages existantes confirmée (36 pages · 37 routes GET, hors technique).
- [ ] Pages « À créer » et personas primaires validés.
- [ ] Feu vert pour démarrer US-081 (parcours par persona).
