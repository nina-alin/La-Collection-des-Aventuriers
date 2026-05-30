import { Controller } from '@hotwired/stimulus';

const POLL_INTERVAL_MS = 30_000;

export default class extends Controller {
    static targets  = ['list', 'countAll', 'countPending', 'countValidated', 'countRefused', 'emptyState', 'suspendedIndicator', 'pendingBadge'];
    static values   = { feedUrl: String };

    connect() {
        this._failCount   = 0;
        this._filter      = 'all';
        this._suggestions = [];

        this.poll();
        this._interval = setInterval(() => this.poll(), POLL_INTERVAL_MS);
    }

    disconnect() {
        clearInterval(this._interval);
    }

    async poll() {
        try {
            const res = await fetch(this.feedUrlValue, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('feed error');
            const data = await res.json();
            this._failCount = 0;
            this._suggestions = data.suggestions ?? [];
            this.updateCounts(data.counts ?? {});
            this.renderCards();
            this.setSuspended(false);
        } catch {
            this._failCount++;
            if (this._failCount >= 3) this.setSuspended(true);
        }
    }

    resume() {
        this._failCount = 0;
        this.setSuspended(false);
        this.poll();
    }

    setSuspended(suspended) {
        this.element.dataset.suspended = suspended ? 'true' : 'false';
        if (this.hasSuspendedIndicatorTarget) {
            this.suspendedIndicatorTarget.classList.toggle('d-none', !suspended);
        }
    }

    updateCounts(counts) {
        const set = (target, val) => { if (target) target.textContent = val ?? 0; };
        set(this.hasCountAllTarget       ? this.countAllTarget : null,       counts.total);
        set(this.hasCountPendingTarget   ? this.countPendingTarget : null,   counts.pending);
        set(this.hasCountValidatedTarget ? this.countValidatedTarget : null, counts.validated);
        set(this.hasCountRefusedTarget   ? this.countRefusedTarget : null,   counts.refused);

        if (this.hasPendingBadgeTarget) {
            this.pendingBadgeTarget.textContent = counts.pending ?? 0;
        }
    }

    filterAll()       { this.setFilter('all'); }
    filterPending()   { this.setFilter('pending'); }
    filterValidated() { this.setFilter('validated'); }
    filterRefused()   { this.setFilter('refused'); }

    setFilter(filter) {
        this._filter = filter;
        this.element.querySelectorAll('.suivi-filter').forEach(btn => {
            btn.setAttribute('aria-pressed', btn.dataset.filter === filter ? 'true' : 'false');
        });
        this.renderCards();
    }

    renderCards() {
        if (!this.hasListTarget) return;

        const visible = this._filter === 'all'
            ? this._suggestions
            : this._suggestions.filter(s => s.status.toLowerCase() === this._filter);

        if (this.hasEmptyStateTarget) {
            this.emptyStateTarget.classList.toggle('d-none', visible.length > 0);
        }

        const existingIds = new Set();
        this.listTarget.querySelectorAll('[data-suggestion-id]').forEach(el => {
            if (!visible.find(s => s.id === el.dataset.suggestionId)) {
                el.remove();
            } else {
                existingIds.add(el.dataset.suggestionId);
            }
        });

        visible.forEach(suggestion => {
            if (!existingIds.has(suggestion.id)) {
                this.listTarget.appendChild(this.buildCard(suggestion));
            }
        });
    }

    buildCard(s) {
        const div = document.createElement('div');
        div.className    = 'suivi-card';
        div.dataset.suggestionId = s.id;

        const statusMap = { PENDING: 'En attente', VALIDATED: 'Validée', REFUSED: 'Refusée' };
        const statusLabel = statusMap[s.status] ?? s.status;
        const relativeDate = this.relativeDate(s.submittedAt);

        div.innerHTML = `
            <div class="suivi-card-body">
                <div class="crumb">
                    <span class="mode ${s.mode === 'CORRECTION' ? 'correction' : ''}">${s.mode === 'CORRECTION' ? 'Correction' : 'Nouvelle fiche'}</span>
                    <span class="sep">·</span>
                    <span>${s.entityType}</span>
                </div>
                <div class="ttl">${this.escapeHtml(s.entityName || '—')}</div>
                <div class="time">${relativeDate}</div>
            </div>
            <div class="rhs">
                <span class="badge badge-${s.status.toLowerCase()}">${statusLabel}</span>
                ${s.refusal ? `<button class="info-btn" type="button" aria-expanded="false" aria-controls="refusal-${s.id}" aria-label="Motif de refus">ℹ</button>` : ''}
            </div>
            ${s.refusal ? this.buildRefusalPanel(s) : ''}
        `;

        if (s.refusal) {
            const btn   = div.querySelector('.info-btn');
            const panel = div.querySelector(`#refusal-${s.id}`);
            btn?.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                panel?.classList.toggle('d-none', expanded);
            });
        }

        return div;
    }

    buildRefusalPanel(s) {
        const r = s.refusal;
        const actionButtons = (r.actions ?? [])
            .map(a => `<button type="button" class="btn btn-sm btn-outline-secondary" data-action="${a}">${a === 'VOIR_FICHE' ? 'Voir la fiche' : 'Masquer'}</button>`)
            .join('');

        return `
            <div id="refusal-${s.id}" class="refusal-panel d-none" role="region" aria-label="Motif de refus">
                <p class="mb-1"><strong>${this.escapeHtml(r.moderatorName)}</strong> — ${this.escapeHtml(r.reason)}</p>
                <div class="d-flex gap-2 flex-wrap">${actionButtons}</div>
            </div>
        `;
    }

    relativeDate(iso) {
        const diff = Date.now() - new Date(iso).getTime();
        const min  = Math.floor(diff / 60_000);
        if (min < 1)   return 'À l\'instant';
        if (min < 60)  return `il y a ${min} min`;
        const h = Math.floor(min / 60);
        if (h < 24)   return `il y a ${h} h`;
        const d = Math.floor(h / 24);
        return `il y a ${d} j`;
    }

    escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
}
