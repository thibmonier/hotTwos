# US-013: Référentiel de compétences & niveaux

## Métadonnées
- **ID**: US-013
- **EPIC**: EPIC-001
- **Sprint**: 16
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: P-ADMIN (administrateur tenant) / P3 (Resource Manager, consommateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-07 (affinage S16 — recadrage référentiel, hors moteur de staffing)

## Traçabilité
- **Implémente**: EF-REF-10 (catégories de compétences), EF-REF-11 (échelle de niveau paramétrable), RG-REF-1 (désactivation, pas suppression)
- **Dépend de**: US-001 (multi-tenant)
- **Reporté (hors périmètre)** : la **recherche/staffing multi-critères** (disponibilité, matching) relève du **module RES** — pas cette US. La réduction dynamique de l'échelle avec réindexation est hors périmètre (échelle fixée à la création, ajustée sans casser l'existant).

## User Story

**En tant qu'** administrateur tenant,
**je veux** structurer un **référentiel de compétences** (catégorie + libellé) avec une **échelle de niveaux paramétrable**, et associer des compétences évaluées aux collaborateurs,
**afin de** qualifier objectivement les profils et préparer le staffing (moteur de recherche livré ultérieurement).

## Contexte (Conversation)
Aucun référentiel de compétences n'existe aujourd'hui. Cette US pose la **fondation référentielle**
(catégories, compétences, échelle de niveaux, association collaborateur↔compétence+niveau), consommée
plus tard par le staffing. Persona gestionnaire = ADMIN ; consommateur = Resource Manager.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : créer une compétence et l'associer à un collaborateur
```gherkin
GIVEN l'échelle de niveaux du tenant est « Débutant, Intermédiaire, Avancé, Expert »
  AND la catégorie « Technique » existe
WHEN l'administrateur crée la compétence « React.js » (Technique)
  AND l'associe au collaborateur « Pierre Martin » au niveau « Avancé »
THEN « React.js » apparaît dans le référentiel avec sa catégorie et le nombre de collaborateurs (1)
  AND le profil de Pierre porte « React.js — Avancé »
```

### CA-2 (Nominal) : l'échelle de niveaux est paramétrable (tenant)
```gherkin
GIVEN l'administrateur configure l'échelle de niveaux du tenant
WHEN il définit 5 niveaux ordonnés (« Notions, Débutant, Intermédiaire, Avancé, Expert »)
THEN les nouvelles associations proposent ces 5 niveaux dans l'ordre
  AND l'échelle est stockée au niveau tenant (pas en dur), utilisée par tous les écrans de saisie
```

### CA-3 (Alternatif) : désactivation d'une compétence obsolète (RG-REF-1)
```gherkin
GIVEN la compétence « Lotus Notes » est associée à d'anciens collaborateurs
WHEN l'administrateur désactive « Lotus Notes »
THEN elle n'apparaît plus dans les listes de saisie pour de nouvelles associations
  AND les associations existantes la conservent en lecture seule (aucune suppression)
```

### CA-4 (Alternatif) : lister le référentiel par catégorie
```gherkin
GIVEN plusieurs compétences réparties en catégories (Technique, Fonctionnel, Méthodologique…)
WHEN l'administrateur consulte le référentiel
THEN les compétences sont regroupées par catégorie, triées par libellé
  AND chaque ligne indique le nombre de collaborateurs qui la possèdent
```

### CA-5 (Erreur) : compétence en doublon refusée (insensible à la casse)
```gherkin
GIVEN la compétence « JavaScript » (Technique) existe déjà
WHEN l'administrateur tente de créer « javascript » (Technique)
THEN la création est refusée avec un message signalant le doublon (« JavaScript » existe déjà)
  AND aucune compétence n'est créée (unicité tenant + catégorie + libellé normalisé)
```

### CA-6 (Erreur) : accès refusé sans habilitation
```gherkin
GIVEN un utilisateur sans permission de gestion du paramétrage (MANAGE_ORGANIZATION)
WHEN il tente de créer/modifier une compétence ou l'échelle
THEN l'accès est refusé (403) et aucune modification n'est possible
```

## Notes techniques (pour la décomposition)
- **Entités** (`Domain\Skill`, `TenantOwned`, RLS) : `Skill` (catégorie via enum `SkillCategory`, libellé, `active`), `SkillLevelScale` (échelle ordonnée du tenant, valeur par défaut 4 niveaux), `SkillAssignment` (userId + skillId + niveau).
- **Unicité** : `(tenant_id, category, normalized_label)` pour `Skill` ; `(tenant_id, user_id, skill_id)` pour l'association.
- **Gating** : gestion réservée à `MANAGE_ORGANIZATION` ; lecture selon RBAC.
- **UI** : page admin `/parametrage/competences` (Twig, CSRF) : catégories, échelle, compétences, association.
- **Immuabilité** : désactivation, jamais suppression (RG-REF-1).
- **Tests** : unit (unicité/normalisation, échelle) + fonctionnels (CRUD, désactivation, 403) ; ajouter les entités aux SchemaTool concernés.

## Definition of Ready
- [x] Description INVEST recadrée (référentiel ; staffing reporté)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Estimation 3 pts confirmée
- [x] Dépendances (US-001) ; gating/RLS explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels), `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] Migration + RLS ; code review

---

## Notes
La recherche combinée de compétences (matching staffing, disponibilité calendaire) est **explicitement
hors périmètre** (module RES). L'échelle par défaut à 4 niveaux sera pré-provisionnée à la création du
tenant (US-019).
