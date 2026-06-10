import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['grid', 'searchInput', 'searchDropdown', 'viewToggle'];
    static values  = { searchUrl: String };

    // ---- View toggle ----

    toggleView(event) {
        const view = event.currentTarget.dataset.view;
        this.#applyView(view);
        localStorage.setItem('lca-createurs-view', view);
    }

    #applyView(view) {
        if (!this.hasGridTarget) return;
        if (view === 'list') {
            this.gridTarget.classList.add('is-list');
        } else {
            this.gridTarget.classList.remove('is-list');
        }
        this.viewToggleTargets.forEach(btn => {
            btn.setAttribute('aria-pressed', btn.dataset.view === view ? 'true' : 'false');
        });
    }

    // ---- Autocomplete search ----

    #debounceTimer = null;
    #abortController = null;

    search(event) {
        clearTimeout(this.#debounceTimer);
        this.#debounceTimer = setTimeout(() => this.#doSearch(event.target.value.trim()), 250);
    }

    async #doSearch(q) {
        if (!this.hasSearchDropdownTarget) return;

        if (q.length === 0) {
            this.#hideDropdown();
            return;
        }

        if (this.#abortController) {
            this.#abortController.abort();
        }
        this.#abortController = new AbortController();

        try {
            const url = `${this.searchUrlValue}?q=${encodeURIComponent(q)}`;
            const resp = await fetch(url, { signal: this.#abortController.signal });
            const data = await resp.json();
            this.#renderDropdown(data, q);
        } catch (err) {
            if (err.name !== 'AbortError') {
                this.#hideDropdown();
            }
        }
    }

    #renderDropdown(data, q) {
        const dropdown = this.searchDropdownTarget;
        const roleLabels = {
            auteur: 'AUTEURS',
            traducteur: 'TRADUCTEURS',
            illustrateur: 'ILLUSTRATEURS',
        };
        const detailPaths = {
            auteur: '/authors/',
            traducteur: '/traductors/',
            illustrateur: '/illustrators/',
        };

        let html = '';
        let totalResults = 0;

        for (const [roleKey, items] of Object.entries(data)) {
            if (!Array.isArray(items) || items.length === 0) continue;
            totalResults += items.length;
            html += `<div class="creator-search-dropdown__group">`;
            html += `<div class="group-label">${roleLabels[roleKey] ?? roleKey}</div>`;
            for (const item of items) {
                const initials = (item.firstName?.[0] ?? '') + (item.lastName?.[0] ?? '');
                const name = this.#highlight(`${item.firstName} ${item.lastName}`, q);
                const sub = [
                    item.mainCollection,
                    item.bookCount ? `${item.bookCount} ouvrage${item.bookCount > 1 ? 's' : ''}` : null,
                    item.averageScore ? `★ ${item.averageScore}` : null,
                ].filter(Boolean).join(' · ');
                html += `<a class="creator-search-dropdown__item" href="${detailPaths[item.role] ?? '/'}${item.slug}">
                    <span class="item-avatar">${initials}</span>
                    <span>
                        <span class="item-name">${name}</span>
                        ${sub ? `<span class="item-sub">${sub}</span>` : ''}
                    </span>
                </a>`;
            }
            html += `</div>`;
        }

        if (totalResults === 0) {
            html = `<div class="no-results">Aucun résultat pour « ${this.#escapeHtml(q)} »</div>`;
        }

        dropdown.innerHTML = html;
        dropdown.classList.remove('d-none');
    }

    clearSearch() {
        this.#hideDropdown();
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = '';
        }
    }

    #hideDropdown() {
        if (this.hasSearchDropdownTarget) {
            this.searchDropdownTarget.classList.add('d-none');
            this.searchDropdownTarget.innerHTML = '';
        }
    }

    #highlight(text, q) {
        if (!q) return this.#escapeHtml(text);
        const escaped = this.#escapeRegex(q);
        return this.#escapeHtml(text).replace(
            new RegExp(`(${escaped})`, 'gi'),
            '<mark>$1</mark>'
        );
    }

    #escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    #escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Close dropdown on outside click
    #outsideClickHandler = (e) => {
        if (!this.element.contains(e.target)) {
            this.#hideDropdown();
        }
    };

    connect() {
        const saved = localStorage.getItem('lca-createurs-view');
        if (saved === 'list') {
            this.#applyView('list');
        }
        document.addEventListener('click', this.#outsideClickHandler);
    }

    disconnect() {
        document.removeEventListener('click', this.#outsideClickHandler);
        if (this.#abortController) {
            this.#abortController.abort();
        }
    }
}
