import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'dropdown'];
    static values  = { suggestionsUrl: String };

    _timer = null;

    connect() {
        this.inputTarget.addEventListener('input', this._onInput);
        this.inputTarget.addEventListener('keydown', this._onKeydown);
        document.addEventListener('click', this._onOutsideClick);
    }

    disconnect() {
        this.inputTarget.removeEventListener('input', this._onInput);
        this.inputTarget.removeEventListener('keydown', this._onKeydown);
        document.removeEventListener('click', this._onOutsideClick);
    }

    _onInput = () => {
        clearTimeout(this._timer);
        const q = this.inputTarget.value.trim();
        if (q.length < 1) {
            this._hideDropdown();
            return;
        }
        this._timer = setTimeout(() => this._fetchSuggestions(q), 200);
    };

    _onKeydown = (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const q = this.inputTarget.value.trim();
            if (q.length > 0) {
                this._applySearch(q);
            }
        } else if (event.key === 'Escape') {
            this._hideDropdown();
        }
    };

    _onOutsideClick = (event) => {
        if (!this.element.contains(event.target)) {
            this._hideDropdown();
        }
    };

    async _fetchSuggestions(q) {
        try {
            const url  = `${this.suggestionsUrlValue}?q=${encodeURIComponent(q)}`;
            const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) { this._hideDropdown(); return; }
            const data = await res.json();
            this._renderDropdown(data, q);
        } catch {
            this._hideDropdown();
        }
    }

    _renderDropdown(data, q) {
        const { books = [], authors = [] } = data;
        if (!books.length && !authors.length) { this._hideDropdown(); return; }

        const dropdown = this.dropdownTarget;
        dropdown.innerHTML = '';

        if (books.length) {
            const lbl = document.createElement('div');
            lbl.className = 'group-label';
            lbl.textContent = `LIVRES · ${books.length} RÉSULTAT${books.length > 1 ? 'S' : ''}`;
            dropdown.appendChild(lbl);

            books.forEach(b => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'catalog-suggest';
                const sub = [b.author, b.year, b.collectionLabel].filter(Boolean).join(' · ');
                btn.innerHTML = `
                    <div class="mini-cover"></div>
                    <div class="s-info">
                        <div class="s-title">${this._highlight(b.title, q)}</div>
                        ${sub ? `<div class="s-sub">${this._esc(sub)}</div>` : ''}
                    </div>
                    ${b.rating != null ? `<span class="s-score">★ ${b.rating}</span>` : ''}
                `;
                btn.addEventListener('click', () => {
                    if (b.slug) { window.location.href = `/livre/${encodeURIComponent(b.slug)}`; }
                    else { this._applySearch(b.title); }
                });
                dropdown.appendChild(btn);
            });
        }

        if (authors.length) {
            const lbl = document.createElement('div');
            lbl.className = 'group-label';
            lbl.textContent = `AUTEURS · ${authors.length} RÉSULTAT${authors.length > 1 ? 'S' : ''}`;
            dropdown.appendChild(lbl);

            authors.forEach(a => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'catalog-suggest';
                const sub = a.bookCount
                    ? `${a.bookCount} ouvrage${a.bookCount > 1 ? 's' : ''}${a.yearRange ? ' · ' + a.yearRange : ''}`
                    : '';
                btn.innerHTML = `
                    <div class="mini-cover" style="border-radius:50%"></div>
                    <div class="s-info">
                        <div class="s-title">${this._highlight(a.name, q)}</div>
                        ${sub ? `<div class="s-sub">${this._esc(sub)}</div>` : ''}
                    </div>
                `;
                btn.addEventListener('click', () => this._applySearch(a.name));
                dropdown.appendChild(btn);
            });
        }

        dropdown.style.display = 'block';
        dropdown.removeAttribute('hidden');
    }

    _highlight(text, q) {
        if (!q) return this._esc(text);
        const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.split(re).map((part, i) =>
            i % 2 === 1 ? `<mark>${this._esc(part)}</mark>` : this._esc(part)
        ).join('');
    }

    _applySearch(q) {
        this._hideDropdown();
        const params = new URLSearchParams(window.location.search);
        params.set('q', q);
        params.delete('page');
        window.location.href = `/catalogue?${params.toString()}`;
    }

    _hideDropdown() {
        this.dropdownTarget.style.display = 'none';
        this.dropdownTarget.innerHTML = '';
    }

    _esc(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}
