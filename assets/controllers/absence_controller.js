import { Controller } from '@hotwired/stimulus';

/*
 * US-054 — module « Mes absences » : déclaration d'une absence via l'API (/api/absences).
 * Les règles (type, dates, durée) sont vérifiées côté serveur (ARC-19) ; l'UI relaie les erreurs
 * (401/422). Aucune donnée médicale n'est collectée (HAB-3).
 *
 * US-091b (CA-2/CA-3) — à la sélection des dates, interroge /api/absences/impact pour afficher le
 * nombre de jours ouvrés concernés, le solde projeté après la demande, et un avertissement de conflit.
 */
export default class extends Controller {
    static targets = ['type', 'startDate', 'endDate', 'startsMorning', 'endsAfternoon', 'comment', 'status', 'impact', 'conflict', 'conflictMessage'];

    async declare(event) {
        event.preventDefault();
        const payload = {
            typeId: this.typeTarget.value,
            startDate: this.startDateTarget.value,
            endDate: this.endDateTarget.value,
            startsMorning: this.startsMorningTarget.checked,
            endsAfternoon: this.endsAfternoonTarget.checked,
            comment: this.commentTarget.value.trim() === '' ? null : this.commentTarget.value.trim(),
        };

        try {
            const response = await fetch('/api/absences', {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (response.ok) {
                window.location.reload();
                return;
            }
            const body = await response.json().catch(() => ({}));
            this.#status(body.error ?? 'Demande refusée par le serveur.');
        } catch {
            this.#status('Erreur réseau : réessayez.');
        }
    }

    async refreshImpact() {
        const from = this.startDateTarget.value;
        const to = this.endDateTarget.value;
        if (from === '' || to === '') {
            return;
        }

        try {
            const response = await fetch(`/api/absences/impact?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                return;
            }
            this.#renderImpact(await response.json());
        } catch {
            // Erreur réseau : on conserve le rappel de solde statique déjà affiché.
        }
    }

    #renderImpact(data) {
        if (this.hasImpactTarget) {
            const balance = String(data.projectedBalance).replace('.', ',');
            // Construction par le DOM (textContent) plutôt qu'innerHTML : aucune interpolation HTML.
            const strong = document.createElement('strong');
            strong.textContent = `${balance} j`;
            this.impactTarget.replaceChildren(
                document.createTextNode(`${data.businessDays} jours ouvrés · solde après validation : `),
                strong,
            );
        }
        if (!this.hasConflictTarget) {
            return;
        }
        if (data.hasConflict) {
            if (this.hasConflictMessageTarget) {
                this.conflictMessageTarget.textContent = `Conflit potentiel : ${data.conflictMessage}`;
            }
            this.conflictTarget.hidden = false;
        } else {
            this.conflictTarget.hidden = true;
        }
    }

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
