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
| Statut | 🟢 Affiné (2026-09-10) — DoR levée sur les 4 US ; prêt à lancer (US-080 en 1er) |

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
| 🔴 Must | US-080 | Référentiel exhaustif des pages cibles | 5 | EPIC-013 | ✅ levée |
| 🔴 Must | US-081 | Cartographie des parcours par persona (P1–P6) | 5 | EPIC-013 | ✅ levée |
| 🟡 Should | US-083 | Mapping pages ↔ composants tailsfadmin (+ gaps) | 3 | EPIC-013 | ✅ levée |
| 🟡 Should | US-085 | Backlog de refonte/reskin priorisé (MoSCoW) | 3 | EPIC-013 | ✅ levée |

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

## Definition of Ready (avant lancement) — ✅ LEVÉE en affinage (2026-09-10)
- [x] US-080 : périmètre = routes applicatives actuelles (`debug:router` sur `main`) + écrans cibles manquants ; taxonomie de modules figée ; format tableau arrêté. **Démarrable immédiatement.**
- [x] US-081 : 1 diagramme Mermaid par persona P1–P6 (+1 variante max) ; JTBD dérivés de `personas.md`. **Entrée : US-080 validée PO.**
- [x] US-083 : chaque page → composants `<twig:tsf:…>` **réels confirmés** (v1.4.2) ; *gaps* formalisés en demandes d'évolution bundle. **Entrée : US-080 + US-081 validées.**
- [x] US-085 : critères MoSCoW explicites et pondérés (valeur métier > fréquence P1 > effort > gaps). **Entrée : US-083 validée.**
- [x] Livrables versionnés dans `project-management/architecture/` (`page-inventory.md`, `parcours-personas.md`, `page-component-mapping.md`, `backlog-reskin-priorise.md`) ; maquettes (US-084) → `design-canvas/` quand débloqué.

> **Séquencement** : DoR levée sur les 4 US, mais l'exécution reste **strictement ordonnée** (US-080 → US-081 → US-083 → US-085) — chaque story a pour condition d'entrée la validation PO du livrable amont. Les mentions ⏳ des fichiers stories signalent ces conditions à confirmer au démarrage effectif de chaque story.

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
- **Affinage** : ✅ réalisé le 2026-09-10 — DoR levée sur les 4 US (décisions arrêtées, conditions d'entrée explicites, composants confirmés).
- **Review** : présentation des livrables au PO (validation).
- **Rétrospective** : *Directive Fondamentale* incluse.

---

*Sprint créé le 2026-09-10. Décomposition détaillée des tâches à générer via `/project:decompose-tasks 019`.*
