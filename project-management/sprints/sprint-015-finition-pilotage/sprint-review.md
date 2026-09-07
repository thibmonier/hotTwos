# Sprint Review — Sprint 15 (Finition pilotage projet)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Sprint Goal | « Boucler le pilotage projet (EPIC-002) : l'atterrissage en charge est historisé et sa courbe visible, et le seuil de dérive est paramétrable par type de projet (avec un 2e seuil d'escalade direction). » |
| EPIC | EPIC-002 (Projets & delivery) — **bouclage** |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI.** Les deux dernières capacités *Should* d'EPIC-002 (EF-PRJ-15/16) sont livrées. **EPIC-002 est complet à 100 %.**

- **US-079c (courbe, EF-PRJ-16)** — l'atterrissage en charge est historisé à chaque clôture de période via un **handler séparé** (`ComputeProjectMargins` inchangé, décision rétro S14) et sa courbe est visible sur la fiche projet.
- **US-079b (seuil par type, EF-PRJ-15)** — le seuil de dérive de charge est paramétrable **par type de projet** (forfait/régie), avec un **2e seuil d'escalade direction** ; repli sur les constantes OBJ-2.

## 📦 Livré

| ID | Titre | Points | PR |
|----|-------|--------|-----|
| US-079c | Courbe d'atterrissage historisée (capture légère) | 5 | #82 |
| US-079b | Seuil de dérive charge par type + 2e seuil direction | 5 | #83 |

**Points livrés : 10 / 10 engagés.**

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Tests | 628 → **≈650** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac, cs, rector, gitleaks) |
| Migrations | `charge_landing_snapshot`, `charge_drift_threshold` (+ RLS) |

## 🎬 Démonstration
1. **Courbe d'atterrissage** — fiche projet, onglet « Suivi budgétaire » : évolution du dépassement projeté période par période (table + sparkline), montants coût masqués pour un CP sans coût (HAB-1).
2. **Seuil par type** — `/finance/config-derive-charge` : réglage alerte + escalade par forfait/régie ; la fiche projet applique le seuil du type et affiche l'« Escalade direction » au 2e seuil.

## 💬 Feedback à collecter
1. La valeur par défaut du **2e seuil d'escalade** (25 %) convient-elle, ou doit-elle être ajustée par la direction ?
2. La courbe doit-elle aussi matérialiser le franchissement du seuil d'escalade (2e couleur) ?
3. EPIC-002 étant bouclé, priorité S16 : EPIC-001 (calendrier fériés, compétences, statuts/circuits, onboarding).

## Impact backlog
- **EPIC-002** : EF-PRJ-15/16 livrés → **EPIC-002 complet à 100 %**.
- **EPIC-001** : reste calendrier fériés (EF-REF-6), compétences (EF-REF-10/11), statuts/circuits (EF-REF-24/25), onboarding (EF-REF-29).

## Prochaines étapes
1. Rétrospective S15 (`sprint-retro.md`).
2. Planifier Sprint 16 sur EPIC-001 (`/workflow:start 016`).
