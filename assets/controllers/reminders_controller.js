import { Controller } from '@hotwired/stimulus';

/*
 * US-056 — écran de paramétrage des relances. Amélioration progressive uniquement : reflète l'état
 * de l'interrupteur maître (« Activer les relances ») sur les champs dépendants (délai, fréquence,
 * canal, escalade) en les désactivant nativement quand les relances sont coupées. Le formulaire
 * reste pleinement fonctionnel sans JavaScript (soumission serveur POST-Redirect-Get).
 */
export default class extends Controller {
    static targets = ['master', 'dependent'];

    connect() {
        this.toggle();
    }

    toggle() {
        if (!this.hasMasterTarget) {
            return;
        }
        const disabled = !this.masterTarget.checked;
        this.dependentTargets.forEach((fieldset) => {
            fieldset.disabled = disabled;
        });
    }
}
