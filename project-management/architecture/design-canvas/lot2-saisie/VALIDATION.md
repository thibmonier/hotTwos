# Validation des maquettes — Lot 2 : parcours de saisie collaborateur (US-084, gate PO)

> **But** : recueillir ta validation (PO) des maquettes haute-fidélité du parcours de saisie P1 (EPIC-003).
> Cette validation **conditionne le dev reskin du Sprint 21** : un écran non « Validé » ne part pas au dev.
> **Story** : US-084 (Sprint 20, EPIC-013) · **Sprint** : 20 · **Système** : bundle `tailsfadmin`.

**Canevas à revoir** : 🎨 https://claude.ai/code/artifact/4b74613a-6890-428b-bb2a-950f751b17ba
(7 artboards ; sources versionnées dans `design-canvas/lot2-saisie/*.dc.html`.)

---

## 1. Décisions d'ergonomie transverses (rappel, déjà ratifiées lot 1)

| Réf | Décision | Application dans le lot 2 |
|-----|----------|---------------------------|
| F-S5-4 | Collaborateurs identifiés par **e-mail** (jamais `userId[:8]`) | ✅ Complétude affiche `camille@acme.fr`, etc. |
| F-S5-5 | Statuts = **texte + icône + couleur** (jamais couleur seule) | ✅ Badges complétude/absences avec icône SVG rétablie |
| F1 | Aucun identifiant technique brut affiché | ✅ Codes projet en libellé secondaire lisible, pas d'UUID |

---

## 2. Grille de validation par écran

Verdict : ✅ Validé · 🔁 Révision demandée (préciser) · ⏸️ En attente.
Chaque écran : cohérence tokens tailsfadmin · parcours P1 · états · accessibilité AA · recommandations d'audit US-082 appliquées.

| # | Écran (artboard) | Régime | Points de contrôle clés | Recos audit intégrées | Verdict | Commentaire |
|---|------------------|--------|-------------------------|-----------------------|---------|-------------|
| 1 | **DSH-COLLAB** (`Main.dc.html`) | Création | PageHeader (G4) + 4 StatCards (G1) contrepartie (heures/complétude/solde/avancement) ; CTA saisie 1 clic ; imputations récentes ; actions rapides | Conçu depuis parcours P1 ; **pas** de compteurs de volume ; gaps G1/G4 matérialisés + notés | ☐✅ ☐🔁 | |
| 2 | **PG-TMP-01 Saisie hebdo** (`SaisieHebdo.dc.html`) | Reskin+ | Grille projet × jours ouvrés ; totaux ligne/jour/semaine rendus ; barre d'objectif visible ; toggle week-ends ; confirmation « Enregistré » inline ; cibles 44px | TMP1-01 (totaux), TMP1-02 (objectif hors dialog), TMP1-03 (confirm inline), TMP1-04 (week-ends), TMP1-07 (code projet lisible) | ☐✅ ☐🔁 | |
| 3 | **PG-TMP-01 États** (`SaisieHebdoEtats.dc.html`) | Reskin+ | État vide (semaine vierge + CTA duplication) ; **état erreur de validation** (cellule > 24h, bordure + message + `aria-invalid` + bannière `role=alert` + enregistrement bloqué) | TMP1-05 (état erreur explicite, CA-4) | ☐✅ ☐🔁 | |
| 4 | **PG-TMP-01 Mobile** (`SaisieHebdoMobile.dc.html`) | Reskin+ | ~390px ; sélecteur de jours (statut par pastille) ; cartes projet empilées ; barre d'action collante ; pas de fausse status bar | CA-4 (variante mobile lisible) | ☐✅ ☐🔁 | |
| 5 | **PG-TMP-02/03 Saisie jour** (`SaisieJour.dc.html`) | Reskin+ | Mobile ; objectif du jour + total ; bannières **hors-ligne + resync** ; commentaire multi-ligne ; reprendre la veille ; cibles 44px+ | TMP2-02 (total), TMP2-03 (objectif jour), TMP2-04 (textarea), TMP2-01 (offline conservé) | ☐✅ ☐🔁 | |
| 6 | **PG-ABS-01 Absences** (`Absences.dc.html`) | Reskin+ | Compteurs (StatCards) ; formulaire + **impact solde projeté contextualisé** ; **vue calendrier** fériés/fermetures/conflits ; demi-journées reformulées ; badges statut icône+texte ; RGPD | ABS-01 (badges), ABS-02 (solde contextuel), ABS-03 (calendrier conflits), ABS-04 (demi-journées), ABS-05 (motif refus en détail), ABS-06 (RGPD) | ☐✅ ☐🔁 | |
| 7 | **PG-CPL-01 Complétude** (`Completude.dc.html`) | Reskin+ | E-mails (F-S5-4) ; badges texte+icône+couleur (F-S5-5) ; **StatCards synthèse** retard/partiel/soumis ; **relance inline** (sélection → relancer) ; filtre/recherche ; **colonne collaborateur figée** | CPL-01 (icônes), CPL-02 (e-mail, 🔴 bloquant), CPL-03 (StatCards G1), CPL-04 (relance inline), CPL-05 (filtre), CPL-06 (sticky) | ☐✅ ☐🔁 | |

> **DoD (CA-2/CA-6)** : la story n'est Done que lorsque **100 % des 5 écrans prioritaires** portent un verdict ✅.
> (DSH-COLLAB, saisie hebdo — incluant états + mobile —, saisie jour, absences, complétude.)

---

## 3. Gaps composants matérialisés (à développer au Sprint 21 — PAS ici)

| Gap | Composant | Écrans | Statut maquette |
|-----|-----------|--------|-----------------|
| **G1** | `Ui:StatCard` | DSH-COLLAB, Absences, Complétude | ✅ Matérialisé (cartes KPI), **noté gap** — dev bundle S21 |
| **G4** | `Layout:PageHeader` | DSH-COLLAB (+ convention transverse) | ✅ Matérialisé (bandeau titre + actions), **noté gap** — dev bundle S21 |

> Aucun composant/token hors bundle `tailsfadmin` (CA-5). Les gaps sont dessinés mais **non développés** (règle S20).

---

## 4. Check-list qualité (rappel, par écran validé)

- [ ] Cohérent avec le bundle `tailsfadmin` — tokens (Outfit, brand #465fff, gray, statuts), pas de style hors système
- [ ] États couverts (saisie hebdo) : vide / rempli / erreur de validation + mobile
- [ ] Info jamais portée par la seule couleur (WCAG 1.4.1) ; cibles ≥ 44px ; focus visible
- [ ] Aucun identifiant technique brut (F1) ; collaborateurs en e-mail (F-S5-4)
- [ ] Contraste conforme AA
- [ ] Recommandation d'audit « bloquant/majeur » appliquée ou écart justifié

---

## 5. Registre de validation (traçabilité)

| Écran | Version maquette | Date | Validé par | Verdict |
|-------|------------------|------|-----------|---------|
| DSH-COLLAB | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Saisie hebdo | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Saisie hebdo — états | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Saisie hebdo — mobile | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Saisie jour | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Absences | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |
| Complétude | design-canvas v1 (2026-09-14) | | | ⏸️ To Review |

> Un écran passe au reskin (S21) **uniquement** lorsqu'il porte un verdict ✅ daté ici.
