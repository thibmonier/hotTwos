import { Controller } from '@hotwired/stimulus';

/*
 * US-054 — module « Mes absences » : déclaration d'une absence via l'API (/api/absences).
 * Les règles (type, dates, durée) sont vérifiées côté serveur (ARC-19) ; l'UI relaie les erreurs
 * (401/422). Aucune donnée médicale n'est collectée (HAB-3).
 */
export default class extends Controller {
    static targets = ['type', 'startDate', 'endDate', 'startsMorning', 'endsAfternoon', 'comment', 'status'];

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

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
