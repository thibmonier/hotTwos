import { Controller } from '@hotwired/stimulus';

/*
 * US-055 — validation par lot : le chef de projet sélectionne des imputations et les valide
 * ou refuse (motif obligatoire au refus) via POST /api/time-entries/validate. L'habilitation
 * (permission + périmètre) est vérifiée côté serveur (ARC-106) ; l'UI ne fait que présenter.
 */
export default class extends Controller {
    static targets = ['entry', 'reason', 'status'];

    toggleAll(event) {
        this.entryTargets.forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    }

    validate() {
        this.#decide('validate');
    }

    reject() {
        this.#decide('reject');
    }

    async #decide(decision) {
        const entryIds = this.entryTargets.filter((c) => c.checked).map((c) => c.value);
        if (entryIds.length === 0) {
            this.#status('Sélectionnez au moins une imputation.');
            return;
        }

        const reason = this.hasReasonTarget ? this.reasonTarget.value : '';
        if (decision === 'reject' && reason.trim() === '') {
            this.#status('Un motif est obligatoire pour refuser.');
            return;
        }

        try {
            const response = await fetch('/api/time-entries/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entryIds, decision, reason }),
            });

            if (response.ok) {
                const body = await response.json().catch(() => ({}));
                this.#status(`${body.decided ?? 0} imputation(s) ${decision === 'validate' ? 'validée(s)' : 'refusée(s)'}. Rechargez pour actualiser.`);
            } else if (response.status === 403) {
                this.#status('Refusé : hors de votre périmètre de responsabilité.');
            } else {
                const body = await response.json().catch(() => ({}));
                this.#status(body.error ?? 'Refusé par le serveur.');
            }
        } catch {
            this.#status('Erreur réseau : réessayez.');
        }
    }

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
