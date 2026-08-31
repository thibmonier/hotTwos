# EPIC-008 : RH & Cycle de Vie Collaborateur (Module RH)

## Métadonnées
- **ID**: EPIC-008
- **Statut**: 🔴 To Do
- **Priorité**: Should Have (MoSCoW)
- **Module**: RH
- **Lot**: 4
- **Exigences fonctionnelles**: 27 EF
- **MMF**: À affiner — lot 4 (27 EF). La RH suit les entretiens, compétences et mobilités sans relancer 40 personnes, en respectant les habilitations strictes (`HAB-2`/`HAB-3`).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de gestion du cycle de vie collaborateur : onboarding/offboarding, suivi des entretiens (annuel, professionnel), gestion des compétences, absences et congés, mobilité interne. Exclut la paie et les fonctions SIRH complètes (`ARB-1`).

Ce module traite des données sensibles soumises à des contraintes réglementaires fortes :
- **AI Act** (`CTR-3`, `ARB-14`) : **qualification juridique externe obligatoire avant conception** — tout traitement automatisé de décision RH est en catégorie à risque élevé.
- **RGPD** (`ENF-RGPD-5`) : **AIPD obligatoire** avant toute fonction IA ou de scoring sur données collaborateurs.

---

## Objectifs Business

- `OBJ-5` — Anticiper le recrutement : compétences et mobilités internes visibles avant d'ouvrir un poste.
- `OBJ-7` — Adoption RH : réduire la charge administrative en éliminant les relances manuelles.
- `HAB-2` — Entretiens : visibles uniquement par l'intéressé, son manager direct et la RH.
- `HAB-3` — Données de santé : type d'arrêt + dates uniquement, jamais de motif médical.

---

## User Stories

À affiner — lot 4 (27 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 4 | - | - | - |

---

## Critères de Succès

### Critères bloquants (prérequis réglementaires)
- [ ] `CTR-3` / `ARB-14` — **Qualification juridique AI Act obtenue** avant démarrage de la conception du module.
- [ ] `ENF-RGPD-5` — **AIPD réalisée et validée** avant tout développement de fonction IA ou scoring RH.
- [ ] `HAB-2` — Cloisonnement des entretiens vérifié par test d'intrusion.
- [ ] `HAB-3` — Aucun motif médical stocké ; type d'arrêt + dates uniquement.

### Critères fonctionnels
- [ ] Circuit d'entretien : planification, saisie, validation, archivage — sans relance manuelle.
- [ ] Compétences collaborateur : déclaratives + validées par manager, liées aux profils REF.
- [ ] Absences : saisie, validation, répercussion sur la capacité EPIC-004.
- [ ] Export éléments variables de paie (`EF-RH-18`) vers SIRH tiers — hors calcul.

### Critères non-fonctionnels
- [ ] `HAB-6` — Toute lecture d'une donnée RH sensible est tracée (piste d'audit).
- [ ] `ENF-RGPD-2` — Purge/anonymisation vérifiable techniquement à échéance de conservation.
- [ ] `ENF-RGPD-3` — Droits des personnes exercés en < 5 jours ouvrés.

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Profils collaborateurs (base commune).
- `CTR-3`/`ARB-14` — Qualification AI Act (externe, hors développement).
- `ENF-RGPD-5` — AIPD RH validée.
- EPIC-003 — Absences répercutées depuis le module temps (lots 1 validé).
- EPIC-004 — Capacité impactée par les absences.

### Dépendants
- EPIC-009 (Recrutement) — Le besoin détecté ici (mobilité interne non couverte) peut déclencher une ouverture de poste.
