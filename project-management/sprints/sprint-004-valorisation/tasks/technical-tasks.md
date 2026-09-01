# Tâches techniques transverses — Sprint 4 (Valorisation)

## Prérequis engagés (dans le total du sprint)

### T-TECH-01 · Installer & configurer Symfony Messenger (async)
- **Type** : [OPS] · **Estimation** : 3h · **Dépend de** : —
- **Pourquoi** : US-060 exige un traitement **asynchrone ≤ 15 min**. `symfony/messenger` **n'est pas installé** aujourd'hui (tout est synchrone ; seul le rebuild analytique est différé, en CLI).
- **Contenu** :
  - `composer require symfony/messenger symfony/doctrine-messenger` (version pinée).
  - `config/packages/messenger.yaml` : **transport Doctrine** (`ADR-0007`), routage du message `TimeEntriesValidated` vers l'async, retry borné.
  - **Parité worker** : consommateur `messenger:consume` supervisé (cohérent FrankenPHP worker `ADR-11`) ; contexte tenant posé/effacé par message (`ARC-47`, pas d'état résiduel — `RSQ-15`).
  - CI : ajouter l'étape « tests worker » couvrant la consommation async (`ADR-12`).
- **Validation** : un message publié est consommé hors requête HTTP ; le contexte tenant est correctement isolé par message (test d'intégration).

### T-TECH-02 · Étendre la RLS aux tables métier (`DBT-SEC-1`)
- **Type** : [DB][OPS] · **Estimation** : 3h · **Dépend de** : migrations US-010/011/060
- **Pourquoi** : `project` et `time_entry` **n'ont pas de policy RLS** déclarée (isolation par filtre ORM + DQL seulement). Les **nouvelles tables** (`org_unit`, `org_membership`, `profile`, `profile_rate`, `time_entry_valuation`) doivent naître avec RLS (double barrière `ADR-0006`).
- **Contenu** : policies `ENABLE + FORCE ROW LEVEL SECURITY` + `tenant_isolation` (`current_setting('app.current_tenant')`) sur les tables métier, en **migrations idempotentes** (bloc `DO $$ … insufficient_privilege`), **relues à la main** (`ARC-106`).
- **Validation** : test d'intrusion RLS runtime (rôle `NOSUPERUSER`) — sans contexte, `COUNT = 0` ; cross-tenant invisible. Étend `TenantRlsRuntimeTest`.

> Une partie du DDL RLS est **portée par les migrations de chaque US** (T-010-02, T-011-01, T-060-03) ; cette tâche **trace l'action transverse** (couverture complète + test d'intrusion) et rattrape `project`/`time_entry`.

---

## Réserve — actions rétro Sprint 3 (hors engagement, « si capacité »)

> Le sprint-goal donne **priorité au métier**. Ces actions restent ouvertes ; à tirer seulement si la capacité le permet. **Non comptées** dans les 21 points / 84h engagés.

### T-TECH-03 · E2E chronométré ≤ 2 min (`RSQ-1`)
- **Type** : [TEST] · **Estimation** : 2h
- Parcours de bout en bout chronométré (saisie → validation → marge visible), assertion `< 2 min`. Action rétro S3 récurrente.

### T-TECH-04 · Fixtures de démo
- **Type** : [OPS] · **Estimation** : 2h
- Jeu de données de démonstration (tenant, org, profils/taux, imputations validées valorisées) pour la Sprint Review et le staging.

---

## Récapitulatif

| ID | Type | Tâche | Est. | Engagé ? |
|----|------|-------|------|----------|
| T-TECH-01 | [OPS] | Installer/configurer Messenger (async, transport Doctrine) | 3h | ✅ oui |
| T-TECH-02 | [DB][OPS] | Étendre RLS aux tables métier + test d'intrusion | 3h | ✅ oui |
| T-TECH-03 | [TEST] | E2E chronométré ≤ 2 min (`RSQ-1`) | 2h | 🔶 réserve |
| T-TECH-04 | [OPS] | Fixtures de démo | 2h | 🔶 réserve |
