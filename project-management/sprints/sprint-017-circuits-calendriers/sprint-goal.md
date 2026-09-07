# Sprint 17 : Circuits de validation, calendriers différenciés & fermeture entreprise (EPIC-001)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 17 |
| Début | 2027-04-01 *(provisoire)* |
| Fin | 2027-04-14 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev ; vélocité de référence 22) |
| Base git | `main` (après clôture S16, PR #93) |
| EPIC | EPIC-001 (Référentiels & paramétrage) — **bouclage** |
| Statut | 🟡 En préparation — 3 US à affiner en Ready (DoR + Gherkin) avant dev |

## Sprint Goal

> « **Achever EPIC-001** : rendre les **circuits de validation paramétrables**, gérer des **calendriers
> de travail différenciés** (temps partiel, par entité) et des **périodes de fermeture entreprise** —
> pour un paramétrage complet et réaliste des règles de travail du tenant. »

**Positionnement** : dernières capacités structurantes d'EPIC-001. À l'issue, EPIC-001 est complet (les
raffinements Review — fériés mobiles à l'onboarding, extension de l'audit — sont planifiés en S18).

## Sprint Backlog

| Priorité | ID | Titre | Points | EF-REF | DoR |
|----------|-----|-------|--------|--------|-----|
| 🔴 Must | US-017 | Statuts & circuits de validation paramétrables | 8 | 24/25 | ⏳ à affiner |
| 🔴 Must | US-021 | Calendriers de travail différenciés (temps partiel / entité) | 8 | 7 | ⏳ à affiner (à créer) |
| 🔴 Must | US-022 | Périodes de fermeture entreprise | 5 | 9 | ⏳ à affiner (à créer) |

**Engagé : ~21 pts** (capacité ~22 — sprint plein). Décision PO : embarquer les 3 capacités restantes
d'EPIC-001. **Risque assumé** : EF-REF-7 (US-021) est la plus invasive (touche `WorkingDaysCalculator`
et l'occupation) et non affinée → affinage soigné requis.

## Ordre d'exécution (dépendances)
1. **US-022 Fermeture entreprise** (EF-REF-9) — s'appuie directement sur le référentiel de fériés livré
   en S16 (`Holiday` / `WorkingDaysCalculator`) ; la plus sûre, à faire tôt.
2. **US-021 Calendriers différenciés** (EF-REF-7) — la plus invasive : introduit une résolution
   collaborateur > entité > tenant et impacte le calcul des jours ouvrés/capacité. Affinage prioritaire.
3. **US-017 Circuits de validation** (EF-REF-24/25) — indépendante des deux précédentes.

## Definition of Ready (avant `/sprint:dev`)
- [ ] Description INVEST + Gherkin (≥ 1 nominal + 2 alternatifs + 2 erreurs)
- [ ] Estimation confirmée ; dépendances & impact multi-tenant/RLS explicités
- [ ] US-021 : impact sur `WorkingDaysCalculator`/occupation cadré ; pas de re-spécification des absences (US-054) ni des fériés (US-012)
- [ ] US-022 : réutilise le référentiel de fériés (une fermeture = plage de jours non ouvrés)

## Definition of Done (rappel projet)
- [ ] `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %
- [ ] Migration + RLS pour toute nouvelle entité ; UI ⇏ Infra (Deptrac) ; TDD
- [ ] Nouvelles tables ajoutées aux SchemaTool des tests concernés (piège récurrent)
- [ ] 1 PR / story, merge squash après CI verte

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| EF-REF-7 invasif (jours ouvrés par collaborateur) casse occupation/complétude | Élevée | Élevé | Étendre `WorkingDaysCalculator` par surcharge de calendrier ; s'appuyer sur les tests existants ; affinage détaillé |
| Sprint plein (21/22) + 2 US non affinées | Moyenne | Moyen | Affinage en préambule ; US-022 (sûre) livrée tôt ; US-021 découpable si dérapage |
| Cascade SchemaTool (nouveau read partagé) | Élevée | Moyen | Recenser les tests impactés au design (action rétro S16) |

## Cérémonies (solo dev = jalons documentaires)
Planning P1 = ce document (validé PO) · P2 = `/project:decompose-tasks 017` · Review/Rétro en clôture.

## Notes
- **Reporté S18** (raffinements Review approuvés) : fériés **mobiles** (Pâques/Ascension/Pentecôte) dans
  les défauts d'onboarding (US-019) ; **extension de l'audit** (US-020) aux paramètres org & profils/taux.
- Dette reconduite : MAILER staging.
- Prochaine action : **affinage des 3 US en Ready** (créer US-021/US-022) puis `/project:decompose-tasks 017`.
