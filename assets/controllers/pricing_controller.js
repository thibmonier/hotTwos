import { Controller } from '@hotwired/stimulus';

/*
 * US-011 — administration des profils et tarifs : création/désactivation de profils, définition
 * d'entrées tarifaires et affichage de l'historique via l'API (/api/profiles, /api/profile-rates).
 * L'habilitation ADMIN et les règles (chevauchement, ≤ 0, rétroactif) sont vérifiées côté serveur
 * (ARC-106) ; l'UI présente et relaie les erreurs (403/422). La ligne en vigueur est mise en évidence.
 */
export default class extends Controller {
    static targets = [
        'profileName', 'profileMode', 'rateProfile', 'rateFrom', 'rateTo', 'rateCost', 'rateSelling',
        'rateConfirm', 'history', 'status',
    ];

    async createProfile(event) {
        event.preventDefault();
        const name = this.profileNameTarget.value.trim();
        if (name === '') {
            this.#status('Le nom du profil est obligatoire.');
            return;
        }
        await this.#send('/api/profiles', 'POST', { name, calculationMode: this.profileModeTarget.value }, true);
    }

    async deactivate(event) {
        await this.#send(`/api/profiles/${event.currentTarget.dataset.profileId}`, 'DELETE', null, true);
    }

    async defineRate(event) {
        event.preventDefault();
        const payload = {
            profileId: this.rateProfileTarget.value,
            effectiveFrom: this.rateFromTarget.value,
            effectiveTo: this.rateToTarget.value === '' ? null : this.rateToTarget.value,
            costPriceCents: Number.parseInt(this.rateCostTarget.value, 10),
            sellingPriceCents: Number.parseInt(this.rateSellingTarget.value, 10),
            confirmRetroactive: this.rateConfirmTarget.checked,
        };
        const ok = await this.#send('/api/profile-rates', 'POST', payload, false);
        if (ok) {
            this.#status('Tarif enregistré.');
            await this.#loadHistory(payload.profileId);
        }
    }

    async showHistory(event) {
        await this.#loadHistory(event.currentTarget.dataset.profileId);
    }

    async #loadHistory(profileId) {
        try {
            const response = await fetch(`/api/profile-rates?profileId=${encodeURIComponent(profileId)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                this.#status('Impossible de charger l\'historique.');
                return;
            }
            const rates = await response.json();
            this.#renderHistory(Array.isArray(rates) ? rates : []);
        } catch {
            this.#status('Erreur réseau : réessayez.');
        }
    }

    #renderHistory(rates) {
        if (!this.hasHistoryTarget) {
            return;
        }
        const today = new Date().toISOString().slice(0, 10);
        this.historyTarget.replaceChildren();
        rates.forEach((rate) => {
            const active = rate.effectiveFrom <= today && (rate.effectiveTo === null || today < rate.effectiveTo);
            const row = document.createElement('tr');
            if (active) {
                row.classList.add('rate-active');
                row.setAttribute('aria-current', 'true');
            }
            row.append(
                this.#cell(rate.effectiveFrom ?? ''),
                this.#cell(rate.effectiveTo ?? '—'),
                this.#cell(String(rate.costPriceCents ?? '')),
                this.#cell(String(rate.sellingPriceCents ?? '')),
            );
            this.historyTarget.append(row);
        });
    }

    #cell(text) {
        const cell = document.createElement('td');
        cell.textContent = text;
        return cell;
    }

    async #send(url, method, payload, reload) {
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
                }
                return true;
            }
            if (response.status === 403) {
                this.#status('Refusé : action réservée à l\'administrateur.');
            } else {
                const body = await response.json().catch(() => ({}));
                this.#status(body.error ?? 'Refusé par le serveur.');
            }
            return false;
        } catch {
            this.#status('Erreur réseau : réessayez.');
            return false;
        }
    }

    #status(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message;
        }
    }
}
