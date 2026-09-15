import { Controller } from '@hotwired/stimulus';

/*
 * US-103 (G3) — liste des clients : recherche par nom/SIREN sans rechargement.
 * Aucune logique métier (présentation seule).
 */
export default class extends Controller {
    static targets = ['row', 'search'];

    filter() {
        const query = this.hasSearchTarget ? this.searchTarget.value.trim().toLowerCase() : '';

        this.rowTargets.forEach((row) => {
            row.hidden = query !== '' && !(row.dataset.search || '').includes(query);
        });
    }
}
