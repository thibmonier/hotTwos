# Sprint 19 : Conception UX — recensement, parcours & mapping sur le socle (EPIC-013)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 19 |
| Début | 2027-04-29 *(provisoire)* |
| Fin | 2027-05-12 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~16 points (sprint de conception — livrables documentaires) |
| Base git | `main` (après clôture US-086 — socle tailsfadmin, PR #112/#113) |
| EPIC | EPIC-013 (Recensement pages cibles, parcours par persona & conception UX) |
| Statut | 🟡 En préparation — 4 items à affiner en Ready avant lancement |

## Sprint Goal

> « **Cartographier le produit sur le socle** : établir le **référentiel exhaustif des pages cibles**,
> les **parcours par persona (P1–P6)**, le **mapping pages ↔ composants tailsfadmin** (avec les *gaps*),
> et en déduire un **backlog de reskin priorisé (MoSCoW)** — pour préparer la refonte des écrans sur le
> socle désormais en place. »

**Positionnement** : premier incrément d'EPIC-013, **exécutable maintenant** puisque le socle
tailsfadmin (US-086) est livré et ses composants adoptés (Tabs, ProgressBar, Clipboard, sidebar repliable).
Sprint **de conception** (livrables = documents/cartes, pas de code produit). L'**audit UX de l'existant**
(US-082) et les **maquettes haute-fidélité** (US-084) sont **reportés** : ils nécessitent l'accès au
produit antérieur `hotones` (non disponible) — à planifier dès l'accès fourni.

## Sprint Backlog

| Priorité | ID | Titre | Points | EF / Origine | DoR |
|----------|-----|-------|--------|--------------|-----|
| 🔴 Must | US-080 | Référentiel exhaustif des pages cibles | 5 | EPIC-013 | ⏳ à affiner |
| 🔴 Must | US-081 | Cartographie des parcours par persona (P1–P6) | 5 | EPIC-013 | ⏳ à affiner |
| 🟡 Should | US-083 | Mapping pages ↔ composants tailsfadmin (+ gaps) | 3 | EPIC-013 | ⏳ à affiner |
| 🟡 Should | US-085 | Backlog de refonte/reskin priorisé (MoSCoW) | 3 | EPIC-013 | ⏳ à affiner |

**Engagé : 16 pts** (capacité ~16).

### Reporté (hors sprint — dépendance externe)

| ID | Titre | Points | Blocage |
|----|-------|--------|---------|
| US-082 | Audit & critique UX de l'existant (inspiré hotones) | 5 | Accès `hotones` requis |
| US-084 | Maquettes haute-fidélité des écrans prioritaires (validées PO) | 8 | Accès `hotones` + sortie US-080/081/083/085 |

## Ordre d'exécution
1. **US-080** (référentiel des pages) — socle de tout le sprint : liste exhaustive des écrans cibles.
2. **US-081** (parcours par persona) — enchaîne les pages du référentiel en parcours P1–P6.
3. **US-083** (mapping ↔ composants) — associe chaque page aux composants tailsfadmin, identifie les *gaps* (composants manquants à demander au bundle).
4. **US-085** (backlog reskin MoSCoW) — synthétise le tout en un backlog priorisé, entrée des sprints de refonte.

## Definition of Ready (avant lancement)
- [ ] US-080 : périmètre = toutes les routes applicatives actuelles + écrans cibles manquants ; format tableau (page, route, persona(s), statut existant/à créer).
- [ ] US-081 : 1 diagramme de parcours (Mermaid) par persona P1–P6 ; s'appuie sur `personas.md`.
- [ ] US-083 : chaque page → composants `tsf:*` mobilisables ; lister les *gaps* comme demandes d'évolution du bundle (issues tailsfadmin).
- [ ] US-085 : critères de priorisation MoSCoW explicites (valeur métier, fréquence d'usage, effort de reskin) ; sortie = liste ordonnée d'US de refonte.
- [ ] Livrables rangés dans `project-management/architecture/design-canvas/` et/ou `project-management/` (documents versionnés).

## Definition of Done (rappel — adapté sprint de conception)
- [ ] Livrables documentaires revus et **validés par le PO**.
- [ ] Cohérence avec le socle tailsfadmin en place (composants réels, pas hypothétiques).
- [ ] *Gaps* de composants formalisés (backlog bundle) le cas échéant.
- [ ] Pas de régression : sprint sans code produit → `make ci` reste vert sur `main`.

## Risques identifiés
| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| US-082/084 bloqués par l'accès `hotones` | Élevée | Moyen | Sortis du sprint ; le sprint reste livrable (analyse/cartographie) sans eux |
| Conception déconnectée de l'implémentable | Moyenne | Moyen | US-083 ancre chaque page sur des composants tailsfadmin réels + *gaps* explicites |
| Sprint « documentaire » difficile à borner | Moyenne | Faible | Formats de livrables fixés en DoR (tableaux, diagrammes Mermaid) |

## Cérémonies
- **Planning (Part 1 & 2)** : à programmer.
- **Daily** : quotidien.
- **Affinage** : lever la DoR des 4 US avant lancement.
- **Review** : présentation des livrables au PO (validation).
- **Rétrospective** : *Directive Fondamentale* incluse.

---

*Sprint créé le 2026-09-10. Décomposition détaillée des tâches à générer via `/project:decompose-tasks 019`.*
