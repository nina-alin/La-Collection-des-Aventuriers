import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'catalogueView';

export default class extends Controller {
    static targets = ['grid'];

    connect() {
        if (this.hasGridTarget) {
            this._applyMode('grid');
            this._updateButtons('grid');
        }
    }

    setGrid() {
        sessionStorage.setItem(STORAGE_KEY, 'grid');
        this._applyMode('grid');
        this._updateButtons('grid');
    }

    setList() {
        sessionStorage.setItem(STORAGE_KEY, 'list');
        this._applyMode('list');
        this._updateButtons('list');
    }

    toggleRail(event) {
        const btn       = event.currentTarget;
        // this.element IS the .catalogue div
        const catalogue = this.element;
        const collapsed = catalogue.classList.toggle('is-rail-collapsed');
        btn.setAttribute('aria-expanded', String(!collapsed));
    }

    _applyMode(mode) {
        const grid = this.hasGridTarget
            ? this.gridTarget
            : document.getElementById('grid');
        if (!grid) return;
        grid.classList.toggle('list-view', mode === 'list');
        grid.classList.remove(mode === 'list' ? 'grid-view' : 'list-view');
    }

    _updateButtons(mode) {
        document.querySelectorAll('[data-action*="catalogue-view#setGrid"]')
            .forEach(btn => btn.setAttribute('aria-pressed', String(mode === 'grid')));
        document.querySelectorAll('[data-action*="catalogue-view#setList"]')
            .forEach(btn => btn.setAttribute('aria-pressed', String(mode === 'list')));
    }
}
