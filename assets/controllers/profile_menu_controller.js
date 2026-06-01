import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { open: { type: Boolean, default: false } };
    static targets = ['trigger', 'card', 'backdrop'];

    initialize() {
        this._focusableElements = [];
        this._startY = 0;
        this._everOpened = false;
        this._boundOnKeydown = this.onKeydown.bind(this);
        this._boundOnTouchStart = this._onTouchStart.bind(this);
        this._boundOnTouchEnd = this._onTouchEnd.bind(this);
        this._boundOnDocumentClick = this._onDocumentClick.bind(this);
    }

    connect() {
        var saved = localStorage.getItem('theme');
        var checkbox = this.element.querySelector('input[type=checkbox][id^="theme-switch-"]');
        if (checkbox) {
            checkbox.checked = (saved === 'parchment');
        }
    }

    disconnect() {
        document.removeEventListener('keydown', this._boundOnKeydown);
        document.removeEventListener('click', this._boundOnDocumentClick);
    }

    toggle() {
        this.openValue ? this.close() : this.open();
    }

    open() {
        this.openValue = true;
    }

    close() {
        this.openValue = false;
    }

    openValueChanged(isOpen) {
        this.cardTarget.classList.toggle('is-open', isOpen);
        this.backdropTarget.classList.toggle('is-open', isOpen);
        this.triggerTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (isOpen) {
            this._everOpened = true;
            var selectors = 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';
            this._focusableElements = Array.from(this.cardTarget.querySelectorAll(selectors));
            document.addEventListener('keydown', this._boundOnKeydown);
            document.addEventListener('click', this._boundOnDocumentClick);
            this.cardTarget.addEventListener('touchstart', this._boundOnTouchStart, { passive: true });
            this.cardTarget.addEventListener('touchend', this._boundOnTouchEnd, { passive: true });
        } else {
            document.removeEventListener('keydown', this._boundOnKeydown);
            document.removeEventListener('click', this._boundOnDocumentClick);
            this.cardTarget.removeEventListener('touchstart', this._boundOnTouchStart);
            this.cardTarget.removeEventListener('touchend', this._boundOnTouchEnd);
            if (this._everOpened) {
                this.triggerTarget.focus();
            }
        }
    }

    onKeydown(e) {
        if (e.key === 'Escape') {
            this.close();
            return;
        }

        var menuItems = Array.from(this.cardTarget.querySelectorAll('[role="menuitem"]'));
        var focusable = this._focusableElements;
        var current = document.activeElement;

        if (e.key === 'Tab') {
            e.preventDefault();
            var idx = focusable.indexOf(current);
            if (e.shiftKey) {
                var prev = idx <= 0 ? focusable.length - 1 : idx - 1;
                if (focusable[prev]) focusable[prev].focus();
            } else {
                var next = idx >= focusable.length - 1 ? 0 : idx + 1;
                if (focusable[next]) focusable[next].focus();
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            var idxD = menuItems.indexOf(current);
            var nextD = idxD >= menuItems.length - 1 ? 0 : idxD + 1;
            if (menuItems[nextD]) menuItems[nextD].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var idxU = menuItems.indexOf(current);
            var prevU = idxU <= 0 ? menuItems.length - 1 : idxU - 1;
            if (menuItems[prevU]) menuItems[prevU].focus();
        }
    }

    toggleTheme(e) {
        var checkbox = e.currentTarget.querySelector('input[type=checkbox]');
        var isOn = checkbox ? checkbox.checked : false;
        var theme = isOn ? 'parchment' : 'dark';
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
    }

    _onDocumentClick(e) {
        if (!this.element.contains(e.target)) {
            this.close();
        }
    }

    _onTouchStart(e) {
        this._startY = e.touches[0].clientY;
    }

    _onTouchEnd(e) {
        var endY = e.changedTouches[0].clientY;
        if ((endY - this._startY) > 80) {
            this.close();
        }
    }
}
