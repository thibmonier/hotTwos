/**
 * ThemeToggleController — bascule de thème (US-086, socle tailsfadmin).
 *
 * Stratégie alignée sur le bundle tailsfadmin : classe `.dark` sur <html>.
 *   - 3 états : système (défaut) → clair → sombre → système…
 *   - Défaut = préférence système (prefers-color-scheme) ; aucune clé stockée.
 *   - Persiste un choix explicite en localStorage (clé : 'theme' = 'dark' | 'light').
 *   - En mode système, suit les changements de préférence OS en direct.
 *
 * L'anti-FOUC du layout du bundle pose déjà `.dark` avant le premier paint
 * (dark si 'theme'==='dark' ou (aucun choix ET OS sombre)).
 */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this._mq = window.matchMedia('(prefers-color-scheme: dark)');
        this._onSystem = () => {
            // Ne réagir aux changements OS qu'en mode système (aucun choix explicite).
            if (this.#stored() === null) {
                this.#apply();
            }
        };
        this._mq.addEventListener('change', this._onSystem);
        this.#apply();
    }

    disconnect() {
        if (this._mq && this._onSystem) {
            this._mq.removeEventListener('change', this._onSystem);
        }
    }

    /**
     * Bascule le thème. Appelé via data-action="click->theme-toggle#toggle".
     * Cycle : système → opposé du système → retour au système.
     */
    toggle() {
        const stored = this.#stored();
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let next;
        if (stored === null) {
            next = systemDark ? 'light' : 'dark';
        } else if (stored === 'dark') {
            next = 'light';
        } else {
            next = 'dark';
        }

        // Si le prochain état correspond à la préférence système → retour au défaut système.
        const nextIsSystem = (next === 'dark') === systemDark;

        try {
            if (nextIsSystem) {
                localStorage.removeItem('theme');
            } else {
                localStorage.setItem('theme', next);
            }
        } catch (e) {
            /* stockage indisponible — on applique quand même l'état visuel */
        }

        this.#apply();
    }

    /** Applique la classe `.dark` selon l'état effectif et met à jour l'icône. */
    #apply() {
        const stored = this.#stored();
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = stored === 'dark' || (stored === null && systemDark);

        document.documentElement.classList.toggle('dark', dark);

        this.element.innerHTML = dark ? this.#sunIcon() : this.#moonIcon();
        this.element.setAttribute(
            'aria-label',
            dark ? 'Passer en mode clair' : 'Passer en mode sombre',
        );
    }

    /** @returns {'dark'|'light'|null} choix explicite stocké, ou null (système). */
    #stored() {
        try {
            const t = localStorage.getItem('theme');
            return t === 'dark' || t === 'light' ? t : null;
        } catch (e) {
            return null;
        }
    }

    #moonIcon() {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>`;
    }

    #sunIcon() {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="5"/>
            <line x1="12" y1="1" x2="12" y2="3"/>
            <line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
            <line x1="1" y1="12" x2="3" y2="12"/>
            <line x1="21" y1="12" x2="23" y2="12"/>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>`;
    }
}
