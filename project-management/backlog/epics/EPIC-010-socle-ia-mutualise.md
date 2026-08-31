# EPIC-010 : Socle IA Mutualisé (Transverse)

## Métadonnées
- **ID**: EPIC-010
- **Statut**: 🔴 To Do
- **Priorité**: Must Have / Should Have (MoSCoW selon brique)
- **Module**: Transverse
- **Lot**: 1 à 3 (progressif)
- **Références**: `ARC-5..13`, `ADR-10`, `ENF-IA-1..9`, `HAB-5`
- **MMF**: Couche d'abstraction unique entre le métier et les modèles IA, avec filtrage des données à la source selon les habilitations (`ARC-9`/`HAB-5`), citation des sources (`ARC-10`), séparation calcul/texte (`ARC-11`), et commutateur par tenant (`ARC-13`). La première brique concrète est le pré-remplissage de saisie de temps (`EF-TMP-9`, US-053, EPIC-003).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Couche transverse qui fournit à **tous** les modules une interface IA normalisée, explicable, habilitable et commutable. Aucun module métier n'appelle un modèle IA directement (`ARC-5`).

Deux ruptures produit issues du positionnement (`research-summary.md § 2`) :
1. **IA comme réducteur de friction de saisie** (priorité absolue) — le pré-remplissage TMP est la première brique.
2. **IA comme aide à la décision sous condition d'explicabilité** — toute suggestion expose les données qui la fondent (`ENF-IA-1`, bloquant MEP).

**`ARC-80`** : un tenant sans clé IA n'a aucune fonction IA, et le produit reste **pleinement utilisable** sans IA. La dégradation gracieuse est structurelle, non optionnelle.

---

## Objectifs Business

- `OBJ-1` — Pré-remplissage temps : réduire la friction de saisie pour atteindre le seuil ≤ 2 min.
- `OBJ-7` — Adoption : l'IA réduit la charge de l'utilisateur, elle ne la remplace pas.
- `ENF-IA-1` — Explicabilité : aucune MEP sans dispositif de citation des sources (bloquant).
- `ENF-IA-9` — Désactivation par tenant : produit pleinement fonctionnel sans IA.
- `CTR-4` — Coût d'inférence compatible avec un SaaS par abonnement (budget par tenant + dégradation).
- `CTR-5` — Souveraineté : hébergement + inférence dans l'UE (`ARB-3`).

---

## User Stories

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-053 | Pré-remplissage IA de la grille de saisie de temps (brique 1) | 🔴 To Do | 8 | 5 |
| - | Briques lot 2 et lot 3 — À affiner lors du Sprint Planning correspondant | - | - | - |

---

## Critères de Succès

### Critères bloquants (aucune MEP de fonction IA sans)
- [ ] `ENF-SEC-6` — Toute fonction IA accède aux données via le même contrôle d'habilitation que l'utilisateur (`HAB-5`) — test d'intrusion (injection de consigne, extraction par recoupement). **BLOQUANT.**
- [ ] `ENF-IA-1` — Toute suggestion/alerte/synthèse IA cite les enregistrements sources (`ARC-10`). **BLOQUANT.**
- [ ] `ENF-IA-3` — Séparation stricte calcul/rédaction : aucun chiffre issu d'un LLM (`ARC-11`).
- [ ] `ENF-RGPD-5` — AIPD validée pour `EF-TMP-9` (signaux d'activité) avant activation.

### Critères architecturaux
- [ ] `ARC-5` — Aucun appel direct à un modèle IA depuis le code métier ; couche d'abstraction unique.
- [ ] `ARC-9` — Construction du contexte IA avec filtrage habilitations à la source, pas filtre d'affichage.
- [ ] `ARC-13` — Commutateur par tenant : activation/désactivation sans redéploiement.
- [ ] `ARC-80` — Produit pleinement fonctionnel si le tenant n'a pas de clé IA.

### Critères non-fonctionnels
- [ ] `ENF-IA-4` — Traçabilité de chaque appel IA (fonction, utilisateur, périmètre, coût tokens).
- [ ] `ENF-IA-5` — Maîtrise du coût : suivi + plafond par tenant ; dégradation gracieuse en cas de dépassement.
- [ ] `ENF-DISPO-5` — Dégradation d'un service IA ne bloque jamais les fonctions cœur.
- [ ] `CTR-5` — Hébergement + inférence IA dans l'UE (`ENF-RGPD-7`).

---

## Progression

0/1 US lot 1 complétée (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle, notamment RBAC et mode worker.
- `ENF-RGPD-5` — AIPD pour chaque usage IA avant activation.
- `ADR-10` — Clés IA par tenant posées dans le socle EPIC-000.

### Dépendants
- EPIC-003 (`US-053`) — Premier consommateur de la couche IA.
- EPIC-004, 005, 007 (lots 2-3) — Consommateurs ultérieurs selon la feuille de route IA.
- EPIC-008, 009 — Conditionnés à la qualification AI Act (`CTR-3`).
