import { Controller } from '@hotwired/stimulus';

/*
 * US-050 — saisie du temps : enregistre chaque cellule (projet × jour) via l'API
 * POST /api/time-entries dès qu'elle change. Aucune règle métier côté client (ARC-27) :
 * la validation (projet actif, plafond) est faite côté serveur, la réponse est reflétée.
 */
export default class extends Controller {
    static targets = ['cell', 'status', 'grid'];
    static values = { weekStart: String };

    connect() {
        this.element.addEventListener('keydown', (event) => this.#onKeydown(event));
    }

    // Enregistre toute la semaine en une requête (US-051, ≤ 2 min).
    async saveWeek() {
        const entries = this.cellTargets
            .map((cell) => ({
                projectId: cell.dataset.projectId,
                date: cell.dataset.date,
                minutes: Number.parseInt(cell.value, 10),
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
            this.#status(`Semaine enregistrée : ${body.recorded ?? 0} imputation(s)${errors.length ? `, ${errors.length} refusée(s)` : ''}.`);
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
        const minutes = Number.parseInt(cell.value, 10);
        if (Number.isNaN(minutes) || minutes <= 0) {
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
                this.#status('Enregistré.');
                cell.classList.remove('is-error');
            } else {
                const body = await response.json().catch(() => ({}));
                this.#status(body.error ?? "Refusé par le serveur.");
                cell.classList.add('is-error');
            }
        } catch {
            this.#status('Erreur réseau : réessayez.');
            cell.classList.add('is-error');
        } finally {
            cell.removeAttribute('aria-busy');
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

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
