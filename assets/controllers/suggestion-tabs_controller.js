import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['pendingBadge'];

    connect() {
        this._isMobile = window.innerWidth < 1080;
        this._tabs     = this.element.querySelectorAll('[role="tab"]');
        this._panels   = [];

        this._tabs.forEach(tab => {
            const panelId = tab.getAttribute('aria-controls');
            const panel   = document.getElementById(panelId);
            if (panel) this._panels.push(panel);
            tab.addEventListener('click', () => this.selectTab(tab));
        });

        if (this._isMobile) {
            this.selectTab(this._tabs[0]);
        }

        window.addEventListener('resize', this.onResize.bind(this));
    }

    disconnect() {
        window.removeEventListener('resize', this.onResize.bind(this));
    }

    selectTab(selectedTab) {
        this._tabs.forEach(tab => {
            const isSelected = tab === selectedTab;
            tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });

        this._panels.forEach(panel => {
            const controlledBy = this.element.querySelector(`[aria-controls="${panel.id}"]`);
            panel.hidden = controlledBy !== selectedTab;
        });
    }

    onResize() {
        this._isMobile = window.innerWidth < 1080;
        if (!this._isMobile) {
            this._panels.forEach(p => (p.hidden = false));
        }
    }

    updatePendingBadge(count) {
        if (this.hasPendingBadgeTarget) {
            this.pendingBadgeTarget.textContent = count;
        }
    }
}
