import { Controller } from '@hotwired/stimulus';

/*
 * US-050 — saisie du temps : enregistre chaque cellule (projet × jour) via l'API
 * POST /api/time-entries dès qu'elle change. Aucune règle métier côté client (ARC-27) :
 * la validation (projet actif, plafond) est faite côté serveur, la réponse est reflétée.
 */
export default class extends Controller {
    static targets = ['cell', 'status'];

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

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
