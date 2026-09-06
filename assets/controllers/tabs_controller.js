import { Controller } from '@hotwired/stimulus';

/*
 * US-030 — onglets accessibles (pattern WAI-ARIA APG Tabs) pour le détail projet. Un seul panneau
 * visible à la fois ; l'onglet actif porte aria-selected + tabindex=0, les autres tabindex=-1.
 * Navigation clavier : flèches gauche/droite, Home, End. Aucune règle métier — présentation seule.
 */
export default class extends Controller {
    static targets = ['tab', 'panel'];

    // T-R01 — normalise l'état initial au montage : exactement un onglet actif (celui marqué
    // aria-selected, sinon le premier) et son panneau visible, tous les autres masqués. Corrige le
    // « 1er clic sans effet » quand des onglets/panneaux sont rendus conditionnellement (état HTML
    // initial potentiellement désaligné avec les cibles Stimulus réellement présentes).
    connect() {
        if (this.tabTargets.length === 0) {
            return;
        }
        const active = this.tabTargets.find((t) => t.getAttribute('aria-selected') === 'true') ?? this.tabTargets[0];
        this.#activate(active);
    }

    select(event) {
        this.#activate(event.currentTarget);
    }

    keydown(event) {
        const keys = { ArrowRight: 1, ArrowLeft: -1 };
        if (event.key in keys) {
            event.preventDefault();
            const tabs = this.tabTargets;
            const current = tabs.indexOf(event.currentTarget);
            const next = (current + keys[event.key] + tabs.length) % tabs.length;
            this.#activate(tabs[next], true);
        } else if (event.key === 'Home') {
            event.preventDefault();
            this.#activate(this.tabTargets[0], true);
        } else if (event.key === 'End') {
            event.preventDefault();
            this.#activate(this.tabTargets[this.tabTargets.length - 1], true);
        }
    }

    #activate(tab, focus = false) {
        this.tabTargets.forEach((t) => {
            const selected = t === tab;
            t.setAttribute('aria-selected', selected ? 'true' : 'false');
            t.tabIndex = selected ? 0 : -1;
        });
        const controlled = tab.getAttribute('aria-controls');
        this.panelTargets.forEach((panel) => {
            panel.hidden = panel.id !== controlled;
        });
        if (focus) {
            tab.focus();
        }
    }
}
