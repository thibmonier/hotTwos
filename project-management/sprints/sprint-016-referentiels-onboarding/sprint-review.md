# Sprint Review — Sprint 16 (Référentiels de paramétrage & mise en route)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Sprint Goal | « Compléter les référentiels (calendrier/jours ouvrés & fériés, compétences), rendre un tenant opérationnel en < 15 min (onboarding) et tracer le paramétrage. » |
| EPIC | EPIC-001 (Référentiels & paramétrage) |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI. 16/16 pts livrés.** Les 4 stories engagées sont mergées ; il reste
une seule capacité EPIC-001 (US-017 circuits de validation).

- **US-012 (fériés, EF-REF-6)** — référentiel de jours fériés tenant + **`WorkingDaysCalculator` unifié** (refactor DRY des 4 calculs inline) ; les fériés réduisent désormais capacité/occupation/complétude.
- **US-013 (compétences, EF-REF-10/11)** — référentiel de compétences (catégories, échelle paramétrable, association collaborateur).
- **US-020 (audit, EF-REF-33)** — journal d'audit **append-only** (INV-7) + lecture gated (`VIEW_AUDIT_LOG`, HAB-6), instrumenté sur seuils/fériés/compétences.
- **US-019 (onboarding, EF-REF-29)** — `tenant:init` idempotent (défauts) + checklist de mise en route.

## 📦 Livré

| ID | Titre | Points | PR |
|----|-------|--------|-----|
| US-012 | Jours fériés & calcul unifié des jours ouvrés | 5 | #89 |
| US-013 | Référentiel de compétences & niveaux | 3 | #90 |
| US-020 | Journal d'audit du paramétrage | 3 | #91 |
| US-019 | Onboarding tenant (défauts + checklist) | 5 | #92 |

**Points livrés : 16 / 16 engagés** (capacité 22).

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Tests | 645 → **672** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac, cs, rector, gitleaks) |
| Migrations | `holiday`, `skill*` (3), `config_audit_entry` (+ RLS) |

## 🎬 Démonstration
1. **Jours fériés** — `/parametrage/jours-feries` : ajout/suppression ; impact immédiat sur l'occupation.
2. **Compétences** — `/parametrage/competences` : catégories, échelle, association.
3. **Audit** — `/parametrage/audit` : modifier un seuil/férié/compétence → entrée tracée (lecture réservée).
4. **Onboarding** — `tenant:init <id>` → tenant opérationnel ; checklist de mise en route sur le dashboard.

## 💬 Feedback à collecter
1. Faut-il inclure les **fériés mobiles** (Pâques/Ascension/Pentecôte) dans les défauts d'onboarding ?
2. La **période de fermeture entreprise** (EF-REF-9) et les **calendriers différenciés** (EF-REF-7) : priorité S17 ?
3. Étendre l'**instrumentation d'audit** aux autres paramètres (org, profils/taux) ?

## Impact backlog
- **EPIC-001** : EF-REF-6/10/11/29/33 livrés. **Reste US-017** (statuts & circuits de validation, EF-REF-24/25) + raffinements reportés (EF-REF-7 calendriers différenciés, EF-REF-9 fermeture).

## Prochaines étapes
1. Rétrospective S16 (`sprint-retro.md`).
2. Sprint 17 : US-017 + éventuels raffinements EF-REF-7/9.
