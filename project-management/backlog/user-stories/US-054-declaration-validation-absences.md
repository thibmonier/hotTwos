# US-054: Déclaration, validation et compteurs d'absences

## Métadonnées
- **ID**: US-054
- **EPIC**: EPIC-003
- **Sprint**: Sprint 2
- **Statut**: ✅ Done (livré Sprint 5)
- **Points**: 5
- **Persona**: P1 (Camille — collaborateur), P2 (Marc — chef de projet / valideur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-14 (déclaration d'absence : type, dates, maille demi-journée), EF-TMP-15 (circuit de validation avec notification du résultat au demandeur), EF-TMP-16 (compteurs acquis/pris/en attente/solde, exacts à date et projetés en fin de période), RG-TMP-3 (une absence validée bloque l'imputation de temps de production sur la période concernée), HAB-3 (données de santé minimisées : type « arrêt maladie » + dates uniquement, jamais de motif médical ou de diagnostic)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base — intégration dans la vue de saisie)
- **Spec Technique**: EF-TMP-14, EF-TMP-15, EF-TMP-16, RG-TMP-3, HAB-3

## User Story

**En tant que** collaborateur (Camille) et chef de projet valideur (Marc),
**je veux** déclarer mes absences (type, dates, maille demi-journée), suivre leur validation via un circuit notifié et consulter mes compteurs de congés en temps réel,
**afin de** gérer mes absences de façon autonome, transparente et sans risque d'imputation de production sur une période d'absence validée.

## Critères d'Acceptation

### CA-1 (Nominal) : Déclaration d'absence et notification du circuit de validation

```gherkin
GIVEN Camille est connectée avec le rôle "Collaborateur"
  AND le type d'absence "Congés payés" est configuré dans le référentiel tenant
WHEN elle accède au module "Absences" et crée une demande :
  - Type : Congés payés
  - Du : 01/09/2026 matin
  - Au : 05/09/2026 soir
  - Commentaire optionnel : "Vacances d'été"
  AND soumet la demande
THEN la demande est créée avec le statut "En attente de validation"
  AND Marc (manager défini dans la hiérarchie du tenant) reçoit une notification (email + notification in-app) avec le détail de la demande
  AND le compteur "En attente" de Camille est incrémenté de 5 jours
  AND Camille reçoit une confirmation "Votre demande de congés du 01/09 au 05/09/2026 a été soumise à validation"
```

### CA-2 (Alternatif) : Absence validée bloque l'imputation de production sur la période

```gherkin
GIVEN la demande d'absence de Camille du 01/09 au 05/09/2026 a été approuvée par Marc
  AND l'absence est au statut "Validée"
WHEN Camille tente de saisir une imputation de production de 4h sur P-Alpha pour le mardi 02/09/2026
THEN la saisie est refusée avec le message "Impossible d'imputer du temps de production sur une période d'absence validée (02/09/2026 — Congés payés)"
  AND la cellule du 02/09/2026 est grisée et non éditable dans la vue de saisie
  AND les compteurs affichent : Acquis 25j, Pris 5j, En attente 0j, Solde 20j (valeurs calculées pour cet exemple)
  AND l'imputation refusée n'est pas enregistrée en base
```

### CA-3 (Alternatif) : Compteurs exacts à date et projetés fin de période

```gherkin
GIVEN Camille a : 25 jours de congés acquis, 10 jours pris, 3 jours en attente de validation
  AND la période de référence se termine le 31/12/2026
WHEN elle consulte le widget "Mes compteurs d'absences"
THEN les valeurs affichées sont :
  - Acquis à date : 25 j
  - Pris (validés) : 10 j
  - En attente : 3 j
  - Solde disponible : 15 j (= 25 - 10)
  - Solde projeté fin de période (si les 3 j en attente sont validés) : 12 j
  AND les compteurs sont recalculés en temps réel sans rechargement de page
  AND un tooltip sur "Projeté" explique "Calcul si toutes les absences en attente sont approuvées"
```

### CA-4 (Erreur) : Motif médical ou diagnostic refusé à la saisie (HAB-3)

```gherkin
GIVEN Camille déclare une absence de type "Arrêt maladie"
WHEN le formulaire de déclaration lui propose un champ "Motif médical" ou "Diagnostic"
THEN ce champ n'existe PAS dans le formulaire (HAB-3 : minimisation des données de santé)
  AND les seules informations enregistrées sont : type "Arrêt maladie", date de début, date de fin, maille (journée/demi-journée), numéro de justificatif (optionnel)
  AND si le code source ou l'API contient un champ "motif_medical", le test de conformité RGPD échoue et bloque la livraison
  AND le manager ne voit que le type et les dates, jamais de données médicales détaillées
```

### CA-5 (Erreur) : Refus de la demande — notification et soldes inchangés

```gherkin
GIVEN Camille a soumis une demande de 5 jours de congés (statut "En attente")
  AND son compteur "En attente" est à 5j
WHEN Marc refuse la demande avec le motif "Chevauchement avec livraison critique — semaine indisponible"
THEN Camille reçoit une notification "Votre demande de congés du 01/09 au 05/09/2026 a été refusée. Motif : Chevauchement avec livraison critique — semaine indisponible"
  AND le compteur "En attente" revient à 0j
  AND le compteur "Pris" reste inchangé (les jours refusés ne sont pas décomptés)
  AND la période du 01/09 au 05/09/2026 redevient disponible à la saisie de production dans la vue de saisie
  AND Camille peut soumettre une nouvelle demande pour d'autres dates
```

## Critères UI/UX

### Web
- Le formulaire de déclaration d'absence est accessible depuis la vue de saisie (lien contextuel dans la cellule d'un jour) et depuis le module dédié "Absences".
- Le calendrier de sélection de dates supporte la sélection par plage (clic-glisser ou clic début puis clic fin) avec distinction visuelle matin/après-midi pour la maille demi-journée.
- Les compteurs sont affichés dans un widget persistent (barre latérale ou en-tête de la vue de saisie) sans nécessiter de navigation vers une autre page.
- Le statut de chaque demande est représenté par un badge coloré : ⏳ En attente (orange), ✅ Validée (vert), ❌ Refusée (rouge).

### Mobile
- La déclaration d'absence est réalisable intégralement sur mobile (voir EF-TMP-6) : le calendrier de dates est adapté au tactile (pas de date-picker natif qui ne supporte pas les plages).
- Les compteurs sont accessibles depuis l'écran d'accueil mobile en un tap (widget condensé : "Solde : 15 j").
- Les notifications de validation/refus déclenchent une notification push système si les permissions sont accordées.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

**RG-TMP-3** : le blocage de l'imputation de production sur une période d'absence validée est implémenté au niveau API (contrôle serveur), pas seulement au niveau UI. Une tentative via l'API directe doit également être refusée avec HTTP 422 et un message explicite.

**HAB-3 (données de santé)** : conformément au RGPD (catégories spéciales, art. 9), seul le type normalisé (ex : "Arrêt maladie", "Hospitalisation") est stocké, jamais le diagnostic ni le motif médical. L'audit de conformité RGPD vérifie l'absence de tout champ de données médicales dans le schéma de base de données et dans l'API.

**Compteurs projetés (EF-TMP-16)** : le calcul projeté prend en compte uniquement les absences au statut "En attente" ou "Validée". Les demandes refusées ne participent pas au calcul. L'acquisition de nouveaux droits (jours acquis en cours de période) peut être paramétrable par tenant (acquisition mensuelle, annuelle, etc.).

**Circuit de validation** : le circuit par défaut est N+1 direct (manager défini dans la fiche collaborateur). Un circuit multi-niveaux (N+1 puis DRH) est prévu en lot 2 ; cette story couvre uniquement le circuit à un seul niveau.
