# Sprint Review — Sprint 5 : Complétude et clôture du cycle temps

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Base git | `feature/sprint-5-planning` (Sprints 0-4 mergés, PR #6 en prod) |
| Animateur | Scrum Master |
| Contexte | Développement piloté IA — **phase de conception UX/UI en amont** (action rétro S4) + revues croisées `security-auditor` / `symfony-reviewer` / `accessibility-expert` |

## Sprint Goal

> « Le cycle de saisie du temps est **complet et clôturable** : chaque collaborateur (web et mobile)
> voit son activité et sa complétude, est relancé en cas de retard, déclare ses absences, et une
> **période close verrouille** toute modification avec traçabilité. »

**Atteint : ✅ OUI**

Justification : EPIC-003 est **achevé**. Par-dessus la saisie/validation/valorisation, le sprint ajoute
la couche pilotage + clôture : complétude (US-058), relances bornées (US-056), absences (US-054),
synthèse (US-059), saisie mobile (US-052) et **clôture de période par tenant** (US-057) qui **lève le
stub global** d'US-060 (action rétro S4). Le verrou `423` s'appuie désormais sur une clôture persistée
et tracée.

## User Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| US-057 | Clôture de période et traçabilité des modifications | 5 | ✅ | ✅ Livré |
| US-054 | Déclaration, validation et compteurs d'absences | 5 | ✅ | ✅ Livré |
| US-058 | Tableau de bord de complétude de saisie | 3 | ✅ | ✅ Livré |
| US-056 | Relances automatiques de retard de saisie | 3 | ✅ | ✅ Livré |
| US-052 | Saisie quotidienne sur mobile | 3 | ✅ | ✅ Livré |
| US-059 | Synthèse d'activité et planning depuis l'écran de saisie | 3 | ✅ | ✅ Livré |

**Livré : 22/22 points (100 %)**

### Enablers techniques (hors points)

| ID | Titre | Statut |
|----|-------|--------|
| T-TECH-03 | Hardening `sprintf`→`set_config` (paramètre lié, 3 sites) — action rétro S4 | ✅ Livré |
| T-TECH-04 | Seed de démo EPIC-003 (`app:demo:seed`) | ✅ Livré |

## User Stories non terminées

Aucune. Le périmètre engagé (44 tâches) est intégralement livré.

## Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés / livrés | 22 / 22 | 100 % |
| Vélocité | 22 | ➡️ stable (S1=29, S2=20, S3=23, S4=21) — dans la cible 20-40 |
| Tests (suite) | 372 | ↗️ (257 → 372, +115 sur le sprint) |
| PHPStan (max) / Deptrac | 0 / 0 | ➡️ stable |
| Migrations RLS ajoutées | 4 (période, absences ×2, relances) | — |
| Vulnérabilité détectée & corrigée en revue | 1 (plancher anti-spam par jour ouvré, US-056) | — |
| Écrans précédés d'une conception UX/UI | 3/3 (`/relances`, `/saisie/jour`, drawer synthèse) | ✅ nouveau |

## Démonstration (scénarios de bout en bout)

```gherkin
# US-057 — clôture & verrou
Given une période comptable ouverte avec des imputations validées
When un administrateur clôture la période (confirmation + CSRF)
Then toute modification d'imputation de la période renvoie 423 (garde + trigger DB)
  And une réouverture formelle (fenêtre 48 h, 4-eyes) est requise pour rééditer

# US-054 — absences
Given un collaborateur déclare une absence (maille demi-journée)
When son manager la valide (auto-décision interdite)
Then les compteurs sont mis à jour, la production est bloquée ces jours-là (422)
  And aucune donnée de santé n'est stockée (gate RGPD/HAB-3)

# US-058 / US-056 — complétude & relances
Given des semaines incomplètes au-delà de l'échéance J+2
When le cron app:reminders:run s'exécute un jour ouvré
Then au plus UNE relance par collaborateur/jour ouvré est émise (dette la plus ancienne)
  And un collaborateur en opt-out n'est jamais relancé (droit non forçable par l'admin)
  And l'escalade N+1 s'active à la 3ᵉ relance

# US-052 — saisie mobile
Given une collaboratrice en déplacement sur /saisie/jour
When elle saisit hors ligne puis retrouve le réseau
Then ses saisies sont conservées localement puis resynchronisées en un tap (aucune perte)

# US-059 — synthèse
Given une collaboratrice sur l'écran de saisie
When elle ouvre « Ma synthèse » (1 clic)
Then un panneau en lecture seule montre son occupation et sa répartition projet/type
  And une demande visant un autre user_id est refusée (403)
```

Ordre de démo suggéré : (1) clôture + verrou 423, (2) absence → blocage saisie, (3) complétude puis
relance cron (plancher + opt-out + escalade), (4) saisie mobile offline/resync, (5) drawer synthèse.

## Feedback (revues croisées)

### Positif
- **Phase de conception UX/UI systématique** avant chaque écran (ux-ergonome + ui-designer +
  accessibility-expert), puis revue WCAG 2.2 AA — action rétro S4 pleinement appliquée.
- **Moteur de relances pur et déterministe** (horloge injectée) : borne, escalade et plancher testés
  hors temps réel.
- **RLS-via-consume** appliquée d'emblée aux nouveaux handlers async (`ReminderWorkerRlsTest`) — action
  rétro S4 (garde de parité) respectée.
- **Esprit critique en revue** : findings acceptés (plancher par jour ouvré) et **rejetés avec
  justification** (hashage `userId`, `<dialog>` non-modal) au regard de la cohérence codebase.

### À améliorer (détecté en revue, traité)
- **[Medium] Plancher anti-spam par (collaborateur, semaine)** au lieu de par jour ouvré → un
  collaborateur cumulant plusieurs semaines en retard pouvait recevoir plusieurs relances le même jour.
  **Corrigé** (`capOnePerUserPerDay` + `sentOnDay`), tests ajoutés.
- **[Critique] Fuite d'écouteurs tactiles** (Stimulus mobile) au retour Turbo. **Corrigé** (retrait en
  `disconnect()`).
- **[Majeur] Double requête jour/veille** (vue mobile). **Corrigé** (une requête plage découpée).

### Suivi (non bloquant — dette assumée)
- **Livraison effective des notifications** (absences + relances) : adaptateur `logging` en place,
  envoi in-app/email à brancher.
- **Planning à venir** (US-059) dégradé tant qu'**US-037** (affectation) n'est pas livrée.
- **Taxonomie d'activité** réduite à production/absence (pas de champ « type » sur les projets).
- **Journal immuable avant/après** des modifications post-réouverture (EF-TMP-23) — US-057.

## Impact sur le Backlog

| Action | US | Description |
|--------|-----|-------------|
| Dépendance à planifier | US-037 | Affectation → active le planning à venir de la synthèse |
| Dette de suivi | — | Handler de livraison notifications (in-app/email) |
| Extension à arbitrer | — | Taxonomie d'activité (type de projet) pour la synthèse |

## Prochaines étapes

1. Rétrospective du sprint (`sprint-retro.md`).
2. Push de la branche + PR #7 « ready » → merge vers `main`.
3. Planifier le Sprint 6 : prioriser la **livraison effective des notifications** et **US-037**
   (planning), en conservant la phase de conception UX/UI en amont.
