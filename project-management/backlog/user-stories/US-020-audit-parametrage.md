# US-020: Journal d'audit du paramétrage

## Métadonnées
- **ID**: US-020
- **EPIC**: EPIC-001
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-33, INV-7, HAB-6
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-33 (journal qui/quoi/avant-après/quand), INV-7 (immuabilité du journal d'audit), HAB-6 (accès restreint aux journaux)

## User Story

**En tant qu'** administrateur tenant,
**je veux** que toute modification d'un paramètre de configuration (référentiel, seuil, circuit de validation, statut…) soit automatiquement enregistrée dans un journal d'audit immuable indiquant qui a modifié quoi, quelle était la valeur avant, quelle est la valeur après et quand,
**afin de** garantir la traçabilité complète du paramétrage, faciliter les audits de conformité, détecter les modifications non autorisées et reconstituer l'état de la configuration à tout instant passé.

## Critères d'Acceptation

### CA-1 (Nominal) : Traçabilité complète d'une modification de taux de profil
```gherkin
GIVEN l'ADMIN "alice@agence.fr" modifie le taux de vente du profil "Consultant Senior" de 700 €/j à 750 €/j
WHEN la modification est enregistrée
THEN une entrée est automatiquement créée dans le journal d'audit avec :
  - Auteur : alice@agence.fr (ID : U-042)
  - Action : MODIFICATION
  - Objet : Profil / Consultant Senior (ID : P-012)
  - Champ modifié : taux_vente
  - Valeur avant : 700.00 EUR/j
  - Valeur après : 750.00 EUR/j
  - Date/heure : 2026-09-01T14:32:17Z (UTC)
  - Contexte : date d'effet = 01/10/2026
  AND cette entrée est visible dans le journal d'audit du tenant dans les 5 secondes suivant la modification
  AND l'entrée ne peut être ni modifiée ni supprimée par aucun utilisateur, y compris l'ADMIN (INV-7)
```

### CA-2 (Nominal) : Recherche dans le journal d'audit par période, auteur et type d'objet
```gherkin
GIVEN le journal d'audit contient 1 200 entrées sur les 12 derniers mois
WHEN P6 Élodie (dirigeante) filtre le journal avec : auteur = "bob@agence.fr", période = septembre 2026, type d'objet = "Circuit de validation"
THEN la liste affiche uniquement les entrées correspondant aux trois critères simultanément
  AND les résultats sont triés par date décroissante (plus récent en premier)
  AND chaque entrée affiche : auteur, action, objet, valeurs avant/après, date/heure
  AND le filtre peut être exporté en CSV pour les audits externes
```

### CA-3 (Alternatif) : Reconstitution de l'état d'un paramètre à une date passée
```gherkin
GIVEN le taux de vente du profil "Chef de Projet" a été modifié 3 fois : 01/01 (600€), 01/04 (650€), 01/09 (680€)
WHEN l'ADMIN ou un auditeur consulte l'historique du profil "Chef de Projet" et sélectionne la date "15/05/2026"
THEN le système affiche la valeur en vigueur au 15/05/2026 : 650 €/j (entrée du 01/04)
  AND la chronologie des 3 modifications est visualisée avec flèches temporelles
  AND le "point dans le temps" sélectionné est mis en évidence sur la chronologie
```

### CA-4 (Alternatif) : Alerte sur modification sensible en dehors des heures ouvrables
```gherkin
GIVEN le tenant configure des alertes sur les modifications de paramètres sensibles (taux, circuits de validation, seuils financiers)
  AND une modification du circuit de validation des devis est effectuée le dimanche 07/09 à 02h14
WHEN la modification est enregistrée dans le journal d'audit
THEN P6 Élodie (dirigeante) reçoit une alerte : "Modification sensible détectée hors heures ouvrables : circuit de validation des devis modifié le 07/09 à 02h14 par user@agence.fr"
  AND l'entrée du journal est taguée "hors heures ouvrables" pour faciliter le filtrage lors d'audits
  AND l'alerte est également visible dans le tableau de bord des alertes (US-018)
```

### CA-5 (Erreur) : Tentative de suppression ou modification d'une entrée du journal → refus absolu
```gherkin
GIVEN une entrée du journal d'audit existe avec ID = LOG-00847
WHEN un utilisateur ADMIN (ou super-admin plateforme) appelle l'API DELETE /audit-logs/LOG-00847
THEN la réponse HTTP est 403 Forbidden avec le message : "Les entrées du journal d'audit sont immuables et ne peuvent être ni supprimées ni modifiées (INV-7)."
  AND aucune entrée n'est supprimée ou altérée
  AND la tentative de suppression est elle-même enregistrée dans le journal d'audit comme événement de sécurité "audit_log_deletion_attempt"
  AND si une API de modification (PATCH/PUT) est appelée sur une entrée de journal, la même protection s'applique
```

### CA-6 (Erreur) : Accès au journal d'audit complet par un rôle non habilité → refus et trace de sécurité
```gherkin
GIVEN un utilisateur avec le rôle "Chef de Projet" (P2 Marc) est authentifié
  AND le journal d'audit global est restreint aux rôles ADMIN et Dirigeant (HAB-6)
WHEN Marc tente d'accéder à l'URL /admin/audit-logs ou appelle l'API GET /audit-logs sans filtre sur ses propres actions
THEN le système renvoie HTTP 403 Forbidden avec le message : "Accès refusé : la consultation du journal d'audit complet est réservée aux rôles Administrateur et Dirigeant."
  AND aucune entrée du journal d'audit n'est retournée dans la réponse
  AND la tentative d'accès non autorisée est elle-même enregistrée dans le journal d'audit comme événement de sécurité "audit_log_unauthorized_access" (auteur, endpoint, timestamp)
  AND Marc peut toujours consulter ses propres actions dans son tableau de bord personnel (périmètre restreint autorisé par HAB-6)
```

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

INV-7 : le journal d'audit est immuable par design. La table `audit_log` doit avoir des permissions PostgreSQL en GRANT INSERT ONLY (pas d'UPDATE ni de DELETE) pour le rôle applicatif. Même l'ADMIN applicatif ne peut pas supprimer des entrées via l'application.

HAB-6 : l'accès au journal d'audit est restreint aux rôles ADMIN et Dirigeant (P6). Les autres rôles peuvent voir uniquement les entrées relatives à leurs propres actions dans leur tableau de bord personnel.

EF-REF-33 : le journal couvre TOUS les paramètres de configuration, pas uniquement les données financières. Cela inclut : référentiels (profils, compétences, devises, calendriers), circuits de validation, statuts, seuils d'alerte et paramètres tenant.

La traçabilité des modifications de données financières (US-011) et les événements de sécurité (US-001 CA-4/CA-5) alimentent le même journal d'audit central. L'architecture du journal est donc un composant transversal à tous les modules.

Rétention recommandée : 7 ans (contraintes légales comptables). L'archivage automatique après 7 ans est à prévoir dans un lot ultérieur.
