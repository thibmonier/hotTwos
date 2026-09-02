# Validation des maquettes — Lot 1 (US-062, gate PO)

> **But** : recueillir ta validation (PO) des maquettes UX/UI. Cette validation **conditionne** le reskin
> (US-064), écran par écran (cf. `ux-conception-lot1.md`). Un écran non « validé » reste bloqué au reskin.

**Canevas à revoir** : 🎨 https://claude.ai/code/artifact/0119b5de-dbaa-4c13-ad4b-248fcbc9ac72
(3 écrans maquettés ; les autres écrans du lot 1 restent à maquetter — T-062-01.)

---

## 1. Décisions d'ergonomie à ratifier (transverses)

| Réf | Décision proposée | Ton verdict |
|-----|-------------------|-------------|
| F-S5-4 | Collaborateurs identifiés par **e-mail** (résolveur tenant-aware). Pis-aller : pas de nom/prénom en base aujourd'hui. | ☐ OK ☐ À revoir |
| — | **Ouvrir un futur enrichissement `User` (nom/prénom)** ? (arbitrage séparé, non bloquant pour le lot 1) | ☐ Oui ☐ Non ☐ Plus tard |
| F-S5-5 | Statuts complétude = **texte + icône + couleur** (jamais la couleur seule, WCAG 1.4.1) ; légende toujours visible | ☐ OK ☐ À revoir |
| F1 | **Aucun identifiant technique** (UUID, préfixe) affiché à l'utilisateur | ☐ OK ☐ À revoir |
| Process | Checklist de validation UX **non négociable** même sous pression « fast-track » | ☐ Je maintiens ☐ J'assouplis |

---

## 2. Grille de validation par écran

Pour chaque écran : cohérence charte (tokens), parcours, états, accessibilité, décisions d'ergonomie.
Verdict : ✅ Validé · 🔁 Révision demandée (préciser) · ⏸️ En attente (écran non encore maquetté).

### Écrans maquettés (dans le canevas)

| Écran | Points de contrôle clés | Verdict | Commentaire |
|-------|-------------------------|---------|-------------|
| **Saisie hebdomadaire** | Grille jours × projets ; navigation semaine ; barre de complétude ; cibles 44px ; objectif ~2 min (US-051) | ☐✅ ☐🔁 | |
| **Complétude** | Grille collaborateurs (e-mail) × semaines ; statuts texte+icône+couleur ; légende visible ; relance en ligne ; F-S5-4/F-S5-5 | ☐✅ ☐🔁 | |
| **Valorisation** | Alerte `missing_rate` ; KPI CA/coût/marge ; table par projet ; note habilitation `VIEW_PROJECT_FINANCIALS` | ☐✅ ☐🔁 | |

### Écrans à maquetter avant validation (T-062-01)

| Écran | Statut | Verdict |
|-------|--------|---------|
| Absences (US-054) | ⏸️ à maquetter | — |
| Relances (US-056) | ⏸️ à maquetter | — |
| Projets — liste/détail/création (EPIC-002) | ⏸️ à maquetter | — |
| Validation des temps par lot (US-055) | ⏸️ à maquetter | — |
| Synthèse d'activité (US-059) | ⏸️ à maquetter | — |
| Organisation (US-010) | ⏸️ à maquetter | — |
| Profils & taux (US-011) | ⏸️ à maquetter | — |
| Administration / périodes (US-057) | ⏸️ à maquetter | — |

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
| Saisie hebdomadaire | design-canvas 2026-09-02 | | | |
| Complétude | design-canvas 2026-09-02 | | | |
| Valorisation | design-canvas 2026-09-02 | | | |

> Un écran passe au reskin (US-064) **uniquement** lorsqu'il porte un verdict ✅ daté et signé ici.
> `uiux-orchestrator` consulte ce registre avant d'ouvrir chaque tâche de reskin.
