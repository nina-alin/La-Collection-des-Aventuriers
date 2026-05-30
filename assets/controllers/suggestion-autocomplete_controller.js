import { Controller } from '@hotwired/stimulus';

const DEBOUNCE_MS = 300;
const TIMEOUT_MS  = 3000;

export default class extends Controller {
    static targets  = ['input', 'dropdown'];
    static values   = {
        type:      String,
        createUrl: String,
        isbnMode:  { type: Boolean, default: false },
    };

    connect() {
        this._debounceTimer = null;
        this._abortCtrl     = null;
        this._selectedIndex = -1;

        if (this.isbnModeValue) {
            this.inputTarget?.addEventListener('input', this.onIsbnInput.bind(this));
            this.inputTarget?.addEventListener('blur', this.onYearBlur.bind(this));
            return;
        }

        this.inputTarget?.addEventListener('input', this.onInput.bind(this));
        this.inputTarget?.addEventListener('keydown', this.onKeyDown.bind(this));
        document.addEventListener('click', this.onDocClick.bind(this));
    }

    disconnect() {
        clearTimeout(this._debounceTimer);
        this._abortCtrl?.abort();
        document.removeEventListener('click', this.onDocClick.bind(this));
    }

    onInput(e) {
        clearTimeout(this._debounceTimer);
        const q = e.target.value.trim();
        if (q.length < 2) {
            this.closeDropdown();
            return;
        }
        this._debounceTimer = setTimeout(() => this.fetchResults(q), DEBOUNCE_MS);
    }

    async fetchResults(q) {
        this._abortCtrl?.abort();
        this._abortCtrl = new AbortController();

        const url = `/api/suggestions/autocomplete/${this.typeValue}?q=${encodeURIComponent(q)}`;
        const timer = setTimeout(() => {
            this._abortCtrl?.abort();
            this.fallbackToFreeText();
        }, TIMEOUT_MS);

        try {
            const res = await fetch(url, { signal: this._abortCtrl.signal });
            clearTimeout(timer);
            if (!res.ok) { this.fallbackToFreeText(); return; }
            const data = await res.json();
            this.renderDropdown(data.results ?? [], q);
        } catch (err) {
            clearTimeout(timer);
            if (err.name !== 'AbortError') this.fallbackToFreeText();
        }
    }

    renderDropdown(results, q) {
        const dropdown = this.dropdownTarget;
        dropdown.innerHTML = '';
        dropdown.classList.remove('d-none');
        dropdown.setAttribute('role', 'listbox');
        this._selectedIndex = -1;

        results.forEach(({ id, label }) => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.dataset.id    = id;
            li.dataset.label = label;
            li.textContent   = label;
            li.addEventListener('click', () => this.selectOption(id, label));
            dropdown.appendChild(li);
        });

        if (this.createUrlValue) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.className   = 'autocomplete-create-option';
            li.textContent = `Créer "${q}"`;
            li.addEventListener('click', () => this.createEntity(q));
            dropdown.appendChild(li);
        }
    }

    selectOption(id, label) {
        this.inputTarget.value = label;
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.closeDropdown();
        this.dispatch('selected', { detail: { id, label } });
    }

    async createEntity(name) {
        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const res = await fetch(this.createUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfMeta?.content ?? '',
                },
                body: JSON.stringify({ name }),
            });
            if (!res.ok) return;
            const data = await res.json();
            this.selectOption(data.id, data.label);
        } catch {}
    }

    onKeyDown(e) {
        const items = this.dropdownTarget.querySelectorAll('[role="option"]');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this._selectedIndex = Math.min(this._selectedIndex + 1, items.length - 1);
            this.highlightItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this._selectedIndex = Math.max(this._selectedIndex - 1, 0);
            this.highlightItem(items);
        } else if (e.key === 'Enter' && this._selectedIndex >= 0) {
            e.preventDefault();
            items[this._selectedIndex].click();
        } else if (e.key === 'Escape') {
            this.closeDropdown();
        }
    }

    highlightItem(items) {
        items.forEach((item, i) => item.classList.toggle('is-active', i === this._selectedIndex));
        items[this._selectedIndex]?.scrollIntoView({ block: 'nearest' });
    }

    closeDropdown() {
        if (this.hasDropdownTarget) {
            this.dropdownTarget.classList.add('d-none');
            this.dropdownTarget.innerHTML = '';
        }
    }

    onDocClick(e) {
        if (!this.element.contains(e.target)) this.closeDropdown();
    }

    fallbackToFreeText() {
        const input = this.inputTarget;
        if (!input) return;
        input.removeAttribute('role');
        input.removeAttribute('aria-autocomplete');
        input.removeAttribute('aria-expanded');
        input.readOnly = false;
        this.closeDropdown();
        const hint = document.createElement('small');
        hint.className = 'text-warning d-block mt-1';
        hint.textContent = 'Saisie libre — service de recherche indisponible';
        input.insertAdjacentElement('afterend', hint);
    }

    // ISBN / Year validation mode
    onIsbnInput(e) {
        const raw    = e.target.value.replace(/[-\s]/g, '');
        const digits = raw.replace(/\D/g, '');
        if (digits.length === 10) {
            this.applyIsbnState(e.target, this.isValidIsbn10(digits));
        } else if (digits.length === 13) {
            this.applyIsbnState(e.target, this.isValidIsbn13(digits));
        } else {
            e.target.classList.remove('is-valid', 'is-invalid');
        }
    }

    onYearBlur(e) {
        const v = parseInt(e.target.value, 10);
        if (!v) return;
        const currentYear = new Date().getFullYear();
        const valid = v >= 1800 && v <= currentYear + 2;
        e.target.classList.toggle('is-valid', valid);
        e.target.classList.toggle('is-invalid', !valid);
    }

    applyIsbnState(input, valid) {
        input.classList.toggle('is-valid', valid);
        input.classList.toggle('is-invalid', !valid);
    }

    isValidIsbn10(digits) {
        let sum = 0;
        for (let i = 0; i < 9; i++) sum += (10 - i) * parseInt(digits[i], 10);
        const check = digits[9] === 'X' ? 10 : parseInt(digits[9], 10);
        return (sum + check) % 11 === 0;
    }

    isValidIsbn13(digits) {
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            sum += parseInt(digits[i], 10) * (i % 2 === 0 ? 1 : 3);
        }
        return (10 - (sum % 10)) % 10 === parseInt(digits[12], 10);
    }
}
