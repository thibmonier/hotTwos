import { Controller } from '@hotwired/stimulus';

/*
 * US-052 — saisie quotidienne mobile. Vue web responsive au-dessus de l'API de saisie US-050 :
 * soumission par lot (/api/time-entries/week), total du jour en direct, repli hors-ligne en
 * localStorage sans perte + resynchronisation au retour réseau, reprise de la veille, et navigation
 * jour par swipe (raccourci — les flèches restent le chemin accessible). Aucune règle métier ici.
 */
export default class extends Controller {
    static targets = ['entry', 'comment', 'total', 'offlineBanner', 'resyncBanner', 'submit', 'status'];
    static values = { date: String, prev: String, next: String };

    connect() {
        this.onOnline = () => this.#refreshConnectivity();
        this.onOffline = () => this.#refreshConnectivity();
        window.addEventListener('online', this.onOnline);
        window.addEventListener('offline', this.onOffline);

        this.touchStartX = null;
        this.element.addEventListener('touchstart', (e) => this.#onTouchStart(e), { passive: true });
        this.element.addEventListener('touchend', (e) => this.#onTouchEnd(e), { passive: true });

        this.recalculate();
        this.#refreshConnectivity();
    }

    disconnect() {
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
    }

    recalculate() {
        const total = this.entryTargets.reduce((sum, input) => {
            const minutes = Number.parseInt(input.value, 10);
            return sum + (Number.isInteger(minutes) && minutes > 0 ? minutes : 0);
        }, 0);
        if (this.hasTotalTarget) {
            this.totalTarget.textContent = `Total du jour : ${this.#format(total)}`;
        }
    }

    async submitDay() {
        const entries = this.#collect();
        if (entries.length === 0) {
            this.#status('Rien à enregistrer.');
            return;
        }

        this.#toggleSubmit(false, 'Envoi…');
        try {
            const response = await fetch('/api/time-entries/week', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entries }),
            });
            const body = await response.json().catch(() => ({}));
            const errors = body.errors ?? [];
            this.#status(`Journée enregistrée : ${body.recorded ?? 0} imputation(s)${errors.length ? `, ${errors.length} refusée(s)` : ''}.`);
            this.#clearLocal();
        } catch {
            // Réseau indisponible : aucune perte, on conserve localement et on informe.
            this.#saveLocal(entries);
            this.#status('Saisie conservée sur cet appareil : elle sera synchronisée au retour du réseau.');
        } finally {
            this.#toggleSubmit(true, 'Enregistrer la journée');
        }
    }

    async resync() {
        const pending = this.#readLocal();
        if (pending.length === 0) {
            this.#hide(this.resyncBannerTarget);
            return;
        }
        try {
            const response = await fetch('/api/time-entries/week', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entries: pending }),
            });
            if (response.ok) {
                this.#clearLocal();
                this.#hide(this.resyncBannerTarget);
                this.#status('Saisies synchronisées.');
            } else {
                this.#status('Échec de synchronisation : réessayez.');
            }
        } catch {
            this.#status('Toujours hors ligne : synchronisation impossible pour l\'instant.');
        }
    }

    duplicatePrevious() {
        const hasValues = this.entryTargets.some((input) => Number.parseInt(input.value, 10) > 0);
        if (hasValues && !window.confirm('Remplacer la saisie actuelle par celle de la veille ?')) {
            return;
        }
        this.entryTargets.forEach((input) => {
            const previous = input.dataset.previousMinutes;
            input.value = previous && Number.parseInt(previous, 10) > 0 ? previous : '';
        });
        this.recalculate();
        this.#status('Saisie de la veille reprise — pensez à enregistrer.');
    }

    #collect() {
        const comments = new Map(this.commentTargets.map((c) => [c.dataset.projectId, c.value.trim()]));
        return this.entryTargets
            .map((input) => {
                const minutes = Number.parseInt(input.value, 10);
                const comment = comments.get(input.dataset.projectId) || null;
                return { projectId: input.dataset.projectId, date: this.dateValue, minutes, comment };
            })
            .filter((entry) => Number.isInteger(entry.minutes) && entry.minutes > 0);
    }

    #refreshConnectivity() {
        const offline = navigator.onLine === false;
        this.#setHidden(this.offlineBannerTarget, !offline);
        const pending = this.#readLocal().length > 0;
        this.#setHidden(this.resyncBannerTarget, !(pending && !offline));
    }

    #storageKey() {
        return `timesheet-day:${this.dateValue}`;
    }

    #saveLocal(entries) {
        try {
            window.localStorage.setItem(this.#storageKey(), JSON.stringify(entries));
        } catch {
            /* stockage indisponible (navigation privée) : dégradation silencieuse */
        }
        if (this.hasOfflineBannerTarget) {
            this.#show(this.offlineBannerTarget);
        }
    }

    #readLocal() {
        try {
            const raw = window.localStorage.getItem(this.#storageKey());
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    #clearLocal() {
        try {
            window.localStorage.removeItem(this.#storageKey());
        } catch {
            /* rien à nettoyer */
        }
        if (this.hasOfflineBannerTarget) {
            this.#hide(this.offlineBannerTarget);
        }
        if (this.hasResyncBannerTarget) {
            this.#hide(this.resyncBannerTarget);
        }
    }

    #onTouchStart(event) {
        const touch = event.changedTouches[0];
        // Ignore les gestes démarrant près du bord (conflit avec le « retour » du navigateur).
        this.touchStartX = touch.clientX < 24 || touch.clientX > window.innerWidth - 24 ? null : touch.clientX;
        this.touchStartY = touch.clientY;
    }

    #onTouchEnd(event) {
        if (this.touchStartX === null) {
            return;
        }
        const touch = event.changedTouches[0];
        const dx = touch.clientX - this.touchStartX;
        const dy = touch.clientY - this.touchStartY;
        // Swipe horizontal franc uniquement (évite de déclencher pendant un scroll vertical).
        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 2) {
            window.location.href = `/saisie/jour/${dx < 0 ? this.nextValue : this.prevValue}`;
        }
    }

    #toggleSubmit(enabled, label) {
        if (!this.hasSubmitTarget) {
            return;
        }
        this.submitTarget.disabled = !enabled;
        this.submitTarget.textContent = label;
    }

    #format(minutes) {
        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;
        return rest === 0 ? `${hours}h` : `${hours}h${String(rest).padStart(2, '0')}`;
    }

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }

    #show(el) {
        this.#setHidden(el, false);
    }

    #hide(el) {
        this.#setHidden(el, true);
    }

    #setHidden(el, hidden) {
        if (el) {
            el.hidden = hidden;
        }
    }
}
