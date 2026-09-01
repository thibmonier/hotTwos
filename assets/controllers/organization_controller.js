import { Controller } from '@hotwired/stimulus';

/*
 * US-010 — administration de la hiérarchie : création/désactivation d'unités et rattachement
 * de collaborateurs via l'API (/api/org-units, /api/org-memberships). L'habilitation ADMIN et
 * les règles métier (cycle, chevauchement) sont vérifiées côté serveur (ARC-106) ; l'UI présente
 * et relaie les erreurs (403/422).
 */
export default class extends Controller {
    static targets = ['unitName', 'unitParent', 'userId', 'attachUnit', 'effectiveFrom', 'status'];

    async createUnit(event) {
        event.preventDefault();
        const name = this.unitNameTarget.value.trim();
        if (name === '') {
            this.#status("Le nom de l'unité est obligatoire.");
            return;
        }
        const parentId = this.hasUnitParentTarget && this.unitParentTarget.value !== '' ? this.unitParentTarget.value : null;

        await this.#send('/api/org-units', 'POST', { name, parentId }, 'Unité créée.');
    }

    async deactivate(event) {
        const id = event.currentTarget.dataset.orgId;
        await this.#send(`/api/org-units/${id}`, 'DELETE', null, 'Unité désactivée.');
    }

    async attach(event) {
        event.preventDefault();
        const userId = this.userIdTarget.value.trim();
        const orgUnitId = this.attachUnitTarget.value;
        const effectiveFrom = this.effectiveFromTarget.value;
        if (userId === '' || orgUnitId === '' || effectiveFrom === '') {
            this.#status('Collaborateur, unité et date de début sont obligatoires.');
            return;
        }

        await this.#send('/api/org-memberships', 'POST', { userId, orgUnitId, effectiveFrom }, 'Collaborateur rattaché.', false);
    }

    async #send(url, method, payload, successMessage, reload = true) {
        try {
            const options = { method, headers: { Accept: 'application/json' } };
            if (payload !== null) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(payload);
            }
            const response = await fetch(url, options);

            if (response.ok) {
                if (reload) {
                    window.location.reload();
                    return;
                }
                this.#status(successMessage);
            } else if (response.status === 403) {
                this.#status("Refusé : action réservée à l'administrateur.");
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
