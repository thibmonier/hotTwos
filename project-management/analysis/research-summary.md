# Synthèse de recherche — Phase d'analyse

**Projet :** HotOnes — refonte de l'ERP de gestion d'agence digitale / ESN
**Phase :** 1/4 — Analyse (workflow Enterprise)
**Date :** 2026-08-31
**Source de vérité :** `project-management/cdc/` (ce document en est une synthèse, il ne s'y substitue pas)

---

## 1. Problème résolu

Une agence digitale ou ESN (10 à 150 personnes) pilote trois variables interdépendantes — **charge vendue, capacité disponible, marge réelle** — avec des outils séparés (CRM, tableur de staffing, saisie de temps, comptabilité) réconciliés manuellement une fois par mois, avec ~3 semaines de retard.

Cinq pain points récurrents (cf. `cdc/01`) :

1. **Dérive projet détectée trop tard** — le dépassement apparaît quand 60-80 % du budget est déjà consommé.
2. **Staffing réactif** — affectations à la semaine, sans visibilité sur le pipeline pondéré.
3. **Recrutement constaté, non anticipé** — délai d'arrivée (3-6 mois) > horizon de visibilité.
4. **Suivi RH en coût de friction** — charge administrative non réinjectée dans la décision.
5. **Saisie de temps mal faite car inutile à celui qui la fait** — point de défaillance unique. Sans temps fiable, ni marge ni capacité calculables. **Enjeu d'adoption avant d'être un enjeu fonctionnel.**

## 2. Positionnement produit

ERP du cycle de vie projet, trois promesses : **Voir** (performance en continu), **Décider** (staffing sur capacité + pipeline consolidés), **Anticiper** (besoins RH/recrutement sur projection de charge).

Deux ruptures :
- **IA comme réducteur de friction de saisie** (priorité absolue), avant les usages IA « démonstratifs ».
- **IA comme aide à la décision sous condition d'explicabilité** — toute suggestion expose les données qui la fondent (`ENF-IA-1`).

Exclusions structurantes (`ARB-1`) : pas de gestion de tâches (interface Jira/Linear), pas de SIRH complet ni de paie, pas de comptabilité (interfaces uniquement). Détail du périmètre inclus/exclu : `constraints.md` § 5.

## 3. Objectifs et cibles de succès (OBJ-*)

| Réf | Objectif | Indicateur | Cible à 12 mois |
|---|---|---|---|
| `OBJ-1` | Fiabiliser la donnée de temps | Saisie complète à J+2 | ≥ 90 % |
| `OBJ-2` | Détecter la dérive tôt | Dépassements > 10 % détectés avant 50 % conso. | ≥ 75 % |
| `OBJ-3` | Réduire le coût du suivi | Temps hebdo CP au reporting | −40 % |
| `OBJ-4` | Améliorer l'occupation | Taux d'occupation facturable | +5 pts (sans surcharge) |
| `OBJ-5` | Anticiper le recrutement | Délai détection besoin → ouverture poste | ≤ 15 j |
| `OBJ-6` | Fiabiliser la prévision | Écart marge mi-projet vs clôture | ≤ 5 pts |
| `OBJ-7` | Obtenir l'adhésion | Utilisateurs actifs hebdo / déclarés | ≥ 85 % |

> **`ARB-2` — action immédiate, hors développement.** Les situations de référence de `OBJ-1` à `OBJ-7` sont **inconnues à ce jour**. Elles doivent être mesurées sur l'organisation pilote **avant** le lot 1 (`AUD-3`, relevé sur 4 semaines) — sinon le ROI reste déclaratif. `OBJ-7` (≥ 85 % actifs hebdo) est le signal le plus discriminant : un produit utilisé produit de la donnée fiable.

## 4. Scénarios de refonte (cdc/07)

| Scénario | Description | Score /155 |
|---|---|---|
| **A** — Modernisation progressive | Conserver le socle MVP, refondre module par module | 89 |
| **B** — Reconstruction complète (greenfield) | Repartir de zéro, MVP = spécification vivante | 112 |
| **C** — Reconstruction du socle, reprise sélective | Socle neuf conçu multi-tenant + invariants ; reprise du MVP au cas par cas, sur décision tracée | **131** |

### Recommandation AMOA : **Scénario C**

1. **Contre A** — le passage mono-organisation → SaaS multi-tenant n'est pas une évolution mais un **changement de nature** ; les invariants `INV-2`/`INV-3` ne se rétro-adaptent pas sans réécrire le cœur.
2. **Contre B** — l'existant contient l'essentiel de la valeur réalisée (règles métier, arbitrages, erreurs déjà corrigées) ; les jeter est un gaspillage.
3. **Pour C** — sépare deux questions confondues : *sur quoi construit-on* (socle neuf, non négociable) et *que réutilise-t-on* (acquis, au cas par cas, décision tracée `CDR-2`).

**Ce qui invaliderait la recommandation** (→ à établir par `AUD-1`/`AUD-2`) : MVP disposant déjà d'un vrai multi-tenant + historisation à date d'effet ; MVP réellement en production avec données vivantes (`HYP-1` fausse) ; couverture réelle du MVP > 50 % des exigences `M` avec qualité satisfaisante.

## 5. Conditions de réussite (CDR-*)

- `CDR-1` — Invariants `INV-1..8` posés dans le modèle du lot 1 et non rediscutés ensuite.
- `CDR-2` — Toute reprise de code MVP = décision tracée, motivée, prise par le responsable technique. Défaut = ne pas reprendre.
- `CDR-3` — Le lot 1 livre de la valeur en production sur une organisation pilote en **4-5 mois**. Pas de tunnel.
- `CDR-4` — Le MVP est **arrêté** à la mise en service du lot 1 sur le périmètre concerné (cf. `RSQ-9`).
- `CDR-5` — `AUD-1`/`AUD-2` réalisés avant le démarrage ; peuvent remettre en cause la recommandation.

## 6. Prérequis d'audit avant lot 1 (AUD-*) — livrables de cette phase

> **Ne pas engager le lot 1 avant `AUD-1` et `AUD-2`.** Une semaine d'audit évite une décision d'architecture qui coûte six mois.

| Réf | Objet | Méthode | Charge |
|---|---|---|---|
| `AUD-1` | Audit technique de l'existant (couverture, dette, présence multi-tenant, tests, obsolescence deps) | Revue de code/archi par un tiers ou le futur resp. technique | 3-5 j |
| `AUD-2` | Cartographie fonctionnelle réelle du MVP vs exigences `M` du CDC (% couvert, qualité) | Confrontation module par module | 2-3 j |
| `AUD-3` | Mesure des situations de référence `OBJ-1..7` sur l'organisation pilote | Relevé sur 4 semaines | 2 j étalés |

## 7. Parties prenantes et critères de rejet

| Population | Attente | Rejette si… |
|---|---|---|
| Direction / CODIR | Vue consolidée marge, capacité, pipeline | Chiffres inexplicables aux commissaires aux comptes |
| Chef de projet | Voir la dérive avant qu'elle soit irrattrapable | Doit ressaisir ce qui est déjà dans Jira |
| Collaborateur | Saisie < 2 min | Saisie ressentie comme du flicage sans contrepartie |
| Resource manager | Arbitrer sans tableur | Affectations absurdes sans explication |
| RH | Suivre entretiens/compétences sans relancer 40 personnes | Module RH dupliquant le SIRH |
| Commerce | Savoir si on peut s'engager sur une date | Capacité affichée ne reflétant pas la réalité |

Personas détaillés (P1-P6) et matrice d'habilitation : `cdc/02` + `constraints.md` § 6.

## 8. Décision de cadrage retenue

**`ARB-20` tranché le 2026-08-31 : périmètre complet (équipe cible ~4,5 ETP supposée constituée), 6 lots / 248 EF.**
L'écart avec `HYP-15` (1 personne au démarrage) reste un risque projet actif à surveiller — voir `risks-opportunities.md` (`RSQ-3`, `RSQ-9`, `RSQ-17`, `RSQ-20`) et l'analyse `cdc/12 § 0`.

---

## Volume analysé

- **248 exigences fonctionnelles** (9 modules), **91 exigences non-fonctionnelles**, **16 ADR**, **8 invariants**, **6 principes d'intégration** + 12 systèmes cibles.
- Horizon **18-22 mois**, 6 lots, chiffrage indicatif **~1 075 j·h** (fourchette 840-1 430, hors design/UX +15-20 %).

**Documents liés :** `constraints.md`, `risks-opportunities.md`, `technical-options.md`.
