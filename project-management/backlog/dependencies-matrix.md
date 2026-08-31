# Matrice des Dépendances — EPICs HotOnes

**Projet :** HotOnes — refonte ERP agence digitale / ESN
**Date :** 2026-08-31
**Source de vérité :** `project-management/backlog/epics/`

> **Règle fondamentale :** La dépendance de données entre les lots est **stricte et non parallélisable** — les données du lot N doivent exister en production avant que le lot N+1 puisse livrer de la valeur métier. L'architecture peut être développée en avance de phase ; la mise en production reste séquentielle.

---

## 1. Graphe des dépendances (Mermaid)

```mermaid
graph TD
    E000["EPIC-000\nSocle Walking Skeleton\n[Lot 0/1 — Must]"]
    E001["EPIC-001\nRéférentiels & Paramétrage\n[Lot 1 — Must]"]
    E002["EPIC-002\nProjets & Delivery\n[Lot 1 — Must]"]
    E003["EPIC-003\nTemps & Activité\n[Lot 1 — Must]"]
    E010["EPIC-010\nSocle IA Mutualisé\n[Transverse Lots 1-3]"]
    E004["EPIC-004\nPlanification & Staffing\n[Lot 2 — Should]"]
    E005["EPIC-005\nFinance & Rentabilité\n[Lot 2 — Must/Should]"]
    E006["EPIC-006\nAvant-Vente & CRM\n[Lot 3 — Should]"]
    E007["EPIC-007\nPilotage & Reporting\n[Lot 3 — Should]"]
    E008["EPIC-008\nRH & Cycle de Vie\n[Lot 4 — Should]"]
    E009["EPIC-009\nRecrutement\n[Lot 4 — Should/Could]"]
    E011["EPIC-011\nIndustrialisation SaaS\n[Lot 5 — Should]"]

    %% Socle : prérequis de tous
    E000 --> E001
    E000 --> E002
    E000 --> E003
    E000 --> E010
    E000 --> E004
    E000 --> E005
    E000 --> E006
    E000 --> E007
    E000 --> E008
    E000 --> E009
    E000 --> E011

    %% Lot 1 : dépendances internes
    E001 --> E002
    E001 --> E003
    E002 --> E003

    %% IA transverse : brique 1 dans TMP
    E010 -.->|"Brique 1 : EF-TMP-9\nUS-053"| E003

    %% Lot 1 → Lot 2 (dépendance de données stricte)
    E001 --> E004
    E001 --> E005
    E002 --> E004
    E002 --> E005
    E003 --> E004
    E003 --> E005

    %% Lot 2 → Lot 3 (dépendance de données stricte)
    E004 --> E006
    E004 --> E007
    E005 --> E007
    E006 -.->|"Charge probable\n→ PLN"| E004

    %% Lot 3 → Lot 4
    E004 --> E008
    E004 --> E009
    E008 --> E009

    %% IA Lots 2-3
    E010 -.->|"Briques lots 2-3"| E004
    E010 -.->|"Briques lots 2-3"| E005
    E010 -.->|"Briques lots 2-3"| E007

    %% Lot 4 → Lot 5
    E001 --> E011
    E002 --> E011
    E003 --> E011
    E004 --> E011
    E005 --> E011
    E006 --> E011
    E007 --> E011
    E008 --> E011
    E009 --> E011

    %% Styles par lot
    classDef lot0 fill:#1a1a2e,color:#fff,stroke:#e94560
    classDef lot1 fill:#16213e,color:#fff,stroke:#0f3460
    classDef lot2 fill:#0f3460,color:#fff,stroke:#533483
    classDef lot3 fill:#533483,color:#fff,stroke:#e94560
    classDef lot4 fill:#e94560,color:#fff,stroke:#533483
    classDef lot5 fill:#6b6b6b,color:#fff,stroke:#999
    classDef transverse fill:#2d6a4f,color:#fff,stroke:#40916c,stroke-dasharray: 5 5

    class E000 lot0
    class E001,E002,E003 lot1
    class E004,E005 lot2
    class E006,E007 lot3
    class E008,E009 lot4
    class E011 lot5
    class E010 transverse
```

---

## 2. Tableau des dépendances

| EPIC | Lot | Prérequis directs | Bloqué par (hors EPIC-000) | Dépendants immédiats |
|------|-----|-------------------|----------------------------|----------------------|
| **EPIC-000** Socle Walking Skeleton | 0/1 | AUD-1, AUD-2, AUD-3 | — | Tous les EPICs |
| **EPIC-001** Référentiels | 1 | EPIC-000 | — | EPIC-002, 003, 004, 005, 011 |
| **EPIC-002** Projets | 1 | EPIC-000, EPIC-001 | — | EPIC-003, 004, 005, 011 |
| **EPIC-003** Temps | 1 | EPIC-000, EPIC-001, EPIC-002 | AIPD (`ENF-RGPD-5`) pour US-053 | EPIC-004, 005, 011 |
| **EPIC-010** Socle IA | 1→3 | EPIC-000, ADR-10 | AIPD par usage | EPIC-003 (brique 1), 004, 005, 007 |
| **EPIC-004** Planification | 2 | EPIC-000, 001, 002, 003 | Lot 1 en production | EPIC-005, 006, 007, 008, 009, 011 |
| **EPIC-005** Finance | 2 | EPIC-000, 001, 002, 003, 004 | Lot 1 en production | EPIC-007, 011 |
| **EPIC-006** CRM | 3 | EPIC-000, 001, 004 | Lot 2 en production | EPIC-004 (rétroaction charge probable), 007, 011 |
| **EPIC-007** Pilotage | 3 | EPIC-000, 001, 002, 003, 004, 005, 006 | Lot 2 en production | — (consommateur terminal) |
| **EPIC-008** RH | 4 | EPIC-000, 001, 003, 004 | Lot 3 validé + AI Act (`CTR-3`) + AIPD | EPIC-009, 011 |
| **EPIC-009** Recrutement | 4 | EPIC-000, 004, 008 | Lot 3 validé + AI Act (`CTR-3`) + AIPD | EPIC-008 (rétroaction), 011 |
| **EPIC-011** SaaS | 5 | EPIC-000 + tous lots 1→4 stables | Tous lots en production | — (dernier lot) |

---

## 3. Chemin critique (lots)

```
LOT 0/1
  EPIC-000 (Socle)
    └── EPIC-001 (REF) ──┐
    └── EPIC-002 (PRJ) ──┤── EPIC-003 (TMP) ← Point de défaillance unique
                                                (adoption = condition de tout)
LOT 2 (après lot 1 en production)
  EPIC-004 (PLN) ──────────────────────────────────────────┐
  EPIC-005 (FIN)                                           │

LOT 3 (après lot 2 en production)                         │
  EPIC-006 (CRM) ──────────────────────────────────────────┤ (rétroaction PLN)
  EPIC-007 (PIL)                                           │

LOT 4 (après lot 3 validé + prérequis réglementaires)     │
  EPIC-008 (RH) ──────────────────────────────────────────┐│
  EPIC-009 (REC)                                          ││

LOT 5 (tous lots stables)                                 ││
  EPIC-011 (SaaS)                                         ││

TRANSVERSE (lots 1→3)                                     ││
  EPIC-010 (IA) ─ brique 1 dans LOT 1 ────────────────────┘┘
```

---

## 4. Contraintes réglementaires bloquantes (hors dépendances techniques)

| Contrainte | Référence | Bloque |
|-----------|-----------|--------|
| Audit technique MVP (`AUD-1`) | Avant lot 1 | EPIC-000 |
| Cartographie fonctionnelle MVP (`AUD-2`) | Avant lot 1 | EPIC-000 |
| Mesure situations de référence (`AUD-3`) | Avant lot 1 | OBJ-1..7 |
| AIPD signaux d'activité (`ENF-RGPD-5`) | Avant US-053 | EPIC-010 brique 1, EPIC-003 |
| Qualification AI Act (`CTR-3`/`ARB-14`) | Avant conception RH/REC | EPIC-008, EPIC-009 |
| AIPD RH et recrutement (`ENF-RGPD-5`) | Avant fonction IA RH | EPIC-008, EPIC-009 |

---

**Documents liés :** `backlog/epics/EPIC-*.md`, `analysis/constraints.md`, `analysis/research-summary.md`
