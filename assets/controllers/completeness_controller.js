import { Controller } from '@hotwired/stimulus';

/*
 * US-092b — grille de complétude : filtre/recherche client (CPL-05) et relance inline (CPL-04).
 * La relance POST /completude/relances (CSRF) sans quitter l'écran ; le serveur porte les règles
 * (habilitation MANAGE_REMINDERS, opt-out, retard réel). Retour accessible via une zone aria-live.
 */
export default class extends Controller {
    static targets = ['row', 'checkbox', 'selectAll', 'status', 'search', 'feedback'];
    static values = { token: String, url: String };

    filter() {
        const status = this.hasStatusTarget ? this.statusTarget.value : '';
        const query = this.hasSearchTarget ? this.searchTarget.value.trim().toLowerCase() : '';

        this.rowTargets.forEach((row) => {
            const name = row.dataset.name || '';
            const states = (row.dataset.states || '').split(' ');
            const matchStatus = status === '' || states.includes(status);
            const matchSearch = query === '' || name.includes(query);
            row.hidden = !(matchStatus && matchSearch);
        });
    }

    toggleAll() {
        const checked = this.hasSelectAllTarget && this.selectAllTarget.checked;
        this.checkboxTargets.forEach((box) => {
            if (this.#rowHidden(box)) {
                return; // ne pas sélectionner les lignes masquées par le filtre
            }
            box.checked = checked;
        });
    }

    async remind() {
        const ids = this.#selectedIds();
        if (ids.length === 0) {
            this.#feedback('Sélectionnez au moins un collaborateur.');
            return;
        }

        const body = new URLSearchParams();
        body.append('_token', this.tokenValue);
        ids.forEach((id) => body.append('userIds[]', id));

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok) {
                this.#feedback(data.message ?? 'Relance envoyée.');
                this.#clearSelection();
            } else {
                this.#feedback(data.error ?? 'La relance a échoué. Réessayez.');
            }
        } catch {
            this.#feedback('Erreur réseau : réessayez.');
        }
    }

    #selectedIds() {
        return this.checkboxTargets
            .filter((box) => box.checked && !this.#rowHidden(box))
            .map((box) => box.value);
    }

    #rowHidden(box) {
        const row = box.closest('[data-completeness-target="row"]');
        return row !== null && row.hidden;
    }

    #clearSelection() {
        this.checkboxTargets.forEach((box) => { box.checked = false; });
        if (this.hasSelectAllTarget) {
            this.selectAllTarget.checked = false;
        }
    }

    #feedback(message) {
        if (this.hasFeedbackTarget) {
            this.feedbackTarget.textContent = message;
        }
    }
}
