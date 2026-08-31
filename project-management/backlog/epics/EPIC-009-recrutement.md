# EPIC-009 : Recrutement (Module REC)

## Métadonnées
- **ID**: EPIC-009
- **Statut**: 🔴 To Do
- **Priorité**: Should Have / Could Have (MoSCoW selon sous-périmètre)
- **Module**: REC
- **Lot**: 4
- **Exigences fonctionnelles**: 22 EF
- **MMF**: À affiner — lot 4 (22 EF). Le délai entre la détection du besoin et l'ouverture de poste est ≤ 15 j (`OBJ-5`), grâce à un pipeline de recrutement structuré et traçable.
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de gestion du pipeline de recrutement : ouverture et qualification des postes, suivi des candidatures, étapes de sélection, intégration onboarding. Pas de portail candidat externe (statut `W`, exclu de la V1 — `ARB-1`).

Pain point n°3 (`research-summary.md`) : le délai d'arrivée d'un recrutement (3-6 mois) est supérieur à l'horizon de visibilité actuel. Ce module ne réduit pas le délai de recrutement — il avance la détection du besoin.

Même prérequis réglementaire que EPIC-008 :
- **AI Act** (`CTR-3`, `ARB-14`) — qualification juridique avant conception (scoring de candidatures = risque élevé).
- **RGPD** (`ENF-RGPD-5`) — AIPD avant toute aide IA au tri ou à l'évaluation.

---

## Objectifs Business

- `OBJ-5` — Délai détection besoin → ouverture de poste ≤ 15 j.
- `OBJ-4` — Anticipation de la capacité : le recrutement est déclenché avant la rupture de charge.
- `OBJ-7` — Adoption RH : pipeline structuré vs suivi par e-mail.

---

## User Stories

À affiner — lot 4 (22 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 4 | - | - | - |

---

## Critères de Succès

### Critères bloquants (prérequis réglementaires)
- [ ] `CTR-3` / `ARB-14` — **Qualification juridique AI Act obtenue** avant démarrage de la conception (scoring candidatures = catégorie à risque élevé).
- [ ] `ENF-RGPD-5` — **AIPD validée** avant toute aide IA au tri ou à l'évaluation.

### Critères fonctionnels
- [ ] `OBJ-5` — Délai moyen détection besoin → ouverture de poste tracé et ≤ 15 j en moyenne sur 3 mois.
- [ ] Pipeline de candidatures : étapes configurables, historique des échanges, statuts traçés.
- [ ] Lien avec EPIC-008 (RH) : recrutement validé déclenche l'onboarding dans le cycle de vie.
- [ ] Export données candidats en autonomie à la clôture (`ENF-RGPD-9`).

### Critères non-fonctionnels
- [ ] `ENF-RGPD-2` — Purge des données candidats non retenus à l'échéance légale, vérifiable techniquement.
- [ ] `HAB-6` — Accès aux dossiers de candidatures tracés (piste d'audit).
- [ ] Aucune décision automatisée sur candidature sans intervention humaine (`ENF-IA-2`).

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-004 (Planification) — Besoin détecté par projection de charge.
- EPIC-008 (RH) — Mobilité interne non couverte → ouverture externe.
- `CTR-3`/`ARB-14` — Qualification AI Act (externe).
- `ENF-RGPD-5` — AIPD REC validée.

### Dépendants
- EPIC-008 (RH) — Candidat recruté → onboarding.
