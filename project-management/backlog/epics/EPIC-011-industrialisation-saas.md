# EPIC-011 : Industrialisation SaaS (Transverse)

## Métadonnées
- **ID**: EPIC-011
- **Statut**: 🔴 To Do
- **Priorité**: Should Have (MoSCoW)
- **Module**: Transverse
- **Lot**: 5
- **Références**: `EF-REF-29` étendu, `EF-REF-34`, `ENF-SAAS-1..6`, `CTR-1`, `ARC-3`, `ARC-14`
- **MMF**: À affiner — lot 5. Un nouveau client crée son tenant, le paramètre et commence à l'utiliser en moins de 15 min sans aucune intervention humaine de l'équipe produit (`ENF-SAAS-2` en self-service complet).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module d'industrialisation permettant le passage à un vrai modèle SaaS multi-tenant distribué : onboarding self-service (trial autonome, activation, paramétrage guidé), feature flags par tenant, supervision opérationnelle, et exploitabilité par une équipe de 2-4 personnes sans astreinte 24/7 (`CTR-1`, `ARC-3`).

Ce lot valorise la fondation multi-tenant posée dans EPIC-000 en la transformant en un produit commercialisable sans friction d'activation.

---

## Objectifs Business

- `CTR-1` — Exploitable par 2-4 personnes sans astreinte : chaque ajout d'opération manuelle à l'onboarding est un frein à la scalabilité.
- `ENF-SAAS-2` — Création d'un tenant < 15 min en self-service (opérationnel dès EPIC-000, ici étendu au parcours commercial complet).
- `ENF-SAAS-5` — Supervision : disponibilité, temps de réponse, erreurs, consommation IA par tenant visible par l'équipe produit.
- `OBJ-7` — Adoption : onboarding sans friction = premier signal de valeur pour le nouveau client.

---

## User Stories

À affiner — lot 5

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 5 | - | - | - |

---

## Critères de Succès

### Critères bloquants
- [ ] `ENF-SAAS-2` — Un tenant se crée, se paramètre et devient productif en < 15 min sans intervention infra.
- [ ] `ENF-SAAS-5` — Supervision opérationnelle : dispo, latence, erreurs, conso IA par tenant — visible par l'équipe sans accès base de données.

### Critères fonctionnels
- [ ] Feature flags par tenant : activation/désactivation de fonctionnalités sans redéploiement (`ENF-SAAS-4`).
- [ ] Onboarding guidé : wizard de paramétrage validant la complétude avant mise en service.
- [ ] Export complet des données d'un tenant en autonomie (réversibilité — `ENF-RGPD-9`).
- [ ] `ENF-SEC-8` — Accès éditeur à un tenant : exceptionnel, motivé, tracé, notifié au tenant.

### Critères non-fonctionnels
- [ ] `ARC-3` / `ENF-SAAS-6` — L'architecture n'exige pas d'astreinte 24/7 ; pas de système distribué complexe.
- [ ] `ARC-14` — Minimiser le nombre de technologies à exploiter simultanément.

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle multi-tenant (la fondation technique est déjà là).
- Tous les EPICs lots 1 à 4 en production stable.

### Dépendants
- Aucun EPIC fonctionnel ne dépend de EPIC-011 (dernier lot).
