import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['pilule', 'marqueeTrack', 'marqueeBlock', 'searchForm', 'searchInput'];

    connect() {
        this._fetchStats();
        this._fetchMarquee();
        this._bindSearch();
    }

    _fetchStats() {
        fetch('/api/public/stats')
            .then(r => {
                if (!r.ok) throw new Error('stats unavailable');
                return r.json();
            })
            .then(data => {
                this._populatePilule(data);
                this._setCounterTargets(data);
            })
            .catch(() => {
                if (this.hasPiluleTarget) {
                    this.piluleTarget.style.display = 'none';
                }
                document.querySelectorAll('[data-counter-target="number"]').forEach(el => {
                    el.textContent = '--';
                });
            });
    }

    _populatePilule(data) {
        if (!this.hasPiluleTarget) return;
        const el = this.piluleTarget;
        el.classList.remove('skeleton');
        el.innerHTML =
            '<span class="pulse"></span>' +
            `${data.total_books.toLocaleString('fr-FR')} fiches · ` +
            `${data.total_users.toLocaleString('fr-FR')} aventuriers · ` +
            `${data.new_this_week} nouvelles cette semaine`;
    }

    _setCounterTargets(data) {
        const counters = document.querySelectorAll('[data-counter-target="number"]');
        const values = [data.total_books, data.total_users, data.new_this_week];
        counters.forEach((el, i) => {
            if (values[i] !== undefined) {
                el.dataset.target = String(values[i]);
            }
        });

        // Trigger counter animation if section already in viewport (race condition fix — CHK028)
        const statsSection = counters[0]?.closest('[data-controller~="counter"]');
        if (statsSection) {
            const rect = statsSection.getBoundingClientRect();
            const inViewport = rect.top < window.innerHeight && rect.bottom > 0;
            if (inViewport) {
                statsSection.dispatchEvent(new CustomEvent('landing:stats-ready', { bubbles: false }));
            }
        }
    }

    _fetchMarquee() {
        fetch('/api/public/marquee')
            .then(r => {
                if (!r.ok) throw new Error('marquee unavailable');
                return r.json();
            })
            .then(items => {
                if (!Array.isArray(items) || items.length === 0) {
                    this._hideMarquee();
                    return;
                }
                this._buildMarquee(items);
            })
            .catch(() => this._hideMarquee());
    }

    _buildMarquee(items) {
        if (!this.hasMarqueeTrackTarget) return;
        const track = this.marqueeTrackTarget;

        // Replace skeleton items with real content
        track.innerHTML = '';

        const buildItem = (item, hidden) => {
            const a = document.createElement('a');
            a.href = item.url;
            a.className = 'marquee-item';
            a.setAttribute('aria-label', item.name);
            if (hidden) {
                a.setAttribute('aria-hidden', 'true');
                a.setAttribute('tabindex', '-1');
            }

            const cover = document.createElement('span');
            cover.className = `marquee-cover ${item.color_class}`;
            cover.textContent = item.initials;

            const text = document.createElement('span');
            text.className = 'marquee-text';

            const title = document.createElement('span');
            title.className = 'marquee-title';
            title.textContent = item.name;

            const sub = document.createElement('span');
            sub.className = 'marquee-sub';
            sub.textContent = item.subtitle;

            text.appendChild(title);
            text.appendChild(sub);
            a.appendChild(cover);
            a.appendChild(text);
            return a;
        };

        // First pass — visible and focusable
        items.forEach(item => track.appendChild(buildItem(item, false)));
        // Second pass — duplicated for infinite loop, hidden from a11y
        items.forEach(item => track.appendChild(buildItem(item, true)));
    }

    _hideMarquee() {
        if (this.hasMarqueeBlockTarget) {
            this.marqueeBlockTarget.style.display = 'none';
        }
    }

    // US2 — search redirect
    _bindSearch() {
        if (!this.hasSearchFormTarget) return;
        this.searchFormTarget.addEventListener('submit', e => {
            e.preventDefault();
            const term = this.hasSearchInputTarget ? this.searchInputTarget.value.trim() : '';
            if (term !== '') {
                window.location.href = `/catalogue?q=${encodeURIComponent(term)}`;
            } else {
                window.location.href = '/catalogue';
            }
        });
    }
}
