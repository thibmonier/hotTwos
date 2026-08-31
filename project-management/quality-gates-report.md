# Rapport Quality Gates — HotOnes

> Généré : 2026-08-31 · Mis à jour : 2026-08-31 (après correction du backlog) · Phase workflow : Conception terminée, Implémentation prête

## Résumé des gates

| Gate | Seuil | Score | Statut |
|------|-------|-------|--------|
| PRD | ≥ 80 % | **95 %** | ✅ PASSÉ |
| Tech Spec | ≥ 90 % | **96 %** | ✅ PASSÉ |
| Backlog (INVEST) | 6/6 | **6/6** (36/36 sur les 6 critères) | ✅ PASSÉ |
| Sprint Ready | 100 % | Sprint non composé (~84 pts par défaut) | ❌ NON PRÊT |
| Story DoD | 100 % | 36 US en To Do (0 démarrée) | 📊 N/A |

**Santé globale : 🟢 Bonne** — les 3 gates de contenu (PRD, Tech Spec, Backlog) sont au vert. Reste une seule action avant de coder : composer un Sprint 1 réaliste (`/workflow:start`).

> **Mise à jour backlog (2026-08-31).** Les 22 US en déficit de scénarios d'erreur ont été complétées (ajout de scénarios d'erreur Gherkin + labels Nominal/Alternatif/Erreur), et US-030 labellisée. Vérification déterministe : **36/36 US** satisfont le critère Testable (labels + ≥ 1 nominal, 2 alternatifs, 2 erreurs). Gate Backlog : ⚠️ 5,39/6 → ✅ **6/6**.

---

## Détail par gate

### PRD — 95 % ✅
Points forts : énoncé du problème (5 pain points), 6 personas, OBJ-1..7 mesurables, périmètre + exclusions, 11 risques. 
Faiblesses : pas d'aperçu d'US au format standard dans le PRD (−3) ; hypothèses peu nombreuses (−2) ; **signatures d'approbation vides → reste un draft** (priorité 3).

### Tech Spec — 96 % ✅
Points forts : architecture, composants (src/ Clean/DDD), modèle de données, sécurité (double barrière, filtrage IA), perf (6 seuils), tests (6 niveaux), déploiement (CI 11 étapes). 
Faiblesses : **gestion d'erreurs API** sans section dédiée (format RFC 7807, retry/circuit-breaker sur intégrations externes) (−3) ; contrats API sans exemple inline (−1) ; ARB-25 (prod) ouvert → ADR-17 à fermer au lot 2.

### Backlog INVEST — 6/6 ✅ (après correction)
| Critère | Conformes /36 |
|---|---|
| Independent / Negotiable / Valuable / Estimable / Sized ≤8 | 36/36 ✅ |
| **Testable (labels + 1 nominal + 2 alternatifs + 2 erreurs)** | **36/36** ✅ |

Correction appliquée le 2026-08-31 : 23 US complétées (US-003/004/005, US-010..020, US-031..038, + labellisation d'US-030). Vérification déterministe : les 36 US portent des labels Nominal/Alternatif/Erreur et satisfont ≥ 1N + 2A + 2E.

### Sprint Ready — ❌ NON PRÊT
Aucun sprint composé. La répartition par défaut charge le **Sprint 1 à ~84 pts** (vélocité max 40). À rééquilibrer via `/workflow:start` (Walking Skeleton EPIC-000 + saisie de base ≈ 31-34 pts).

### Story DoD — 📊 N/A
Les 36 US sont en 🔴 To Do. Aucune en développement/review/done : la DoD ne s'évalue pas encore.

---

## Recommandations prioritaires

**Priorité 2 (à corriger avant Sprint Planning)**
1. **Compléter les scénarios d'erreur Gherkin (22 US)** — ajouter 1 scénario d'erreur aux 14 US EPIC-000/001 ; ajouter labels + scénarios d'erreur aux 8 US EPIC-002 (US-031/035/038 en priorité). → fait passer le gate Backlog au vert.
2. **Rééquilibrer les sprints** (`/workflow:start`) — Sprint 1 réaliste ≤ 40 pts.

**Priorité 3 (souhaitable)**
3. **PRD** : faire ratifier (signatures sponsor + resp. technique) ; ajouter un aperçu d'US et compléter les hypothèses (équipe 4,5 ETP, budget d'inférence, fournisseurs IA UE).
4. **Tech Spec** : ajouter une section gestion d'erreurs API (RFC 7807 Problem Details, retry/backoff, circuit-breaker) ; fermer ARB-25 par un ADR-17 au lot 2.

---

## Commandes

- `/gate:validate-backlog` — relancer après correction des scénarios d'erreur
- `/gate:validate-prd` · `/gate:validate-techspec` — relancer après amendements
- `/workflow:start` — composer le Sprint 1 (lève le gate Sprint Ready)
