# Référentiel des pages cibles — `page-inventory.md`

> **Livrable US-080** (Sprint 19 — EPIC-013). Source de vérité du périmètre écrans.
> **Sources** : `php bin/console debug:router` sur `main` (pages existantes) + direction PO 2026-09-10 (dashboards, cycles de vie, conventions).
> **Statut** : 🟡 Draft **v2** (intègre le retour PO) — en attente de **validation PO**.
> **Mis à jour** : 2026-09-10.

## Personas
P1 Camille (collaboratrice/dev) · P2 Marc (chef de projet) · P3 Sophie (resource manager / dir. production) · P4 Yann (commercial) · P5 Nadia (RH) · P6 Élodie (dirigeante).

---

## 1. Conventions transverses (toutes pages)

- **Point d'entrée** : `/` **redirige vers le dashboard du profil** (P1→collaborateur, P2→projets, P3→staffing, P4→commerce, P5→RH, P6→agence). **Repli** : une **home générale** légère pour les profils particuliers (freelance en simple saisie, administrateur, rôle non mappé).
- **Patron de navigation par domaine** : `Dashboard` (mon activité, ou globale si manager/dirigeant) → **liste allégée** des éléments d'intérêt sur le dashboard → **liste complète** (filtres + recherche, infos rapides) → **détail** d'un élément **ou création**.
- **Menu regroupé par cycle de vie** (cf. §2).
- **Breadcrumb** affiché sur **chaque** page (position dans l'arborescence).
- **En-tête de page contextuel** : widgets d'infos utiles à voir vite (évolution du CA, avancement projet, finance projet, KPI du domaine…).
- **Pages de détail complexes** → **navigation horizontale par onglets** (`tsf:Ui:Tabs`) par thème (modèle de la fiche projet).
- **Accessibilité WCAG 2.2 AA** : cibles ≥ 44 px, focus visible, navigation clavier, pas d'action critique au survol seul.

## 2. Structure de menu (par cycle de vie)

```
Dashboard (selon profil)
─── Cycle commercial & projet ───────────────
  Clients   →  Devis   →  Projets   →  Factures
─── Cycle RH ────────────────────────────────
  Offres d'emploi → Recrutement (kanban) → Embauche & Contrats → Suivi de carrière
─── Mon activité ────────────────────────────
  Saisie · Absences · Complétude
─── Pilotage & finance ──────────────────────
  Valorisation · Finance · Facturation
─── Planification ───────────────────────────
  Recherche staffing · Plan de charge
─── Validation ──────────────────────────────
  Temps · Absences
─── Paramétrage & administration ────────────
  Référentiels · Organisation · Profils & taux · Périodes · Audit · Relances
```

> L'ordre des domaines du cycle commercial/projet suit `Client → Devis → Projet → Facture` ; le bloc RH suit `Offre → Recrutement → Embauche → Carrière`.

## 3. Résumé

| Bloc | Pages existantes | Pages à créer |
|------|------------------|---------------|
| Dashboards (par profil) | 0 | 8 |
| Home / Auth / Common | 6 | 3 |
| Cycle commercial & projet (Clients/Devis/Projets/Factures) | 6 | 8 |
| Cycle RH (Offres/Recrutement/Contrats/Carrières) | 0 | 10 |
| Mon activité (Saisie/Absences/Complétude) | 5 | – |
| Pilotage & finance | 6 | – |
| Planification (staffing) | 2 | – |
| Validation | 1 | 1 |
| Paramétrage & administration | 8 | – |
| **Total** | **34** | **~30** |

> Statut : **Existant** (route implémentée) · **À créer**. Type : Dashboard · Liste · Détail · Création · Config · Action · Auth · Système. Layout : `admin` sauf mention `auth`.

---

## 4. Dashboards (par profil — à créer)

| ID | Type | Nom | Route (cible) | Statut | Persona | Objectif / contenu clé |
|----|------|-----|---------------|--------|---------|------------------------|
| DSH-COLLAB | Dashboard | Mon activité (collaborateur) | `/` → `/tableau-de-bord` | À créer | P1 | Saisie du jour, complétude perso, solde d'absences, raccourcis. Liste allégée : mes imputations récentes. |
| DSH-COM | Dashboard | Commerce | `/commerce` | À créer | P4 | Pipeline, devis en cours, CA signé/prévu. Liste allégée : mes devis/opportunités chauds. |
| DSH-PRJ | Dashboard | Projets | `/projets/tableau-de-bord` | À créer | P2 | Santé de mes projets (avancement, dérive, RAF). Liste allégée : mes projets actifs. |
| DSH-STF | Dashboard | Staffing | `/planification` | À créer | P3 | Capacité vs charge, sous/sur-staffing, demandes. Liste allégée : collaborateurs à arbitrer. |
| DSH-REC | Dashboard | Recrutement | `/recrutement` | À créer | P5 | Offres ouvertes, pipeline candidats, entretiens à venir. Liste allégée : candidats à traiter. |
| DSH-RH | Dashboard | Effectifs & carrières | `/rh` | À créer | P5 | Effectif, contrats à échéance, entretiens de carrière. Liste allégée : contrats/carrières à suivre. |
| DSH-FAC | Dashboard | Facturation | `/facturation/tableau-de-bord` | À créer | P6 (compta) | Factures à émettre/en retard, encours, CA facturé. Liste allégée : factures à émettre. |
| DSH-AGENCE | Dashboard | Agence / Direction | `/direction` | À créer | P6 | Vue globale : CA, marge, occupation, effectif — consolidé agence. Liste allégée : alertes/dérives. |

---

## 5. Home / Auth / Common

| ID | Type | Nom | Route | Statut | Persona | Objectif / contenu | Données |
|----|------|-----|-------|--------|---------|--------------------|---------|
| PG-CMN-01 | Système | Redirection d'accueil par profil | `/` (`home`) | Existant (à faire évoluer) | Tous | Router `/` vers le dashboard du profil ; repli home générale | Rôles/habilitations |
| PG-CMN-02 | Dashboard | Home générale (repli) | `/accueil` | À créer | Particuliers (freelance, admin) | Landing légère : raccourcis (saisie, absences), notifications | Imputations, absences |
| PG-CMN-03 | Détail | Mon compte | `/mon-compte` (`account`) | Existant | Tous | Gérer profil / mot de passe | User |
| PG-CMN-04 | Système | Styleguide (interne) | `/styleguide` (`styleguide`) | Existant | Interne (dev/design) | Vitrine des composants du socle | – |
| PG-AUTH-01 | Auth | Connexion | `/login` (`login`) | Existant · auth | Tous | S'authentifier | Security (User) |
| PG-AUTH-02 | Auth | Mot de passe oublié | `/mot-de-passe-oublie` (`forgot_password_request`) | Existant · auth | Tous | Demander une réinitialisation | reset-password-bundle |
| PG-AUTH-03 | Auth | Vérifiez vos emails | `/mot-de-passe-oublie/verification` (`check_email`) | Existant · auth | Tous | Confirmer l'envoi | reset-password-bundle |
| PG-AUTH-04 | Auth | Réinitialisation mot de passe | `/reinitialiser/{token}` (`reset_password`) | Existant · auth | Tous | Définir un nouveau mdp | reset-password-bundle |
| PG-ERR-403 | Système | Erreur 403 | – | À créer | Tous | Accès refusé lisible | – |
| PG-ERR-404 | Système | Erreur 404 | – | À créer | Tous | Page introuvable lisible | – |
| PG-ERR-500 | Système | Erreur 500 | – | À créer | Tous | Erreur serveur sans stack (prod) — gabarit socle | – |

---

## 6. Cycle commercial & projet — `Client → Devis → Projet → Facture`

### 6.1 Clients (P4 Yann · `manage:organization`)
| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-CLI-01 | Liste | Liste des clients | `/clients` (`client_index`) | Existant | Table clients + filtres/recherche | Client |
| PG-CLI-02 | Détail | Fiche client (onglets) | `/clients/{id}` | À créer | Vue 360 : coordonnées, devis, projets, CA (en-tête : évolution CA client) | Client, Devis, Project |
| PG-CLI-03 | Création | Nouveau client | `/clients/nouveau` (+ `client_create` POST) | Existant (POST) / formulaire à créer | Créer un client | Client |

### 6.2 Devis (P4 Yann) — **nouveau domaine**
| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-DEV-01 | Liste | Liste des devis | `/devis` | À créer | Devis par statut (brouillon/envoyé/accepté), filtres | Devis |
| PG-DEV-02 | Détail | Fiche devis (onglets) | `/devis/{id}` | À créer | Lignes, montants, statut, conversion en projet (en-tête : montant, probabilité) | Devis, Client |
| PG-DEV-03 | Création | Nouveau devis | `/devis/nouveau` | À créer | Créer/éditer un devis, le lier à un client | Devis, Client |

### 6.3 Projets (P2 Marc)
| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-PRJ-01 | Liste | Liste des projets | `/projets` (`project_index`) | Existant | Table (code, nom, client, statut, budget) + filtres/recherche | Project |
| PG-PRJ-02 | Détail | Fiche projet (onglets) | `/projets/{id}` (`project_show`) | Existant | Onglets Cycle de vie / Structure / Équipe / Engagements / Clôture / Suivi budgétaire (`tsf:Ui:Tabs`) ; en-tête : avancement + finance projet | Project, Lot, ProjectMargin, budgets, factures |
| PG-PRJ-03 | Création | Nouveau projet | `/projets/nouveau` (`project_new`) | Existant | Créer un projet (option : depuis un devis accepté) | Project, Client, Devis |
| PG-PRJ-04 | Action | Export pilotage (CSV) | `/projets/{id}/pilotage/export` (`project_pilotage_export`) | Existant | Export pilotage | Pilotage projet |

### 6.4 Factures (P6 compta) — **externaliser de la fiche projet**
| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-FAC-01 | Liste | Liste des factures | `/factures` | À créer | Factures par statut (à émettre/émise/encaissée/retard), filtres | Facture |
| PG-FAC-02 | Détail | Fiche facture | `/factures/{id}` | À créer | Détail facture, lien projet/client, échéance | Facture, Project |
| PG-FAC-03 | Création | Émettre une facture | `/factures/nouvelle` | À créer (aujourd'hui dans la fiche projet) | Émettre une facture (par projet/période) | Facture, Project |

---

## 7. Cycle RH — `Offre → Recrutement → Embauche → Carrière` (P5 Nadia — EPIC-008, à créer)

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-OFF-01 | Liste | Offres d'emploi | `/recrutement/offres` | À créer | Offres ouvertes/fermées, filtres | Offre |
| PG-OFF-02 | Détail | Fiche offre | `/recrutement/offres/{id}` | À créer | Détail, candidats liés | Offre, Candidat |
| PG-OFF-03 | Création | Nouvelle offre | `/recrutement/offres/nouvelle` | À créer | Publier une offre | Offre |
| PG-REC-01 | Détail | Pipeline recrutement (**kanban**) | `/recrutement/pipeline` | À créer | Candidats par étape en **kanban** (`tailsfadmin--kanban`, drag & drop) | Candidat, Offre |
| PG-REC-02 | Détail | Fiche candidat | `/recrutement/candidats/{id}` | À créer | CV, entretiens, décision | Candidat |
| PG-EMB-01 | Liste | Contrats & embauches | `/rh/contrats` | À créer | Contrats (type, échéance), filtres | Contrat, User |
| PG-EMB-02 | Détail | Fiche contrat | `/rh/contrats/{id}` | À créer | Détail contrat, avenants | Contrat, User |
| PG-CAR-01 | Liste | Suivi de carrière | `/rh/carrieres` | À créer | Collaborateurs, entretiens, évolutions | User, Entretien |
| PG-CAR-02 | Détail | Fiche collaborateur (RH) | `/rh/collaborateurs/{id}` | À créer | Onglets : contrat, compétences, absences, entretiens (en-tête : ancienneté, poste) | User, Skill, AbsenceRequest, Contrat |
| PG-CAR-03 | Création | Entretien / évaluation | `/rh/entretiens/nouveau` | À créer | Planifier/saisir un entretien | Entretien |

---

## 8. Mon activité (P1 Camille)

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-TMP-01 | Détail | Saisie hebdomadaire | `/saisie` (`timesheet_week`) | Existant | Grille jours × projets/lots (< 2 min) | Imputation, Project/Lot |
| PG-TMP-02 | Détail | Saisie du jour | `/saisie/jour/{date}` (`timesheet_day`) | Existant | Éditer une journée | Imputation |
| PG-TMP-03 | Détail | Saisie — aujourd'hui | `/saisie/jour` (`timesheet_day_today`) | Existant | Raccourci jour courant | Imputation |
| PG-ABS-01 | Détail | Mes absences | `/absences` (`absence_page`) | Existant | Solde, historique, demande d'absence | AbsenceRequest |
| PG-CPL-01 | Dashboard/Liste | Complétude | `/completude` (`completeness_page`) | Existant | Grille collaborateurs × jours ouvrés (managers) | CompletenessGrid, absences |

---

## 9. Pilotage & finance (P3 / P6 · `view:project_financials`)

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-VAL-01 | Dashboard | Valorisation du temps validé | `/valorisation` (`valuation_dashboard`) | Existant | CA reconnu, avancement (ProgressBar), occupation | ProjectMargin, imputations |
| PG-FIN-01 | Dashboard | Tableau de bord financier | `/finance` (`finance_dashboard`) | Existant | Marge, revenu retenu, dérive | ProjectMargin, budgets |
| PG-FIN-02 | Config | Seuil de dérive marge | `/finance/config-derive` (`margin_drift_config`) | Existant | Régler seuil marge | MarginDriftThreshold |
| PG-FIN-03 | Config | Seuil de dérive charge | `/finance/config-derive-charge` (`charge_drift_config`) | Existant | Régler seuil charge | ChargeDriftThreshold |
| PG-FIN-04 | Config | Devises | `/finance/config-devises` (`currency_config`) | Existant | Devise référence + taux | Currency |
| PG-FIN-05 | Config | Config FEC | `/finance/config-fec` (`fec_config`) | Existant | Mapping comptes FEC | FecConfiguration |
| PG-FIN-06 | Action | Export FEC | `/finance/export/fec` (`finance_export_fec`) | Existant | Export FEC légal | FecGenerator |

## 10. Planification (P3 Sophie)

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-STF-01 | Liste | Recherche de staffing | `/planification/recherche` (`staffing_search`) | Existant | Filtrer par compétence + dispo | Skill, SkillAssignment |
| PG-STF-02 | Détail | Plan de charge | `/planification/charge` (`workload_plan`) | Existant | Capacité vs charge ferme | Affectations, capacité |

## 11. Validation

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-VLD-01 | Liste | Validation des temps | `/validation` (`timesheet_validation`) | Existant · P2 (`validate:time`) | Valider/refuser les temps | Imputation, circuit |
| PG-VLD-02 | Liste | Validation des absences | – | À créer · P2/P5 | Circuit de validation des absences | AbsenceRequest, circuit |

## 12. Paramétrage & administration (P6 / admin)

| ID | Type | Nom | Route | Statut | Objectif / contenu | Données |
|----|------|-----|-------|--------|--------------------|---------|
| PG-ORG-01 | Détail | Organisation | `/organisation` (`organization_admin`) | Existant (`manage:organization`) | Unités & rattachements | OrgUnit, OrgMembership |
| PG-PRC-01 | Liste | Profils & taux | `/profils` (`pricing_admin`) | Existant (`manage:pricing`) | Profils, taux coût/vente, affectations | Profile, ProfileRate, SellingRate |
| PG-REF-01 | Liste | Jours fériés | `/parametrage/jours-feries` (`holiday_index`) | Existant | Gérer fériés | Holiday |
| PG-REF-02 | Liste | Fermetures | `/parametrage/fermetures` (`closure_index`) | Existant | Gérer fermetures | Closure |
| PG-REF-03 | Liste | Compétences | `/parametrage/competences` (`skill_index`) | Existant | Référentiel de compétences | Skill |
| PG-REF-04 | Liste | Régimes de travail | `/parametrage/regimes-travail` (`work_schedule_index`) | Existant | Temps partiel / régimes | WorkSchedule |
| PG-REF-05 | Liste | Circuits de validation | `/parametrage/circuits-validation` (`validation_circuit_index`) | Existant | Configurer les circuits | ValidationCircuit |
| PG-REF-06 | Liste | Journal d'audit | `/parametrage/audit` (`audit_log_index`) | Existant (`VIEW_AUDIT_LOG`) | Audit append-only | AuditLog |
| PG-ADM-01 | Liste | Périodes | `/administration/periodes` (`period_admin`) | Existant (`manage:periods`) | Clôturer/gérer les périodes | Period |
| PG-REL-01 | Liste | Relances | `/relances` (`reminders_page`) | Existant (`manage:reminders`) | Relancer les saisies manquantes | Complétude, notifications |

---

## 13. Notes d'accessibilité (WCAG 2.2 AA — écrans à fort enjeu)
- **Saisie P1** (PG-TMP-01/02/03) : cibles ≥ 44 px, focus visible, labels explicites, aucune action critique au survol, pas de captcha temporel (critère de rejet P1 : saisie > 2 min).
- **Kanban recrutement** (PG-REC-01) : **alternative clavier** au drag & drop (« Déplacer vers… ») + annonces `aria-live` (le contrôleur `tailsfadmin--kanban` les fournit).
- **Pages auth** : contraste AA, clavier complet, erreurs explicites.
- **Tableaux & listes** (clients, devis, projets, factures, staffing, RH) : en-têtes associés, tri/filtre accessibles, pagination clavier.
- **Breadcrumb** : repère de position pour tous (orientation, WCAG 2.4.8).

## 14. Hors périmètre
- Routes internes Symfony (`_profiler`, `_wdt`, `_error`) et techniques (`health`, `metrics`, `_probe`).
- API Platform (`/api/*`) — documentée via OpenAPI (non des « pages »).

## 15. Validation PO
- [ ] Dashboards par profil (8) + home de repli validés.
- [ ] Cycles de vie (commercial/projet, RH) et regroupement de menu validés.
- [ ] Domaines Devis / Factures / Recrutement / Contrats / Carrières validés.
- [ ] Conventions transverses (breadcrumb, en-tête contextuel, tabs) validées.
- [ ] Couverture 100 % des pages existantes confirmée.
- [ ] Feu vert pour démarrer US-081 (parcours par persona).
