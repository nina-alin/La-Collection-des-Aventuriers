import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'diffPanel',
        'queuePanel',
        'fluxView',
        'tableView',
        'viewToggle',
        'refusalModal',
        'refusalSelect',
        'refusalTextarea',
        'refusalError',
        'toastContainer',
        'searchInput',
        'entitiesTableBody',
    ];

    static values = {
        pendingCount: Number,
    };

    _currentSuggestionId = null;
    _currentCsrfToken = null;
    _searchTimer = null;
    _activeType = '';

    connect() {
        if (!this.hasToastContainerTarget) {
            const toast = document.createElement('div');
            toast.setAttribute('data-moderation-room-target', 'toastContainer');
            toast.style.cssText = 'position:fixed;bottom:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
            document.body.appendChild(toast);
        }
        this._fetchEntities();
    }

    // ─── Approve ───────────────────────────────────────────────────────────────

    async approveSuggestion(event) {
        const btn = event.currentTarget;
        const suggestionId = btn.dataset.suggestionId;
        const csrfToken = btn.dataset.csrfToken;
        await this._approve(btn, suggestionId, csrfToken);
    }

    async approveFromTable(event) {
        const btn = event.currentTarget;
        const suggestionId = btn.dataset.suggestionId;
        const csrfToken = btn.dataset.csrfToken;
        await this._approve(btn, suggestionId, csrfToken);
    }

    async _approve(btn, suggestionId, csrfToken) {
        btn.disabled = true;
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);

        try {
            const response = await fetch(`/moderation/suggestion/${suggestionId}/approve`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                await this._loadNextSuggestion(data.nextSuggestionId);
            } else {
                this._showToast(data.message ?? 'Erreur lors de la validation.');
            }
        } catch {
            this._showToast('Erreur réseau. Veuillez réessayer.');
        } finally {
            btn.disabled = false;
        }
    }

    async _loadNextSuggestion(nextId) {
        if (!nextId) {
            if (this.hasDiffPanelTarget) {
                this.diffPanelTarget.innerHTML = '<p style="padding:1rem;color:var(--text-muted)">Aucune suggestion en attente.</p>';
            }
            return;
        }

        try {
            const response = await fetch(`/moderation/suggestion/${nextId}/diff-partial`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            if (this.hasDiffPanelTarget) {
                this.diffPanelTarget.innerHTML = html;
            }
        } catch {
            this._showToast('Impossible de charger la suggestion suivante.');
        }
    }

    // ─── Load suggestion (queue click) ─────────────────────────────────────────

    async loadSuggestion(event) {
        const item = event.currentTarget;
        const suggestionId = item.dataset.suggestionId;
        await this._loadNextSuggestion(suggestionId);
    }

    // ─── Refusal modal ─────────────────────────────────────────────────────────

    openRefusalModal(event) {
        const btn = event.currentTarget;
        this._currentSuggestionId = btn.dataset.suggestionId;
        this._currentCsrfToken = btn.dataset.csrfToken;
        this._openModal();
    }

    openRefusalModalFromTable(event) {
        const btn = event.currentTarget;
        this._currentSuggestionId = btn.dataset.suggestionId;
        this._currentCsrfToken = btn.dataset.csrfToken;
        this._openModal();
    }

    _openModal() {
        if (this.hasRefusalErrorTarget) {
            this.refusalErrorTarget.style.display = 'none';
            this.refusalErrorTarget.textContent = '';
        }
        if (this.hasRefusalTextareaTarget) {
            this.refusalTextareaTarget.style.display = 'none';
            this.refusalTextareaTarget.value = '';
            this.refusalTextareaTarget.required = false;
        }
        if (this.hasRefusalSelectTarget) {
            this.refusalSelectTarget.selectedIndex = 0;
        }
        if (this.hasRefusalModalTarget) {
            this.refusalModalTarget.showModal?.() ?? (this.refusalModalTarget.style.display = 'block');
        }
    }

    closeRefusalModal() {
        if (this.hasRefusalModalTarget) {
            this.refusalModalTarget.close?.() ?? (this.refusalModalTarget.style.display = 'none');
        }
    }

    toggleRefusalTextarea(event) {
        const isOther = event.target.value === 'Autre';
        if (this.hasRefusalTextareaTarget) {
            this.refusalTextareaTarget.style.display = isOther ? 'block' : 'none';
            this.refusalTextareaTarget.required = isOther;
        }
    }

    async submitRefusal() {
        if (!this._currentSuggestionId) return;

        const select = this.hasRefusalSelectTarget ? this.refusalSelectTarget : null;
        const textarea = this.hasRefusalTextareaTarget ? this.refusalTextareaTarget : null;

        let reason = select?.value ?? '';
        if (reason === 'Autre') {
            const text = textarea?.value?.trim() ?? '';
            if (!text) {
                if (this.hasRefusalErrorTarget) {
                    this.refusalErrorTarget.textContent = 'Veuillez préciser le motif.';
                    this.refusalErrorTarget.style.display = 'block';
                }
                return;
            }
            reason = text;
        }

        const formData = new FormData();
        formData.append('_csrf_token', this._currentCsrfToken ?? '');
        formData.append('reason', reason);

        try {
            const response = await fetch(`/moderation/suggestion/${this._currentSuggestionId}/refuse`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.closeRefusalModal();
                await this._loadNextSuggestion(data.nextSuggestionId);
            } else {
                if (this.hasRefusalErrorTarget) {
                    this.refusalErrorTarget.textContent = data.message ?? 'Erreur.';
                    this.refusalErrorTarget.style.display = 'block';
                }
            }
        } catch {
            this._showToast('Erreur réseau. Veuillez réessayer.');
        }
    }

    // ─── View toggle ───────────────────────────────────────────────────────────

    toggleView() {
        const fluxVisible = this.hasFluxViewTarget && this.fluxViewTarget.style.display !== 'none';

        if (this.hasFluxViewTarget) {
            this.fluxViewTarget.style.display = fluxVisible ? 'none' : '';
        }
        if (this.hasTableViewTarget) {
            this.tableViewTarget.style.display = fluxVisible ? '' : 'none';
        }
        if (this.hasViewToggleTarget) {
            this.viewToggleTarget.textContent = fluxVisible ? 'Vue Flux' : 'Vue Tableau';
        }
    }

    async switchToFluxView(event) {
        const btn = event.currentTarget;
        const suggestionId = btn.dataset.suggestionId;

        if (this.hasFluxViewTarget) {
            this.fluxViewTarget.style.display = '';
        }
        if (this.hasTableViewTarget) {
            this.tableViewTarget.style.display = 'none';
        }
        if (this.hasViewToggleTarget) {
            this.viewToggleTarget.textContent = 'Vue Tableau';
        }

        if (suggestionId) {
            await this._loadNextSuggestion(suggestionId);
        }
    }

    // ─── Entities search / filter ───────────────────────────────────────────────

    onSearchInput() {
        clearTimeout(this._searchTimer);
        this._searchTimer = setTimeout(() => this._fetchEntities(), 300);
    }

    filterByType(event) {
        this._activeType = event.currentTarget.dataset.type ?? '';
        this._fetchEntities();
    }

    async _fetchEntities() {
        const search = this.hasSearchInputTarget ? this.searchInputTarget.value : '';
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (this._activeType) params.set('type', this._activeType);

        try {
            const response = await fetch(`/moderation/entities?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            if (this.hasEntitiesTableBodyTarget) {
                this.entitiesTableBodyTarget.innerHTML = html;
            }
        } catch {
            this._showToast('Erreur lors du chargement des fiches.');
        }
    }

    // ─── Delete modal ──────────────────────────────────────────────────────────

    openDeleteModal(event) {
        const btn = event.currentTarget;
        const entityId = btn.dataset.entityId;
        const entityType = btn.dataset.entityType;
        const entityName = btn.dataset.entityName;

        if (confirm(`Choisissez une action pour "${entityName}" :\n\nOK = Supprimer\nAnnuler = Dépublier`)) {
            this._deleteEntity(entityType, entityId);
        }
    }

    async _deleteEntity(type, id) {
        try {
            const response = await fetch(`/moderation/entities/${type}/${id}`, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            await this._fetchEntities();
        } catch {
            this._showToast('Erreur lors de la suppression.');
        }
    }

    openRefusalReasonModal(event) {
        const btn = event.currentTarget;
        const reason = btn.dataset.refusalReason ?? '';
        alert(`Motif de refus :\n\n${reason}`);
    }

    // ─── Toast ─────────────────────────────────────────────────────────────────

    _showToast(message) {
        if (!this.hasToastContainerTarget) return;

        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = 'background:var(--color-error,#dc2626);color:#fff;padding:.625rem 1rem;border-radius:var(--radius-md,.375rem);font-size:.875rem;box-shadow:0 2px 8px rgba(0,0,0,.15)';
        this.toastContainerTarget.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }
}
