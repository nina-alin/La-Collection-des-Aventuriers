import { Controller } from '@hotwired/stimulus';

const DEBOUNCE_MS = 300;
const TIMEOUT_MS = 5000;

const TYPE_LABEL = { livre: 'Livre', collection: 'Collection', auteur: 'Auteur' };
// design CSS uses .type-pip.collec (not .collection)
const TYPE_CSS   = { livre: 'livre', collection: 'collec', auteur: 'auteur' };

export default class extends Controller {
    static targets = ['input', 'panel', 'head', 'results', 'foot', 'status'];
    static values  = {
        urlLivre:      { type: String, default: '/livre/__SLUG__' },
        urlCollection: { type: String, default: '/collections/__SLUG__' },
        urlAuteur:     { type: String, default: '/authors/__SLUG__' },
        catalogue:     { type: String, default: '/catalogue' },
    };

    connect() {
        this._debounceTimer   = null;
        this._abortCtrl       = null;
        this._popularAbortCtrl = null;
        this._activeIndex     = -1;
        this._history         = [];
        this._inResultsMode   = false;

        this._onDocClick = this._handleDocClick.bind(this);
        document.addEventListener('click', this._onDocClick);

        this.inputTarget.addEventListener('focus',   this._handleFocus.bind(this));
        this.inputTarget.addEventListener('input',   this._handleInput.bind(this));
        this.inputTarget.addEventListener('keydown', this._handleKeydown.bind(this));
    }

    disconnect() {
        document.removeEventListener('click', this._onDocClick);
        clearTimeout(this._debounceTimer);
        this._abortCtrl?.abort();
        this._popularAbortCtrl?.abort();
    }

    // ── Event handlers ────────────────────────────────────────────────────────

    _handleFocus() {
        this._openPanel();
        if (!this._inResultsMode) {
            this._buildHead(null, 0);
            this._buildFoot('');
            this._renderPresaisie();
        }
    }

    _handleInput(e) {
        clearTimeout(this._debounceTimer);
        const q = e.target.value;

        this._openPanel();

        if (q.trim() === '') {
            this._inResultsMode = false;
            this._activeIndex   = -1;
            this.inputTarget.setAttribute('aria-activedescendant', '');
            this._buildHead(null, 0);
            this._buildFoot('');
            this._renderPresaisie();
            return;
        }

        this._inResultsMode = true;
        this._activeIndex   = -1;
        this._popularAbortCtrl?.abort();
        this._buildHead(q.trim(), null);
        this._buildFoot(q.trim());
        this._renderSkeletons();
        this._debounceTimer = setTimeout(() => this._fetchResults(q.trim()), DEBOUNCE_MS);
    }

    _handleKeydown(e) {
        const items = this._getActiveItems();

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this._activeIndex = Math.min(this._activeIndex + 1, items.length - 1);
            this._syncActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this._activeIndex = Math.max(this._activeIndex - 1, -1);
            this._syncActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const active = items[this._activeIndex];
            if (active) {
                this._pushHistory(this.inputTarget.value.trim());
                window.location.href = active.href || active.dataset.href || '#';
            } else if (this.inputTarget.value.trim()) {
                this._pushHistory(this.inputTarget.value.trim());
                window.location.href = this.catalogueValue + '?q=' + encodeURIComponent(this.inputTarget.value.trim());
            }
        } else if (e.key === 'Escape') {
            this._closePanel();
            this.inputTarget.focus();
        }
    }

    _handleDocClick(e) {
        if (!this.element.contains(e.target)) {
            this._closePanel();
        }
    }

    // ── API fetches ───────────────────────────────────────────────────────────

    async _fetchResults(q) {
        this._abortCtrl?.abort();
        this._abortCtrl = new AbortController();
        const ctrl = this._abortCtrl;
        const timeoutId = setTimeout(() => ctrl.abort(), TIMEOUT_MS);

        try {
            const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`, { signal: ctrl.signal });
            clearTimeout(timeoutId);
            const data = res.ok ? await res.json() : { results: [] };
            this._activeIndex = -1;
            this.inputTarget.setAttribute('aria-activedescendant', '');
            this._buildHead(q, (data.results ?? []).length);
            this._renderResults(data.results ?? [], q);
        } catch (err) {
            clearTimeout(timeoutId);
            if (err.name !== 'AbortError') {
                this._buildHead(q, 0);
                this._renderResults([], q);
            }
        }
    }

    // ── Panel sections ────────────────────────────────────────────────────────

    _buildHead(query, count) {
        const hint = `<span class="search-dd-hint">Navigation <kbd>↑</kbd><kbd>↓</kbd> <kbd>↵</kbd></span>`;
        let left;
        if (query === null) {
            left = `<span class="count">Commence à écrire…</span>`;
        } else if (count === null) {
            left = `<span class="count">Recherche en cours…</span>`;
        } else {
            const label = count === 0 ? 'Aucun résultat' : `<strong>${count}</strong> résultat${count > 1 ? 's' : ''} pour « ${this._esc(query)} »`;
            left = `<span class="count">${label}</span>`;
        }
        this.headTarget.innerHTML = left + hint;
    }

    _buildFoot(query) {
        const base = this.catalogueValue;
        const href = query ? `${base}?q=${encodeURIComponent(query)}` : base;
        this.footTarget.innerHTML = `
            <a href="${href}">Recherche avancée dans le Catalogue <svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2;vertical-align:middle"><path d="M5 12h14M13 5l7 7-7 7"/></svg></a>
            <span class="legend"><kbd>esc</kbd> fermer</span>
        `;
    }

    async _renderPresaisie() {
        this._popularAbortCtrl?.abort();
        this._popularAbortCtrl = new AbortController();
        const ctrl = this._popularAbortCtrl;

        const body = this.resultsTarget;
        body.innerHTML = '';

        if (this._history.length > 0) {
            body.appendChild(this._buildGroup('Recherches récentes', this._history.length, this._historyItems()));
        }

        const placeholder = document.createElement('div');
        placeholder.className = 'search-group';
        placeholder.innerHTML = '<div class="search-group-label">Chargement…</div>';
        body.appendChild(placeholder);

        try {
            const res = await fetch('/api/search/popular', { signal: ctrl.signal });
            if (ctrl.signal.aborted) return;
            if (!res.ok) { placeholder.remove(); return; }
            const data = await res.json();
            const popular = data.popular ?? [];
            if (ctrl.signal.aborted) return;
            placeholder.remove();
            if (popular.length > 0) {
                body.appendChild(this._buildGroup('Souvent Consultés', popular.length, popular.map((it, i) => this._resultAnchor(it, i, 'popular'))));
            }
        } catch (err) {
            if (err.name !== 'AbortError') placeholder.remove();
        }
    }

    _renderSkeletons() {
        const body = this.resultsTarget;
        body.innerHTML = '';
        for (let i = 0; i < 3; i++) {
            const div = document.createElement('div');
            div.className = 'search-skeleton';
            div.innerHTML = `
                <div class="search-skeleton-indicator"></div>
                <div class="search-skeleton-lines">
                    <div class="search-skeleton-line"></div>
                    <div class="search-skeleton-line"></div>
                </div>
            `;
            body.appendChild(div);
        }
    }

    _renderResults(results, q) {
        const body = this.resultsTarget;
        body.innerHTML = '';

        if (results.length === 0) {
            body.innerHTML = `
                <div class="search-empty">
                    <div class="glyph"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>
                    <div class="ttl">Aucun grimoire ne correspond</div>
                    <div class="sub">Rien pour <strong>« ${this._esc(q)} »</strong>.</div>
                </div>
            `;
            this.statusTarget.textContent = '0 résultats';
            return;
        }

        // group by type in order
        const groups = {};
        results.forEach(it => { (groups[it.type] = groups[it.type] ?? []).push(it); });
        ['livre', 'collection', 'auteur'].forEach(type => {
            const arr = groups[type];
            if (!arr?.length) return;
            const anchors = arr.map((it, i) => this._resultAnchor(it, i, type));
            body.appendChild(this._buildGroup(TYPE_LABEL[type] ?? type, arr.length, anchors));
        });

        this.statusTarget.textContent = `${results.length} résultat${results.length > 1 ? 's' : ''}`;
    }

    // ── Builders ──────────────────────────────────────────────────────────────

    _buildGroup(label, count, children) {
        const grp = document.createElement('div');
        grp.className = 'search-group';

        const lbl = document.createElement('div');
        lbl.className = 'search-group-label';
        lbl.innerHTML = `${this._esc(label)} <span class="grp-count">${count}</span>`;
        grp.appendChild(lbl);

        children.forEach(child => grp.appendChild(child));
        return grp;
    }

    _historyItems() {
        return this._history.map((term, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'search-recent';
            btn.dataset.recent = term;
            btn.dataset.idx = i;
            btn.id = `search-recent-${i}`;
            btn.setAttribute('role', 'option');
            btn.innerHTML = `
                <span class="clock"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                <span class="rec-text">${this._esc(term)}</span>
            `;
            btn.addEventListener('click', () => {
                this.inputTarget.value = term;
                this.inputTarget.dispatchEvent(new Event('input'));
            });
            return btn;
        });
    }

    _resultAnchor(item, i, prefix) {
        const url  = this._itemUrl(item);
        const css  = TYPE_CSS[item.type] ?? item.type;
        const a    = document.createElement('a');
        a.href     = url;
        a.className = 'search-result';
        a.setAttribute('role', 'option');
        a.id = `search-option-${prefix}-${i}`;
        a.innerHTML = `
            ${this._thumb(item)}
            <span class="info">
                <span class="ttl">${this._esc(item.title)}</span>
                <span class="sub">${this._esc(item.subtitle)}</span>
            </span>
            <span class="type-pip ${css}">${this._esc(TYPE_LABEL[item.type] ?? item.type)}</span>
        `;
        a.addEventListener('click', () => this._pushHistory(this.inputTarget.value.trim()));
        return a;
    }

    _thumb(item) {
        if (item.type === 'auteur') {
            const c = this._esc(item.avatarColor ?? 'cuir');
            return `<span class="thumb avatar" data-tone="${c}">${this._esc(item.initials ?? '?')}</span>`;
        }
        if (item.thumbnailUrl) {
            return `<span class="thumb cover"><img src="${this._esc(item.thumbnailUrl)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:3px"></span>`;
        }
        if (item.type === 'collection') {
            return `<span class="thumb icon"><svg viewBox="0 0 24 24"><path d="M4 6a2 2 0 0 1 2-2h11v16H6a2 2 0 0 1-2-2z"/><path d="M17 4v16"/></svg></span>`;
        }
        return `<span class="thumb cover"></span>`;
    }

    _itemUrl(item) {
        const slug = encodeURIComponent(item.slug);
        if (item.type === 'livre')      return this.urlLivreValue.replace('__SLUG__', slug);
        if (item.type === 'collection') return this.urlCollectionValue.replace('__SLUG__', slug);
        return this.urlAuteurValue.replace('__SLUG__', slug);
    }

    // ── Active item management ─────────────────────────────────────────────────

    _getActiveItems() {
        return Array.from(this.resultsTarget.querySelectorAll('[role="option"]'));
    }

    _syncActive(items) {
        items.forEach((el, i) => el.classList.toggle('is-active', i === this._activeIndex));
        if (this._activeIndex >= 0) {
            const el = items[this._activeIndex];
            this.inputTarget.setAttribute('aria-activedescendant', el?.id ?? '');
            el?.scrollIntoView({ block: 'nearest' });
        } else {
            this.inputTarget.setAttribute('aria-activedescendant', '');
        }
    }

    // ── Panel open/close ──────────────────────────────────────────────────────

    _openPanel() {
        this.panelTarget.hidden = false;
        this.panelTarget.classList.add('is-open');
        this.inputTarget.setAttribute('aria-expanded', 'true');
    }

    _closePanel() {
        this._inResultsMode = false;
        this.panelTarget.hidden = true;
        this.panelTarget.classList.remove('is-open');
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this._activeIndex = -1;
        this.inputTarget.setAttribute('aria-activedescendant', '');
    }

    // ── History ───────────────────────────────────────────────────────────────

    _pushHistory(q) {
        if (!q) return;
        const idx = this._history.indexOf(q);
        if (idx !== -1) this._history.splice(idx, 1);
        this._history.unshift(q);
        if (this._history.length > 5) this._history.pop();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    _esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
}
