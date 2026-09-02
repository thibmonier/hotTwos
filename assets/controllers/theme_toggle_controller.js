/**
 * ThemeToggleController — US-063, T-063-04
 *
 * Gère la bascule thème clair/sombre.
 *
 * Logique :
 *   - Pose data-theme ET data-bs-theme sur <html> (synchronisés — requis par Bootstrap 5.3)
 *   - Persiste le choix en localStorage (clé : 'theme')
 *   - Défaut = préférence système (prefers-color-scheme) — aucun attribut posé
 *   - Cycle : système → opposé du système → retour système (si l'opposé correspond au système suivant)
 *
 * Le script early-init dans <head> lit localStorage avant le rendu pour éviter le FOUC.
 */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.updateIcon();

        // Écouter les changements de préférence système pour mettre à jour l'icône
        this._mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this._onSystemChange = () => this.updateIcon();
        this._mediaQuery.addEventListener('change', this._onSystemChange);
    }

    disconnect() {
        if (this._mediaQuery && this._onSystemChange) {
            this._mediaQuery.removeEventListener('change', this._onSystemChange);
        }
    }

    /**
     * Bascule le thème.
     * Appelé via data-action="click->theme-toggle#toggle"
     */
    toggle() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme'); // 'dark' | 'light' | null
        const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let next;

        if (!current) {
            // Défaut système → basculer vers l'opposé
            next = systemIsDark ? 'light' : 'dark';
        } else if (current === 'dark') {
            next = 'light';
        } else {
            next = 'dark';
        }

        // Si next correspond déjà à la préférence système → retour au défaut (pas d'attribut)
        const nextMatchesSystem = (next === 'dark' && systemIsDark) || (next === 'light' && !systemIsDark);

        if (nextMatchesSystem) {
            html.removeAttribute('data-theme');
            html.removeAttribute('data-bs-theme');
            localStorage.removeItem('theme');
        } else {
            html.setAttribute('data-theme', next);
            html.setAttribute('data-bs-theme', next); // Bootstrap 5.3 sync obligatoire
            localStorage.setItem('theme', next);
        }

        this.updateIcon();
    }

    /**
     * Met à jour l'icône du bouton selon le thème effectif.
     * Lune = mode sombre actif ou préférence système sombre
     * Soleil = mode clair actif ou préférence système clair
     */
    updateIcon() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        const effectiveDark = current === 'dark' || (!current && systemIsDark);

        // SVG lune (mode sombre) ou soleil (mode clair)
        this.element.innerHTML = effectiveDark
            ? this._sunIcon()
            : this._moonIcon();

        // Mise à jour de l'aria-label
        this.element.setAttribute(
            'aria-label',
            effectiveDark ? 'Passer en mode clair' : 'Passer en mode sombre'
        );
    }

    _moonIcon() {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>`;
    }

    _sunIcon() {
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
