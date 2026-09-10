# US-085: Backlog de refonte/reskin priorisé (MoSCoW)

## Métadonnées
- **ID**: US-085
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 19
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: Tous (P1–P6)
- **Créé le**: 2026-09-10
- **Mis à jour**: 2026-09-10

## Traçabilité
- **Implémente**: EPIC-013 (C6 — Déduire le backlog de refonte priorisé MoSCoW)
- **Dépend de**: US-083 (mapping pages↔composants + gaps — fournit l'effort de reskin et les dépendances bundle), US-080 (référentiel complet des pages), US-081 (parcours per persona — révèle la valeur métier de chaque page)
- **Alimente**: Tous les EPICs modules (EPIC-002 Projets, EPIC-003 Temps, EPIC-004 Staffing, EPIC-005 Finance, EPIC-006 CRM, EPIC-007 Pilotage, EPIC-008 RH…) — chaque EPIC consomme ce backlog pour planifier ses sprints de refonte front

## User Story

**En tant que** Product Owner,
**je veux** un backlog de refonte/reskin priorisé en MoSCoW, ventilé par EPIC module cible, dérivé du recensement des pages, des parcours et du mapping composants,
**afin de** transformer un backlog implicite (« reskiner l'existant ») en un backlog explicite, traçable et actionnable qui irrigue les EPICs modules et permet de planifier les sprints de refonte front avec des critères de priorisation documentés.

## Contexte (Conversation)
Le référentiel de pages (US-080), les parcours par persona (US-081) et le mapping composants (US-083)
permettent désormais de raisonner sur *quelles* pages refaire, *dans quel ordre*, et *avec quel effort*.
Cette US — dernière étape du sprint de conception — synthétise ces informations en un backlog priorisé.

Chaque item du backlog correspond à un écran (ou groupe d'écrans cohérents) et précise :
- la priorité MoSCoW (Must/Should/Could/Won't) selon la valeur métier, la fréquence d'usage persona
  et l'effort de reskin ;
- le critère de priorisation explicité (ex. « Must — parcours P1 critique, 80 % des utilisateurs,
  critère de rejet saisie > 2 min ») ;
- le EPIC module cible (à quel EPIC cette refonte appartient) ;
- les gaps de composants liés (dépendances bundle à lever en amont) ;
- un effort de reskin indicatif en points.

Les items Won't sont conservés dans le backlog avec justification (reportés à une version ultérieure
ou hors périmètre actuel), afin de ne pas les perdre.

Points à clarifier :
- Format : tableau Markdown (colonnes : ID page, Nom, Module, MoSCoW, Critère de priorisation,
  EPIC cible, Gaps liés, Effort reskin (pts indicatif), Statut US).
- Critères de priorisation explicites : valeur métier (OBJ), fréquence d'usage persona (P1 prioritaire),
  effort de reskin estimé, dépendances de gaps composants.
- Un item de backlog peut regrouper plusieurs pages (ex. « Tableau de bord dirigeant — 3 pages »)
  si leur refonte forme un tout cohérent.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : chaque page du référentiel reçoit une priorité MoSCoW
```gherkin
GIVEN le référentiel `page-inventory.md` (US-080) et le mapping `page-component-mapping.md` (US-083)
WHEN le PO produit le backlog de refonte
THEN chaque page du référentiel est présente dans le backlog avec une priorité MoSCoW explicite
     (Must / Should / Could / Won't)
  AND le critère de priorisation est renseigné pour chaque item
     (valeur métier, fréquence d'usage persona, effort reskin estimé)
```

### CA-2 (Nominal) : ventilation par EPIC module et rattachement explicite
```gherkin
GIVEN le backlog de refonte priorisé
WHEN l'équipe consulte les items de priorité Must ou Should
THEN chaque item Must ou Should est rattaché à un EPIC module cible
     (ex. EPIC-003 Temps, EPIC-002 Projets, EPIC-005 Finance…)
  AND aucun item Must ou Should n'est sans EPIC cible renseigné
```

### CA-3 (Alternatif) : items Could/Won't portent une justification explicite
```gherkin
GIVEN des pages classées Could ou Won't dans le backlog
WHEN le PO ou un membre de l'équipe consulte la justification de ces items
THEN chaque item Could ou Won't porte une raison explicite
     (ex. « Won't S19 — page de paramétrage rare, P6 uniquement, aucun parcours critique »
      ou « Could — dépend d'un gap composant gantt, priorité bundle faible »)
  AND ces items restent visibles dans le backlog (pas supprimés) pour référence future
```

### CA-4 (Alternatif) : pages des parcours critiques P1 classées Must
```gherkin
GIVEN les pages traversées par le parcours critique de P1 (saisie de temps, planning hebdo, absences/congés)
     identifiées dans US-081
WHEN le backlog est produit
THEN ces pages sont classées Must dans le backlog de refonte
  AND leur critère de priorisation mentionne explicitement l'enjeu d'adoption P1
     (80 % des utilisateurs, critère de rejet saisie > 2 min)
  AND les éventuels gaps de composants bloquants pour ces pages sont signalés en colonne « Gaps liés »
```

### CA-5 (Erreur) : item Must ou Should sans EPIC cible
```gherkin
GIVEN un item de priorité Must ou Should dans le backlog de refonte
  AND dont la colonne « EPIC cible » est vide ou contient « À définir »
WHEN le PO effectue la revue de validation
THEN il refuse la validation pour cet item
  AND la story ne peut être marquée Done tant qu'un item Must ou Should est sans EPIC cible renseigné
```

### CA-6 (Erreur) : backlog non validé PO — ne peut pas alimenter les EPICs modules
```gherkin
GIVEN le backlog de refonte produit par le concepteur mais sans validation PO formalisée
     (commentaire de PR, signature dans le fichier ou note de review)
WHEN un responsable de module tente de référencer ce backlog dans un EPIC
THEN le backlog n'est pas reconnu comme source de vérité officielle
  AND la story reste en statut Review jusqu'à la validation PO formalisée
```

## Notes sur les livrables
- **Livrable** : `project-management/architecture/backlog-reskin-priorise.md`
- **Format** : tableau Markdown (colonnes : ID page, Nom, Module, MoSCoW, Critère de priorisation, EPIC cible, Gaps liés, Effort reskin (pts indicatif), Statut US) ; une ligne par page ou groupe de pages cohérent.
- **Accessibilité** : les items Must portent une annotation sur les exigences WCAG 2.2 AA de la page (ex. « WCAG : formulaire accessible, focus visible, cibles ≥ 44 px, label explicite » pour les écrans de saisie P1).
- **Parité tactile** : les pages accessibles uniquement au survol sont signalées comme dette WCAG dans la colonne « Critère de priorisation ».
- **Hors périmètre** : rédaction des US de refonte (portée par les EPICs modules à partir de ce backlog) ; estimation détaillée des tâches de développement front ; maquettes haute-fidélité (US-084, reportée).
- **Validation** : revue PO formalisée (signature ou commentaire) ; cohérence vérifiée avec US-080 (couverture exhaustive) et US-083 (effort reskin reflète les gaps identifiés).

## Definition of Ready
- [x] Description INVEST (borné : synthèse du recensement en backlog MoSCoW ; 3 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Dépend d'US-083 (effort reskin connu), US-080 (périmètre complet), US-081 (valeur par persona)
- [x] Critères de priorisation MoSCoW définis (valeur métier OBJ, fréquence d'usage persona, effort reskin, dépendances gaps)
- [x] Validation INVEST : Independent ✓ (une fois US-083 livrée) / Negotiable ✓ (granularité du regroupement d'items adaptable) / Valuable ✓ (transforme la conception en plan d'action actionnable pour les EPICs modules) / Estimable ✓ (3 pts — synthèse tabulaire) / Sized ✓ (≤ 8 pts) / Testable ✓ (présence, cohérence et validation PO vérifiables)

## Definition of Done
- [ ] `project-management/architecture/backlog-reskin-priorise.md` créé et commité
- [ ] Toutes les pages du référentiel (US-080) présentes dans le backlog avec priorité MoSCoW
- [ ] Critères de priorisation explicités pour chaque item
- [ ] Chaque item Must et Should rattaché à un EPIC module cible
- [ ] Items Could/Won't justifiés (raison explicite)
- [ ] Pages des parcours P1 critiques classées Must avec mention de l'enjeu d'adoption
- [ ] Exigences WCAG 2.2 AA annotées sur les items Must
- [ ] Revue PO formalisée (commentaire PR ou annotation dans le fichier)
- [ ] PR mergée sur la branche de sprint 19
