/**
 * SidebarToggleController — US-063, T-063-03
 *
 * Gère le drawer sidebar sur mobile (< 768px).
 *
 * Fermeture sur :
 *   - Clic sur l'overlay
 *   - Touche Escape
 *   - Navigation Turbo (turbo:before-visit)
 *
 * Mise à jour aria-expanded sur le bouton hamburger.
 */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'overlay', 'button'];

    connect() {
        // Fermer le drawer sur navigation Turbo
        this._onTurboVisit = () => this.close();
        document.addEventListener('turbo:before-visit', this._onTurboVisit);

        // Fermer sur Escape
        this._onKeydown = (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        };
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this._onTurboVisit);
        document.removeEventListener('keydown', this._onKeydown);
    }

    /**
     * Bascule l'état ouvert/fermé du drawer.
     * Appelé via data-action="click->sidebar-toggle#toggle"
     */
    toggle() {
        if (this.hasSidebarTarget && this.sidebarTarget.classList.contains('is-open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.add('is-open');
        }
        if (this.hasOverlayTarget) {
            this.overlayTarget.classList.add('is-open');
        }
        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'true');
            this.buttonTarget.setAttribute('aria-label', 'Fermer le menu');
        }
        // Empêcher le scroll body quand le drawer est ouvert
        document.body.style.overflow = 'hidden';
    }

    close() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.remove('is-open');
        }
        if (this.hasOverlayTarget) {
            this.overlayTarget.classList.remove('is-open');
        }
        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'false');
            this.buttonTarget.setAttribute('aria-label', 'Ouvrir le menu');
        }
        document.body.style.overflow = '';
    }

    /**
     * Ferme le drawer sur clic de l'overlay.
     * Appelé via data-action="click->sidebar-toggle#closeOverlay"
     */
    closeOverlay() {
        this.close();
    }
}
