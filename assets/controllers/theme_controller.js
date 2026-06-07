import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        let theme;
        try {
            theme = localStorage.getItem('theme') || 'parchment';
        } catch {
            theme = 'parchment';
        }
        this._applyTheme(theme);
    }

    toggle() {
        const current = document.documentElement.getAttribute('data-theme') || 'parchment';
        const next = current === 'dark' ? 'parchment' : 'dark';
        this._applyTheme(next);
        try {
            localStorage.setItem('theme', next);
        } catch {
            // localStorage unavailable — theme applies for session only
        }
    }

    _applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this._syncButtons(theme);
    }

    _syncButtons(theme) {
        document.querySelectorAll('[data-action*="theme#toggle"]').forEach(btn => {
            btn.setAttribute('aria-pressed', String(theme === 'dark'));
        });
    }
}
