import { Controller } from '@hotwired/stimulus';

/*
 * US-059 — panneau « Ma synthèse » ouvert depuis l'écran de saisie, en 1 clic, sans navigation.
 * Contenu rendu côté serveur (lecture seule) : ce contrôleur ne fait qu'ouvrir/fermer le <dialog>
 * natif (piège de focus + Échap gérés par le navigateur) et **restaurer le focus** sur le déclencheur
 * à la fermeture (CA-5). Il ne touche jamais au formulaire de saisie : les valeurs restent intactes.
 */
export default class extends Controller {
    static targets = ['dialog', 'trigger'];

    connect() {
        this.onClose = () => this.#restoreFocus();
        if (this.hasDialogTarget) {
            this.dialogTarget.addEventListener('close', this.onClose);
        }
    }

    disconnect() {
        if (this.hasDialogTarget) {
            this.dialogTarget.removeEventListener('close', this.onClose);
        }
    }

    open() {
        if (this.hasDialogTarget && typeof this.dialogTarget.showModal === 'function' && !this.dialogTarget.open) {
            this.dialogTarget.showModal();
        }
    }

    close() {
        if (this.hasDialogTarget && this.dialogTarget.open) {
            this.dialogTarget.close();
        }
    }

    #restoreFocus() {
        if (this.hasTriggerTarget) {
            this.triggerTarget.focus();
        }
    }
}
