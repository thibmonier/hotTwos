# Validation des maquettes — Lot 1 (US-062, gate PO)

> **But** : recueillir ta validation (PO) des maquettes UX/UI. Cette validation **conditionne** le reskin
> (US-064), écran par écran (cf. `ux-conception-lot1.md`). Un écran non « validé » reste bloqué au reskin.

**Canevas à revoir** : 🎨 https://claude.ai/code/artifact/0119b5de-dbaa-4c13-ad4b-248fcbc9ac72
(3 écrans maquettés ; les autres écrans du lot 1 restent à maquetter — T-062-01.)

---

## 1. Décisions d'ergonomie à ratifier (transverses)

| Réf | Décision proposée | Ton verdict |
|-----|-------------------|-------------|
| F-S5-4 | Collaborateurs identifiés par **e-mail** (résolveur tenant-aware). Pis-aller : pas de nom/prénom en base aujourd'hui. | ✅ OK ☐ À revoir |
| — | **Ouvrir un futur enrichissement `User` (nom/prénom)** ? (arbitrage séparé, non bloquant pour le lot 1) | ✅ Oui ☐ Non ☐ Plus tard |
| F-S5-5 | Statuts complétude = **texte + icône + couleur** (jamais la couleur seule, WCAG 1.4.1) ; légende toujours visible | ✅ OK ☐ À revoir |
| F1 | **Aucun identifiant technique** (UUID, préfixe) affiché à l'utilisateur | ✅ OK ☐ À revoir |
| Process | Checklist de validation UX **non négociable** même sous pression « fast-track » | ☐ Je maintiens ✅ J'assouplis |

---

## 2. Grille de validation par écran

Pour chaque écran : cohérence charte (tokens), parcours, états, accessibilité, décisions d'ergonomie.
Verdict : ✅ Validé · 🔁 Révision demandée (préciser) · ⏸️ En attente (écran non encore maquetté).

### Écrans maquettés (dans le canevas)

| Écran | Points de contrôle clés | Verdict | Commentaire |
|-------|-------------------------|---------|-------------|
| **Saisie hebdomadaire** | Grille jours × projets ; navigation semaine ; barre de complétude ; cibles 44px ; objectif ~2 min (US-051) | ✅✅ ☐🔁 | |
| **Complétude** | Grille collaborateurs (e-mail) × semaines ; statuts texte+icône+couleur ; légende visible ; relance en ligne ; F-S5-4/F-S5-5 | ✅✅ ☐🔁 | |
| **Valorisation** | Alerte `missing_rate` ; KPI CA/coût/marge ; table par projet ; note habilitation `VIEW_PROJECT_FINANCIALS` | ✅✅ ☐🔁 | |

### Écrans à maquetter avant validation (T-062-01)

| Écran | Statut | Verdict |
|-------|--------|---------|
| Absences (US-054) | ⏸️ à maquetter | ✅ |
| Relances (US-056) | ⏸️ à maquetter | ✅ |
| Projets — liste/détail/création (EPIC-002) | ⏸️ à maquetter | ✅ |
| Validation des temps par lot (US-055) | ⏸️ à maquetter | ✅ |
| Synthèse d'activité (US-059) | ⏸️ à maquetter | ✅ |
| Organisation (US-010) | ⏸️ à maquetter | ✅ |
| Profils & taux (US-011) | ⏸️ à maquetter | ✅ |
| Administration / périodes (US-057) | ⏸️ à maquetter | ✅ |

> **Interprétation (process assoupli, PO 2026-09-02)** : ces ✅ valent **pré-approbation de la direction design**
> (pas de maquette haute-fidélité séparée exigée). Ces écrans seront **reskinnés en appliquant directement la
> charte** (`design-system.md`) + le layout (US-063), puis **validés en lot** par le PO sur le résultat.
> T-062-01 (produire les 8 maquettes) devient donc **optionnel/à la demande**, pas un gate bloquant.
> Plancher conservé : WCAG 2.2 AA + états « sans-permission » sur les écrans à données sensibles
> (valorisation, projets coût/marge, profils taux).

---

## 3. Check-list qualité (rappel, par écran validé)

- [ ] Cohérent avec la charte (`design-system.md`) — tokens, composants, pas de style hors système
- [ ] États couverts : nominal / vide / erreur / chargement / **sans-permission** (obligatoire pour données sensibles)
- [ ] Déclinaison **mobile ET desktop**
- [ ] Info jamais portée par la seule couleur (WCAG 1.4.1) ; cibles ≥ 44px ; focus visible
- [ ] Aucun identifiant technique brut affiché (F1)
- [ ] Contraste conforme AA (charte vérifiée)

---

## 4. Registre de validation (traçabilité — T-062-03)

| Écran | Version maquette | Date | Validé par | Verdict |
|-------|------------------|------|-----------|---------|
| Saisie hebdomadaire | design-canvas 2026-09-02 | 2026-09-02 | Thibaut (PO) | ✅ Validé |
| Complétude | design-canvas 2026-09-02 | 2026-09-02 | Thibaut (PO) | ✅ Validé |
| Valorisation | design-canvas 2026-09-02 | 2026-09-02 | Thibaut (PO) | ✅ Validé |

> Un écran passe au reskin (US-064) **uniquement** lorsqu'il porte un verdict ✅ daté ici.
> `uiux-orchestrator` consulte ce registre avant d'ouvrir chaque tâche de reskin.

---

## 5. Décisions PO enregistrées (2026-09-02)

- **F-S5-4 → e-mail** ✅ · **F-S5-5 → texte+icône+couleur** ✅ · **F1 → aucun ID technique** ✅.
- **Enrichissement `User` (nom/prénom)** : ✅ **à ouvrir** (US séparée, non bloquante lot 1 — remplacera l'e-mail
  comme libellé une fois livrée ; la maquette prévoit déjà la bascule sans redesign).
- **Process de validation UX → assoupli.** La cérémonie de validation **par écran** n'est plus un gate bloquant :
  les écrans restants peuvent être reskinnés en appliquant directement la charte, avec **validation PO en lot**
  (a posteriori) plutôt qu'écran par écran a priori.
  **Plancher conservé (reste dans la DoD, non négociable) :** WCAG 2.2 AA (contraste, focus, clavier, cibles 44px,
  info non portée par la seule couleur) et **états « sans-permission »** pour les écrans à données sensibles.
  L'allègement porte sur la *cérémonie*, pas sur l'accessibilité ni la sécurité.
- **3 écrans validés** (saisie, complétude, valorisation) → reskin ouvert (US-064-01/02/03), séquencé après US-063 (layout).
