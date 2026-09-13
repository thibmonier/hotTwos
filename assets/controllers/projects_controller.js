import { Controller } from '@hotwired/stimulus';

/*
 * US-097 (CPL-05) — liste des projets : filtre par statut + recherche par code/nom,
 * filtrage client sans rechargement. Aucune logique métier (présentation seule).
 */
export default class extends Controller {
    static targets = ['row', 'status', 'search'];

    filter() {
        const status = this.hasStatusTarget ? this.statusTarget.value : '';
        const query = this.hasSearchTarget ? this.searchTarget.value.trim().toLowerCase() : '';

        this.rowTargets.forEach((row) => {
            const matchStatus = status === '' || (row.dataset.status || '') === status;
            const matchSearch = query === '' || (row.dataset.search || '').includes(query);
            row.hidden = !(matchStatus && matchSearch);
        });
    }
}
