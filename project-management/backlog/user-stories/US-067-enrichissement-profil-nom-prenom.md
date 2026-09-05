# US-067: Enrichissement du profil utilisateur (nom et prénom)

## Métadonnées
- **ID**: US-067
- **EPIC**: EPIC-000
- **Sprint**: Backlog (non assigné)
- **Statut**: ✅ Done (livré Sprint 8)
- **Points**: 3
- **Persona**: Tous (P1-P6) — identification lisible ; bénéfice premier P2 Marc / P3 Sophie (pilotage)
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: décision PO du 2026-09-02 (validation maquettes EPIC-012) — remplacer le pis-aller
  « e-mail comme libellé collaborateur » (F-S5-4) par un vrai nom d'affichage.
- **Dépend de**: US-002 (entité `App\Domain\User\User`, cycle de vie utilisateur), US-001 (isolation tenant)
- **Lié à**: US-058 (complétude), US-064 (reskin — F-S5-4 e-mail → nom une fois livré)

## User Story

**En tant que** utilisateur et manager (chef de projet, resource manager, direction),
**je veux** que les collaborateurs soient identifiés par leur **nom et prénom** (et non uniquement par leur
adresse e-mail),
**afin de** lire les écrans de pilotage (complétude, valorisation, projets) de façon naturelle et sans
ambiguïté, et lever le pis-aller e-mail décidé en F-S5-4.

## Critères d'Acceptation

### CA-1 (Nominal) : Affichage « Prénom Nom » partout où l'e-mail était montré

```gherkin
GIVEN un collaborateur dont le profil porte un prénom "Camille" et un nom "Martin"
WHEN un manager consulte un écran affichant des collaborateurs (complétude, valorisation, projets)
THEN chaque collaborateur est affiché sous la forme "Camille Martin"
  AND l'adresse e-mail n'est plus le libellé principal (elle peut rester en information secondaire)
  AND aucun identifiant technique (UUID, préfixe) n'est affiché (rappel F1)
```

### CA-2 (Alternatif) : Renseignement du nom/prénom

```gherkin
GIVEN un utilisateur authentifié
WHEN il renseigne son prénom et son nom (écran "Mon compte", ou l'administration pour un tiers habilité)
THEN les valeurs sont enregistrées sur son profil
  AND elles sont immédiatement reflétées dans les écrans qui l'affichent
  AND l'opération respecte l'habilitation (un utilisateur modifie son propre profil ; un tiers requiert un droit d'administration)
```

### CA-3 (Alternatif) : Rétrocompatibilité — comptes sans nom

```gherkin
GIVEN un compte existant dont le nom et le prénom ne sont pas encore renseignés
WHEN un écran doit afficher ce collaborateur
THEN l'application affiche un libellé de repli lisible (l'adresse e-mail)
  AND aucun écran ne casse (pas de "null null", pas d'erreur)
  AND le collaborateur reste identifiable de façon non ambiguë
```

### CA-4 (Erreur) : Validation des entrées

```gherkin
GIVEN un utilisateur saisit son nom ou son prénom
WHEN une valeur est invalide (trop longue, vide alors que requise, caractères de contrôle)
THEN la saisie est rejetée avec un message clair
  AND aucune donnée non validée n'est persistée (validation serveur, pas seulement client)
  AND les champs sont protégés contre l'injection (échappement à l'affichage)
```

### CA-5 (Erreur) : Isolation multi-tenant préservée

```gherkin
GIVEN deux tenants distincts
WHEN un manager consulte la liste des collaborateurs
THEN il ne voit que les noms/prénoms des collaborateurs de son propre tenant (filtre ORM + RLS)
  AND aucune fuite de nom entre tenants n'est possible via l'API ou les écrans
```

## Critères UI/UX

### Web
- Le nom d'affichage « Prénom Nom » remplace l'e-mail comme libellé principal dans les tables et listes de collaborateurs.
- L'e-mail reste disponible en information secondaire (au survol, en sous-texte, ou dans le détail) sans être le libellé.
- Champs prénom/nom éditables dans l'écran « Mon compte » (cf. US-068).

### Mobile
- Le libellé « Prénom Nom » reste lisible et tronqué proprement si nécessaire (pas de débordement).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Champs nom/prénom ajoutés à l'entité `User` + migration (RLS/tenant respectés)
- [ ] Affichage « Prénom Nom » avec repli e-mail sur les écrans concernés
- [ ] Validation serveur + protection injection
- [ ] Isolation multi-tenant testée
- [ ] `make ci` vert ; documentation mise à jour

---

## Notes

**Origine** : décision PO du 2026-09-02 (`design-canvas/VALIDATION.md`). F-S5-4 avait retenu l'e-mail comme
libellé faute de nom/prénom en base ; cette US livre le vrai nom d'affichage et **remplace** ce pis-aller.

**Périmètre** : ajout de deux attributs de profil + affichage. **Pas** de refonte du cycle de vie utilisateur
ni de la création de compte (cf. US-068, question ouverte sur la création de compte). Non bloquant pour le lot 1.

**Sécurité** : données personnelles (RGPD) — nom/prénom sont des données identifiantes ; accès borné au tenant,
pas d'exposition inter-tenant, journalisation des modifications sensibles si applicable (HAB-6).
