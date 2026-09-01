# Rétrospective — Sprint 4 : Valorisation automatique du temps validé

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Format | Sailboat ⛵ |
| Facilitateur | Scrum Master |
| Contexte | Dev piloté IA + revues automatisées (`security-auditor`, `symfony-reviewer`) |

## Directive Fondamentale (Kerth)

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait
> du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences, des
> ressources disponibles et de la situation. »

## 🏝️ Île — Destination (objectifs à viser au prochain sprint)

- Lever le **stub de clôture de période** : clôture/réouverture **par tenant** (US-057).
- Solder la **dette de hardening** : lot `sprintf`→`set_config` (paramètre lié) sur les 3 points de pose du tenant.
- Décider du **fait coût/marge** dans le modèle en étoile (extension analytique).

## 💨 Vent — Ce qui nous a poussés

- **Non-divergence par construction** : un réducteur unique (`RecognizedRevenue`) partagé
  projecteur/checker — élégant et robuste, a évité une classe entière de bugs.
- **Boucle de vérification serrée** : TDD + `make ci` dockerisé (PHPStan max, Deptrac, tests,
  gitleaks) à chaque tâche → chaque commit vert, aucune régression.
- **Revue croisée adversariale** (sécurité + Symfony) : a détecté une **vraie vulnérabilité**
  d'isolation, pas seulement du style.
- **Découpage en tâches fines** commitées indépendamment : progression lisible, reprise facile.

## ⚓ Ancre — Ce qui nous a freinés

- **Gap de parité test/prod sur l'async** : la faille RLS worker était invisible car les tests
  consomment les messages via un transport `in-memory` **dans la requête HTTP** (où le tenant DB
  est déjà posé). Vrai vecteur non exercé → détecté seulement en revue.
- **Frictions PHPStan max** : plusieurs itérations sur les casts `mixed` (int/string), le
  `?->` post-`assertSame`, `createMock` vs `createStub` — petites reprises répétées.
- **Piège kernel-reboot** en test fonctionnel : un override de service ne survit qu'avec
  `disableReboot()` + id concret — coûté un aller-retour de debug.
- **Messages de revue tronqués** : findings livrés en plusieurs morceaux → round-trips
  `SendMessage` pour récupérer la suite.

## 🪨 Récifs — Risques à éviter

- **Stub de clôture global** : tant qu'US-057 n'est pas là, une période « clôturée » l'est pour
  **tous** les tenants — à ne pas prendre pour une vraie isolation.
- **Motif d'interpolation `sprintf` du tenant** répété (3 sites) : sûr aujourd'hui (UUID validé),
  fragile si une future source de tenant n'est plus validée.
- **`flush` par imputation** : acceptable au budget actuel, à surveiller si les volumes montent.
- **Coût/marge hors étoile** : le dashboard lit `TimeEntryValuation` directement — cohérent, mais
  le fait analytique reste partiel (CA seul).

## Analyse de cause racine — la faille RLS worker (5 pourquoi)

1. **Pourquoi la faille a-t-elle failli passer ?** → Toute la CI était verte.
2. **Pourquoi verte malgré la faille ?** → En test, l'async tourne sur `in-memory` consommé
   **dans** la requête HTTP, où `TenantSessionConfigurator` a déjà posé `app.current_tenant`.
3. **Pourquoi le worker réel diffère-t-il ?** → La variable de session DB n'était posée que par un
   listener `RequestEvent` (HTTP), pas par le middleware Messenger.
4. **Pourquoi aucun test ne l'a exercé ?** → US-060 introduit la **première écriture async réelle**
   en base ; aucun test ne consommait un message jusqu'à une écriture sous rôle NOSUPERUSER.
5. **Cause racine** → **Gap de parité test/prod** : le modèle d'exécution asynchrone n'était pas
   testé de bout en bout contre la RLS sous le rôle applicatif réel.

## 🎯 Actions Sprint 5

### Action 1 : Garde de parité RLS pour tout handler async

| Attribut | Valeur |
|----------|--------|
| Description | Tout nouveau `#[AsMessageHandler]` qui écrit en base doit avoir un test d'intrusion RLS **via consume** sous rôle NOSUPERUSER (pattern `ValuationWorkerRlsTest`). |
| Responsable | Tech Lead |
| Deadline | Sprint 5 (checklist DoD) |
| DoD | Ajout au `definition-of-done.md` ; au moins un test de parité par handler async écrivant en DB. |
| Priorité | Haute |

### Action 2 : Solder le lot de hardening `set_config`

| Attribut | Valeur |
|----------|--------|
| Description | Remplacer `sprintf("SET app.current_tenant='%s'")` par `set_config('app.current_tenant', ?, ...)` (paramètre lié) dans `DoctrineAnalyticsProjector`, `TenantSessionConfigurator`, `TenantContextMiddleware`. |
| Responsable | Dev |
| Deadline | Début Sprint 5 |
| DoD | `grep -r "SET app.current_tenant = '"` sur `src/` ne renvoie plus rien ; CI verte. |
| Priorité | Moyenne |

### Action 3 : Tracer la dépendance US-057 (stub de clôture)

| Attribut | Valeur |
|----------|--------|
| Description | Documenter dans le backlog que `ConfiguredPeriodClosure` est un stub **global** à remplacer par une clôture par tenant sous RLS quand US-057 est planifiée. |
| Responsable | Product Owner |
| Deadline | Planning Sprint 5 |
| DoD | US-057 porte explicitement « remplace le stub de clôture global d'US-060 ». |
| Priorité | Moyenne |

## Suivi des actions précédentes

| Sprint | Action | Status |
|--------|--------|--------|
| S-3 | Généraliser la RLS aux tables métier | ✅ Fait (T-TECH-02 : project + time_entry) |

## Check-out

- **ROTI** : 5/5 — le sprint a livré 100 % du périmètre ET révélé/corrigé une faille socle avec sa couverture.
- **Ce qu'on emporte** : « la CI verte ne prouve pas la parité prod ; l'async se teste jusqu'à la barrière DB réelle. »
