# Module Absences (US-054)

Déclaration, validation et compteurs d'absences, avec **minimisation des données de santé**
(HAB-3, RGPD art. 9) et **blocage de l'imputation de production** sur une absence validée
(`RG-TMP-3`). Traçabilité `EF-TMP-14/15/16`.

## Flux

```
Collaborateur                        Manager (VALIDATE_ABSENCE)
   │ DeclareAbsence (self-service)        │ DecideAbsence
   ▼                                      ▼
 AbsenceRequest (pending) ──► AbsenceDeclared ──(async)──► notif manager
   │                                      │
   │                          approve/reject → AbsenceDecided ──(async)──► notif demandeur
   ▼
 Compteurs (AbsenceBalance)          RecordTimeEntry ─► 422 si jour couvert par une absence validée
 acquis/pris/attente/solde/projeté        (RG-TMP-3, contrôle serveur)
```

## Modèle de domaine (`src/Domain/Absence/`)

| Élément | Rôle |
|---------|------|
| `AbsenceType` | Type normalisé par tenant (« Congés payés », « Arrêt maladie »…) — **jamais** de motif médical (HAB-3). |
| `AbsenceRequest` | Demande : type, dates, **maille demi-journée** (`startsMorning`/`endsAfternoon`), statut, commentaire libre optionnel. `days()` (0,5/demi-journée), `coversDay()`, `validate()`/`reject()`. |
| `AbsenceStatus` | `pending` / `validated` / `rejected`. |
| `AbsenceCounters` | VO : `solde` = acquis − pris ; `projeté` = solde − en attente. |
| `AbsenceException` | Erreur métier → 422. |

Toutes les entités sont `TenantOwned`. **Aucune donnée de santé** n'est portée (gate de conformité —
voir Tests).

## Règles métier (couche Application)

- **`DeclareAbsence`** (CA-1) : self-service (l'acteur est le collaborateur — pas de permission) ;
  valide le type et les dates ; crée une demande `pending` ; notifie le manager
  (`AbsenceDeclared`, async) ; trace `absence_declaree`.
- **`DecideAbsence`** (CA-1/CA-5) : habilitation `VALIDATE_ABSENCE` (403) ; valide/refuse (motif
  obligatoire → 422) une demande **en attente** (déjà traitée → 422) ; notifie le demandeur
  (`AbsenceDecided`, async) ; trace.
- **`AbsenceBalance`** (CA-3, EF-TMP-16) : compteurs — `pris` = jours des validées, `en attente` =
  jours des `pending`, refusées ignorées ; droit `acquis` paramétré par tenant
  (`absence.acquired_days`, accrual annuel fixe **simplifié**).
- **Blocage RG-TMP-3** : `RecordTimeEntry` refuse (422) toute saisie de production sur un jour
  couvert par une absence **validée** — contrôle serveur, pas seulement UI.

## API & Web

| Opération | Effet |
|-----------|-------|
| `POST /api/absences` | Déclaration (201). |
| `GET /api/absences` | Liste des absences du collaborateur (périmètre soi-même). |
| `POST /api/absences/{id}/decision` | Valider/refuser (`VALIDATE_ABSENCE` → 403). |
| `GET /api/absences/balance` | Compteurs du collaborateur. |
| `GET /absences` | Écran « Mes absences » (widget compteurs + liste + formulaire). |

Erreurs : **401** (anonyme), **403** (décision non habilitée), **422** (`AbsenceException`) via
`AbsenceExceptionListener`. DTO strict, sans champ médical.

## Sécurité des données (RLS & RGPD)

Les tables `absence_type` et `absence_request` naissent avec Row-Level Security (`ENABLE` + `FORCE`
+ policy texte). **HAB-3** : seules des données minimales sont stockées (type normalisé, dates,
commentaire libre optionnel, n° justificatif à venir) — jamais de diagnostic ni de motif médical.

## Tests

- `AbsenceRequestTest` (durée demi-journée, couverture, validate/reject, gardes).
- `AbsenceWorkflowTest` (déclaration/décision + notifications, 403 collaborateur).
- `AbsenceBalanceTest` (compteurs + projeté, refusées ignorées).
- `RecordTimeEntryTest::testRefusesProductionOnAValidatedAbsenceDay` (RG-TMP-3).
- `AbsenceApiTest` (API 201/liste/balance/décision/403), `AbsencePageTest` (écran 401/200).
- **`AbsenceRgpdComplianceTest`** — gate HAB-3 : échoue si un champ de santé apparaît (entités + DTO).
- `AbsenceRlsRuntimeTest` (intrusion RLS, rôle NOSUPERUSER).

## Limites connues / suite

- **Scope N+1 du décideur** : `VALIDATE_ABSENCE` sans vérifier que le décideur est le manager du
  demandeur (raffinement à câbler via la hiérarchie US-010).
- **Accrual** : droit annuel fixe paramétré ; un vrai moteur d'acquisition (mensuel/prorata) suivra.
- **Notifications** : les messages `AbsenceDeclared`/`AbsenceDecided` sont publiés ; le handler de
  livraison effective (email + in-app) reste à implémenter.
- **Blocage demi-journée** : un jour d'absence validée bloque toute la journée de production
  (le raffinement « l'autre demi-journée reste saisissable » est différé).

## Revue (T-054-08) — findings traités

Revue `security-auditor` (OWASP 2025 + RGPD) — isolation tenant, habilitations et blocage RG-TMP-3
confirmés conformes (déclaration forcée à l'utilisateur authentifié, lecture strictement soi-même,
`ensureCan` avant tout effet, RLS double barrière, DQL paramétré). Corrections appliquées :

- **[Moyen — corrigé] Séparation des tâches** : `DecideAbsence` refuse qu'un collaborateur décide de
  sa **propre** demande (`décideur ≠ demandeur`), tentative tracée. Le scope N+1 complet (le décideur
  doit être le manager du demandeur) reste un raffinement documenté.
- **[Moyen — atténué] Donnée de santé dans le commentaire libre** : le gate ne contrôle que les noms
  de champs, pas le contenu. Mitigations appliquées : **avertissement UI** « aucune donnée de santé »
  + `maxlength` ; le scan de contenu (peu fiable) est écarté. Risque résiduel accepté et documenté.
- **[Faible — corrigé]** Gate RGPD étendu à `AbsenceBalanceResource`.
- **[Faible — corrigé]** `comment` / motif de refus **bornés** (500 caractères).
- **[Faible — corrigé]** **Durée d'absence bornée** (≤ 366 jours) — évite l'inflation des compteurs.
- **[Info — noté]** Rate limiting de la déclaration self-service : à couvrir au niveau firewall (hors module).

Revue `symfony-reviewer` — corrections/arbitrages :

- **[Élevé — corrigé] Longueur du motif de refus** : bornée (500 caractères, cf. fix sécu).
- **[Moyen — corrigé] Index composite** : `(tenant_id, user_id, status)` sur `absence_request`
  (migration `Version20260901250000`) — `findValidatedCovering` est interrogée à chaque saisie.
- **[Moyen — corrigé] Piège UX du select** : option vide « — Choisir un type — » (forçage du choix).
- **[Faible — corrigé] Docstring `coversDay()`** : sémantique explicite (couverture partielle/complète).
- **Écartés/notés** : assertion tenant dans `DecideAbsenceProcessor` (déjà couverte par
  `findById(tenant, …)`) ; « champs absents de la réponse » (inexact — `startsMorning`/`endsAfternoon`
  sont renvoyés) ; invariants `AbsenceCounters` ≥ 0 (structurellement impossibles) ; perf du calcul
  des compteurs et soft-delete des types (phase 2, cf. *Limites*).

**Conclusion des revues : GO** — aucun Critique/Élevé résiduel ; RGPD/HAB-3, isolation tenant,
habilitations et blocage RG-TMP-3 conformes.
