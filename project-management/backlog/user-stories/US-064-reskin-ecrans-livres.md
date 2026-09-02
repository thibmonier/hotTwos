# US-064: Reskin des écrans livrés selon les maquettes

## Métadonnées
- **ID**: US-064
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: Tous (P1 à P6) — écrans quotidiens de saisie et de pilotage
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (D4 — reskin), OBJ-7 (adoption), RSQ-1 (résistance à la saisie), résolution F-S5-4 et F-S5-5
- **Dépend de**: US-061 (design system), US-062 (maquettes validées), US-063 (layout intégré)
- **Spec Technique**: maquettes validées (US-062), chantier F-S5-4

## User Story

**En tant que** utilisateur de tous les rôles,
**je veux** que les écrans déjà livrés (saisie, /saisie/jour, projets, complétude, absences, relances, valorisation, organisation, profils, administration/périodes) soient reskinnés selon les maquettes validées et le design system,
**afin de** disposer d'une ergonomie définitive lisible et agréable au quotidien, sans perte de fonctionnalité, et de résoudre les écarts de recette à composante visuelle.

## Critères d'Acceptation

### CA-1 (Nominal) : Écrans du lot 1 conformes aux maquettes validées

```gherkin
GIVEN les maquettes du lot 1 sont validées (US-062) et le layout est intégré (US-063)
WHEN les écrans existants sont reskinnés
THEN chaque écran du lot 1 utilise le design system (tokens et composants US-061) et respecte sa maquette validée
  AND aucune fonctionnalité existante n'est perdue (les critères d'acceptation des US d'origine restent satisfaits)
  AND make ci reste vert et la recette navigateur est re-passée sur chaque écran reskinné
```

### CA-2 (Alternatif) : F-S5-4 résolu — collaborateurs identifiables

```gherkin
GIVEN /completude affichait les collaborateurs par préfixe d'UUID (indistinguables)
WHEN l'écran de complétude est reskinné selon la maquette
THEN chaque collaborateur est identifié par un libellé humain (nom d'affichage ou email selon la décision de conception)
  AND aucun identifiant technique brut (UUID/préfixe) n'est présenté à l'utilisateur
  AND l'affichage reste conforme aux habilitations (périmètre RBAC de l'utilisateur)
```

### CA-3 (Alternatif) : F-S5-5 traité — code couleur complet

```gherkin
GIVEN le code couleur de complétude était partiel
WHEN l'écran est reskinné
THEN les états de statut utilisent les tokens sémantiques complets (succès/avertissement/erreur/neutre) avec légende visible
  AND l'information n'est jamais portée par la seule couleur (libellé/icône associé — accessibilité)
```

### CA-4 (Erreur) : États non-nominaux couverts

```gherkin
GIVEN un écran peut être vide, en erreur ou en chargement
WHEN l'écran reskinné rencontre l'un de ces états
THEN l'état vide affiche un message explicite (pas d'écran cassé)
  AND l'état d'erreur affiche un message métier générique (pas de stack trace)
  AND l'état de chargement est signalé visuellement, cohérent avec le design system
```

### CA-5 (Erreur) : Aucun reskin sans maquette validée

```gherkin
GIVEN la consigne PO impose la conception avant le dev front
WHEN un écran dont la maquette n'est pas validée est présenté au reskin
THEN son reskin est reporté jusqu'à validation de sa maquette (US-062)
  AND seuls les écrans à maquette validée sont intégrés dans cette US
```

## Critères UI/UX

### Web
- Priorisation : écrans les plus utilisés d'abord (saisie, complétude), puis pilotage et administration.
- Conformité stricte aux maquettes validées ; tout écart est renvoyé en conception (US-062).

### Mobile
- Déclinaison mobile appliquée conformément aux maquettes (liste vs grille, actions au tactile).
- Aucune action clé uniquement au survol ; cibles ≥ 44 × 44 px.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Écrans du lot 1 reskinnés conformes aux maquettes
- [ ] F-S5-4 (noms collaborateurs) et F-S5-5 (code couleur) résolus
- [ ] Aucune régression fonctionnelle (make ci vert, recette re-passée)
- [ ] États vide/erreur/chargement couverts
- [ ] Documentation / captures mises à jour

---

## Notes

**Découpage** : 8 points — susceptible d'être scindé en affinage (ex. lot saisie/complétude d'abord, puis pilotage/administration). Le reskin d'un écran est conditionné à la validation de sa maquette (US-062).

**Dette absorbée** : F-S5-4 (chantier `project-management/chantiers/CHANTIER-F-S5-4-completude-noms-collaborateurs.md`), F-S5-5 (code couleur), rappel F1 (identifiants bruts). Voir `.recette/reports/`.

**Non-régression** : chaque écran conserve les critères d'acceptation de son US fonctionnelle d'origine ; le reskin est purement présentationnel.
