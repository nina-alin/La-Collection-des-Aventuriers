import { Controller } from '@hotwired/stimulus';

const DEBOUNCE_MS = 280;

export default class extends Controller {
    static targets  = ['modal', 'searchInput', 'list', 'confirmBtn'];
    static values   = { searchUrl: String };

    connect() {
        this._scope         = 'ALL';
        this._debounceTimer = null;
        this._abortCtrl     = null;
        this._onKeyDown     = this._handleKeyDown.bind(this);
    }

    disconnect() {
        clearTimeout(this._debounceTimer);
        this._abortCtrl?.abort();
        document.removeEventListener('keydown', this._onKeyDown);
    }

    open() {
        this.modalTarget.classList.add('is-open');
        document.addEventListener('keydown', this._onKeyDown);
        this.searchInputTarget.focus();
        this._doSearch(this.searchInputTarget.value.trim());
    }

    close() {
        this.modalTarget.classList.remove('is-open');
        document.removeEventListener('keydown', this._onKeyDown);
        this.searchInputTarget.value = '';
        this._scope = 'ALL';
        this._setScopeButtons('ALL');
    }

    setScope(event) {
        this._scope = event.currentTarget.dataset.scope;
        this._setScopeButtons(this._scope);
        this._doSearch(this.searchInputTarget.value.trim());
    }

    search(event) {
        clearTimeout(this._debounceTimer);
        const q = event.target.value.trim();
        this._debounceTimer = setTimeout(() => this._doSearch(q), DEBOUNCE_MS);
    }

    async _doSearch(q) {
        this._abortCtrl?.abort();
        this._abortCtrl = new AbortController();

        const url = `${this.searchUrlValue}?q=${encodeURIComponent(q)}&scope=${this._scope}`;
        try {
            const res = await fetch(url, { signal: this._abortCtrl.signal });
            if (!res.ok) return;
            const data = await res.json();
            this._renderItems(data.results ?? [], !!data.default);
        } catch (err) {
            if (err.name !== 'AbortError') this._renderItems([], false);
        }
    }

    _renderItems(items, isDefault = false) {
        const list = this.listTarget;
        list.innerHTML = '';

        if (items.length === 0) {
            list.classList.add('is-empty');
            const empty = document.createElement('div');
            empty.className = 'fp-empty';
            empty.textContent = 'Aucun résultat pour cette recherche.';
            list.appendChild(empty);
            return;
        }

        list.classList.remove('is-empty');
        const group = document.createElement('div');
        group.className = 'fp-group';
        group.textContent = isDefault ? 'Suggestions populaires' : 'Tous les résultats';
        list.appendChild(group);

        items.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fp-item';
            btn.dataset.id   = item.id;
            btn.dataset.type = item.type;

            const isBook    = item.type === 'BOOK';
            const typeLabel = { BOOK: 'Livre', AUTHOR: 'Auteur', ILLUSTRATOR: 'Illustrateur', TRADUCTOR: 'Traducteur', EDITOR: 'Éditeur', COLLECTION: 'Collection' }[item.type] ?? item.type;

            btn.innerHTML = `
              <div class="fp-thumb${isBook ? '' : ' round'}">
                ${item.thumb
                    ? `<img src="${item.thumb}" alt="" />`
                    : `<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>`}
              </div>
              <div class="fp-meta">
                <div class="fp-name">${this._highlight(item.label)}</div>
                ${item.subtitle ? `<div class="fp-sub2">${item.subtitle}</div>` : ''}
              </div>
              <span class="fp-badge">${typeLabel}</span>
            `;

            btn.addEventListener('click', () => this._select(item));
            list.appendChild(btn);
        });
    }

    _highlight(text) {
        const q = this.searchInputTarget.value.trim();
        if (!q) return text;
        const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>');
    }

    _select(item) {
        this.listTarget.querySelectorAll('.fp-item').forEach(el => el.classList.remove('is-current'));
        const btn = this.listTarget.querySelector(`[data-id="${item.id}"]`);
        btn?.classList.add('is-current');

        const trigger = this.confirmBtnTarget;
        trigger.dataset.liveTypeParam = item.type;
        trigger.dataset.liveIdParam   = item.id;
        trigger.click();

        setTimeout(() => this.close(), 220);
    }

    _setScopeButtons(scope) {
        this.element.querySelectorAll('.fp-scope button').forEach(btn => {
            btn.setAttribute('aria-pressed', btn.dataset.scope === scope ? 'true' : 'false');
        });
    }

    _handleKeyDown(e) {
        if (e.key === 'Escape') this.close();
        if (e.key === 'Enter') {
            const first = this.listTarget.querySelector('.fp-item');
            first?.click();
        }
    }
}
