# Sprint 4 : Valorisation automatique du temps validé

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 4 |
| Début | 2026-09-29 |
| Fin | 2026-10-10 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~21 points (vélocité observée S1=29, S2=20, S3=23 → moy. ~24) |
| Base git | `main` (Sprints 1-3 mergés ; RLS active en prod) |

## Sprint Goal

> « Dès qu'un temps est validé, il est automatiquement valorisé — coût de revient et taux de vente en vigueur à la période — et le modèle analytique reflète la marge du projet. »

C'est la **convergence du produit** : la chaîne `saisie → validation → valorisation` est bouclée, et les faits `fact_project_revenue` (aujourd'hui alimentés par une sonde) deviennent **réels**, dérivés du temps validé × tarifs. L'invariant `INV-2` / la non-divergence (`ARC-113`) garantissent des chiffres fiables et reproductibles.

## Definition of Done (rappel)

- [ ] Tests unitaires + intégration verts, couverture ≥ 80 % sur le code touché
- [ ] `make ci` vert (PHPStan max, Deptrac 0, cs-fixer, Rector, gitleaks)
- [ ] Isolation multi-tenant (filtre ORM + RLS prod) respectée
- [ ] Habilitation vérifiée côté serveur (ARC-19/ARC-106)
- [ ] **Historisation à date d'effet** : une révision tarifaire ne modifie jamais les valorisations passées
- [ ] Documentation mise à jour ; déployable en production (staging à jour, smoke vert)

## Sprint Backlog

| ID | Titre | Points | Persona | Statut |
|----|-------|--------|---------|--------|
| US-010 | Structure organisationnelle et rattachements historisés | 5 | Admin tenant | 🔵 To Do |
| US-011 | Référentiel de profils : coûts & taux de vente historisés à date d'effet | 8 | Admin tenant | 🔵 To Do |
| US-060 | Valorisation automatique après validation (≤ 15 min) | 8 | P2 Marc / P6 Dirigeant | 🔵 To Do |

**Total engagé : 21 points** (dans la vélocité cible 20–40).

## Objectifs de sortie (critères d'acceptation du sprint)

1. **Organisation** : l'admin paramètre une hiérarchie (1..N niveaux) et historise les rattachements des collaborateurs à date d'effet (US-010).
2. **Tarifs temporels** : chaque profil porte un coût de revient et un taux de vente **historisés à date d'effet** ; la valeur en vigueur à une date donnée est déterministe (US-011).
3. **Valorisation** : à la validation d'un temps (US-055, déclencheur), le système calcule coût & vente avec le tarif **en vigueur à la période**, **fige** la valorisation à la date de validation, et met à jour le modèle analytique en ≤ 15 min (US-060).
4. **Non-divergence** : les faits valorisés passent le test de non-divergence (`ARC-113`) ; une révision tarifaire future ne change pas les valeurs figées.

## Cadrage / périmètre

- **Déclencheur** : la valorisation part de l'événement de validation (US-055 ✅) → publication d'un événement de domaine → projecteur analytique (US-005 ✅). La sonde `RevenueRecognized` est **remplacée/complétée** par un événement de valorisation réel (temps validé × taux).
- **Profil du collaborateur** : US-011 pose les profils + taux ; le rattachement collaborateur→profil (à date d'effet) est le pivot de la valorisation. Périmètre minimal suffisant : un profil par collaborateur à une date.
- **Reporté** : rentabilité/atterrissage avancés (US-036), facturation (FIN), reporting dirigeant riche (PIL) — ultérieurs. Ici : coût, vente, marge de base par projet.

## Dépendances

| Élément | Dépend de | Statut |
|---------|-----------|--------|
| US-010 | US-001 (multi-tenant) | ✅ |
| US-011 | US-010 (org) | séquentiel (même sprint) |
| US-060 | US-011 (taux), US-055 (validation ✅), US-005 (projecteur ✅) | US-011 en amont |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Historisation à date d'effet mal modélisée (chevauchements, trous) | Moyenne | **Fort** | Value Object « période de validité » testé (bornes, non-chevauchement) ; TDD sur la résolution du tarif à une date |
| Valorisation non figée (recalcul rétroactif) | Moyenne | **Fort** | Valeur **copiée** dans le fait à la validation (snapshot), jamais recalculée ; test de non-régression après révision tarifaire |
| Divergence analytique (ARC-113) | Faible | Fort | Réutiliser le `DivergenceChecker` (US-005) sur l'indicateur valorisé |
| Latence > 15 min (US-060) | Faible | Moyen | Projection synchrone ou asynchrone bornée ; mesure |

## Cérémonies

| Cérémonie | Cadence |
|-----------|---------|
| Sprint Planning P1/P2 | Début de sprint |
| Daily Scrum | Quotidien (`daily-notes/`) |
| Affinage (Sprint 5 : reporting / rentabilité) | Mi-sprint |
| Sprint Review (démo saisie→validation→marge) | Fin de sprint |
| Rétrospective | Fin de sprint |

## Notes

- Boucle enfin fermée : **la valeur en euros** apparaît. Les faits analytiques deviennent réels (fin de la sonde).
- Actions de rétro Sprint 3 à intégrer si capacité : **E2E chronométré ≤ 2 min** (`RSQ-1`), **extension RLS aux tables métier** (DBT-SEC-1), **fixtures de démo**. Priorité au métier ; ces actions restent ouvertes.
