import { Controller } from '@hotwired/stimulus';

/*
 * US-050 — saisie du temps : enregistre chaque cellule (projet × jour) via l'API
 * POST /api/time-entries dès qu'elle change. Aucune règle métier côté client (ARC-27) :
 * la validation (projet actif, plafond) est faite côté serveur, la réponse est reflétée.
 *
 * Saisie en HEURES décimales (7,5 = 7h30) — l'API attend des minutes, la conversion est faite
 * ici (le stockage reste en minutes). Totaux (jour / semaine / projet) recalculés en direct.
 */
export default class extends Controller {
    static targets = ['cell', 'status', 'grid', 'rowTotal', 'dayTotal', 'grandTotal'];
    static values = { weekStart: String };

    connect() {
        this.element.addEventListener('keydown', (event) => this.#onKeydown(event));
        this.recomputeTotals();
    }

    // Enregistre toute la semaine en une requête (US-051, ≤ 2 min).
    async saveWeek() {
        const entries = this.cellTargets
            .map((cell) => ({
                projectId: cell.dataset.projectId,
                date: cell.dataset.date,
                minutes: this.#toMinutes(cell.value),
            }))
            .filter((entry) => Number.isInteger(entry.minutes) && entry.minutes > 0);

        if (entries.length === 0) {
            this.#status('Rien à enregistrer.');
            return;
        }

        try {
            const response = await fetch('/api/time-entries/week', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entries }),
            });
            const body = await response.json().catch(() => ({}));
            const errors = body.errors ?? [];
            this.#status(`Tout enregistré : ${body.recorded ?? 0} imputation(s)${errors.length ? `, ${errors.length} refusée(s)` : ''}.`);
        } catch {
            this.#status('Erreur réseau : réessayez.');
        }
    }

    // Reporte la semaine précédente dans la semaine affichée (US-051, ≤ 2 min), puis recharge.
    async duplicatePreviousWeek() {
        try {
            const response = await fetch('/api/time-entries/duplicate-week', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ weekStart: this.weekStartValue }),
            });
            if (response.ok) {
                this.#status('Semaine précédente dupliquée. Actualisation…');
                window.location.reload();
            } else {
                this.#status('Duplication refusée par le serveur.');
            }
        } catch {
            this.#status('Erreur réseau : réessayez.');
        }
    }

    async save(event) {
        const cell = event.target;
        const minutes = this.#toMinutes(cell.value);
        if (!Number.isInteger(minutes) || minutes <= 0) {
            this.#status('Cellule ignorée (durée vide ou nulle).');
            return;
        }

        cell.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch('/api/time-entries', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    projectId: cell.dataset.projectId,
                    date: cell.dataset.date,
                    minutes,
                }),
            });

            if (response.ok) {
                this.#status('Enregistré automatiquement.');
                cell.classList.remove('is-error');
            } else {
                const body = await response.json().catch(() => ({}));
                this.#status(body.error ?? 'Refusé par le serveur.');
                cell.classList.add('is-error');
            }
        } catch {
            this.#status('Erreur réseau : réessayez.');
            cell.classList.add('is-error');
        } finally {
            cell.removeAttribute('aria-busy');
        }
    }

    // Recalcule les totaux par jour (colonne), par projet (ligne) et le total de la semaine.
    recomputeTotals() {
        const perDay = new Map();
        const perProject = new Map();
        let grand = 0;

        for (const cell of this.cellTargets) {
            const minutes = this.#toMinutes(cell.value);
            if (!Number.isInteger(minutes) || minutes <= 0) {
                continue;
            }
            const { date, projectId } = cell.dataset;
            perDay.set(date, (perDay.get(date) ?? 0) + minutes);
            perProject.set(projectId, (perProject.get(projectId) ?? 0) + minutes);
            grand += minutes;
        }

        for (const cell of this.dayTotalTargets) {
            cell.textContent = this.#formatHours(perDay.get(cell.dataset.date) ?? 0);
        }
        for (const cell of this.rowTotalTargets) {
            cell.textContent = this.#formatHours(perProject.get(cell.dataset.projectId) ?? 0);
        }
        if (this.hasGrandTotalTarget) {
            this.grandTotalTarget.textContent = this.#formatHours(grand);
        }
    }

    // Navigation « type tableur » : Entrée valide la cellule et descend d'une ligne.
    #onKeydown(event) {
        if (event.key !== 'Enter' || event.target.tagName !== 'INPUT') {
            return;
        }
        event.preventDefault();
        event.target.dispatchEvent(new Event('change', { bubbles: true }));

        const cells = this.cellTargets;
        const index = cells.indexOf(event.target);
        const columns = this.#columnCount();
        const below = cells[index + columns];
        if (below) {
            below.focus();
            below.select();
        }
    }

    #columnCount() {
        const firstRow = this.hasGridTarget ? this.gridTarget.querySelector('tbody tr') : null;
        return firstRow ? firstRow.querySelectorAll('input').length : 1;
    }

    // Heures décimales saisies (« 7,5 » ou « 7.5 ») → minutes entières attendues par l'API.
    #toMinutes(value) {
        const hours = Number.parseFloat(String(value).replace(',', '.'));
        return Number.isFinite(hours) ? Math.round(hours * 60) : NaN;
    }

    // Minutes → « 7h » ou « 7h30 » (0 → « 0h »).
    #formatHours(minutes) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`;
    }

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
