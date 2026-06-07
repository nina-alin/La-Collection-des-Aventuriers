import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['number'];

    connect() {
        this._animated = false;

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) {
            this._showFinal();
            return;
        }

        this.observer = new IntersectionObserver(
            entries => {
                entries.forEach(e => {
                    if (e.isIntersecting && !this._animated) {
                        this._tryAnimate();
                    }
                });
            },
            { threshold: 0.3 }
        );
        this.observer.observe(this.element);

        // Listen for race-condition signal from landing_controller (CHK028)
        this.element.addEventListener('landing:stats-ready', () => {
            if (!this._animated) this._tryAnimate();
        });
    }

    disconnect() {
        if (this.observer) this.observer.disconnect();
    }

    _tryAnimate() {
        const hasData = this.numberTargets.some(el => {
            const v = parseInt(el.dataset.target, 10);
            return !isNaN(v) && v > 0;
        });

        if (!hasData) {
            // Data not yet available — show '--' and wait
            this.numberTargets.forEach(el => { el.textContent = '--'; });
            return;
        }

        this._animated = true;
        if (this.observer) this.observer.disconnect();
        this._animateAll();
    }

    _animateAll() {
        const formatter = new Intl.NumberFormat('fr-FR');
        const duration = 2000;

        this.numberTargets.forEach(el => {
            const target = parseInt(el.dataset.target, 10);
            if (isNaN(target) || target === 0) {
                el.textContent = '--';
                return;
            }

            const start = performance.now();
            const step = now => {
                const t = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - t, 3);
                el.textContent = formatter.format(Math.round(eased * target));
                if (t < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    }

    _showFinal() {
        const formatter = new Intl.NumberFormat('fr-FR');
        this.numberTargets.forEach(el => {
            const target = parseInt(el.dataset.target, 10);
            el.textContent = (!isNaN(target) && target > 0) ? formatter.format(target) : '--';
        });
    }
}
