# Sprint 5 : Complétude et clôture du cycle temps

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 5 |
| Début | 2026-10-13 |
| Fin | 2026-10-24 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (vélocité observée S1=29, S2=20, S3=23, S4=21 → moy. ~23) |
| Base git | `main` (Sprint 4 mergé, PR #6 — valorisation en prod) |
| Branche | `feature/sprint-5-planning` |

## Sprint Goal

> « Le cycle de saisie du temps est **complet et clôturable** : chaque collaborateur (web et mobile)
> voit son activité et sa complétude, est relancé en cas de retard, déclare ses absences, et une
> **période close verrouille** toute modification avec traçabilité. »

Ce sprint **achève EPIC-003 (Temps & activité)** : par-dessus la saisie (US-050/051), la validation
(US-055) et la valorisation (US-060), il ajoute la couche de **pilotage et de clôture**. US-057
**lève le stub de clôture global** introduit en US-060 (action rétro S4) : le verrou `423` de
recalcul s'appuiera alors sur une clôture **par tenant** persistée et tracée.

## Definition of Done (rappel + action rétro S4)

- [ ] Tests unitaires + intégration verts, couverture ≥ 80 % sur le code touché
- [ ] `make ci` vert (PHPStan max, Deptrac 0, cs-fixer, Rector, gitleaks) ; `schema:validate` OK
- [ ] Isolation multi-tenant (filtre ORM + RLS prod) respectée ; nouvelles tables avec policy RLS
- [ ] **[Action rétro S4]** Tout nouveau `#[AsMessageHandler]` écrivant en base a un **test
      d'intrusion RLS via consume** (rôle NOSUPERUSER, pattern `ValuationWorkerRlsTest`)
- [ ] Habilitation vérifiée côté serveur (ARC-19/ARC-106)
- [ ] Traçabilité des opérations sensibles (clôture, réouverture, validation) — HAB-6
- [ ] Documentation mise à jour ; déployable en production (staging à jour, smoke vert)

## Sprint Backlog

| Priorité | ID | Titre | Points | Persona | Statut |
|----------|-----|-------|--------|---------|--------|
| 🔴 Must | US-057 | Clôture de période et traçabilité des modifications | 5 | P2 Marc / ADMIN | 🔵 To Do |
| 🔴 Must | US-054 | Déclaration, validation et compteurs d'absences | 5 | P1 Camille / P2 Marc | 🔵 To Do |
| 🔴 Must | US-058 | Tableau de bord de complétude de saisie | 3 | P2 Marc / P3 Sophie | 🔵 To Do |
| 🟡 Should | US-056 | Relances automatiques de retard de saisie | 3 | P1 Camille / P2 Marc | 🔵 To Do |
| 🟡 Should | US-052 | Saisie quotidienne sur mobile | 3 | P1 Camille | 🔵 To Do |
| 🟢 Could | US-059 | Synthèse d'activité et planning depuis l'écran de saisie | 3 | P1 Camille | 🔵 To Do |

**Total engagé : 22 points** (dans la vélocité cible 20-40).

## Dépendances

| US | Dépend de | Statut |
|----|-----------|--------|
| US-057 | US-055 (validation), US-060 (verrou recompute à raccorder) | ✅ livrés |
| US-054 | US-050/051 (saisie), circuit de validation US-055 | ✅ livrés |
| US-058 | US-050 (imputations), capacité/absences US-054 | ⏳ US-054 en parallèle |
| US-056 | US-058 (complétude) + Messenger async (T-TECH-01) | ✅ / ⏳ |
| US-052 | US-050/051 (saisie web), design mobile | ✅ livrés |
| US-059 | US-050 (imputations) | ✅ livré |

## Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Clôture US-057 : interactions avec le recompute US-060 (verrou 423) | Moyenne | Moyen | Raccorder `PeriodClosureStatus` à la vraie clôture ; tests de non-régression sur le 423 |
| Relances async (US-056) : nouveau handler tenant-aware | Moyenne | Élevé (RLS) | Appliquer l'action rétro : test d'intrusion RLS via consume |
| Absences (US-054) : impact sur la capacité/occupation des indicateurs | Faible | Moyen | VO période partagé, compteurs isolés du calcul de valorisation |
| Mobile (US-052) : parité fonctionnelle vs web | Faible | Faible | Réutiliser l'API de saisie existante, vue responsive |

## Notes

- **Action rétro S4** portée dans ce sprint : lot de hardening `sprintf`→`set_config` (transverse
  socle) à traiter en tech-debt en début de sprint (hors points, avant US-057 qui touche la clôture).
- **US-053 (préremplissage IA)** volontairement **exclue** : dépend du socle IA mutualisé (EPIC-010),
  à planifier ultérieurement.
