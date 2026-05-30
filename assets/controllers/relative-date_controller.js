import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { timestamp: String };

    connect() {
        this._render();
    }

    _render() {
        if (!this.hasTimestampValue || !this.timestampValue) return;

        const date = new Date(this.timestampValue);
        const diffSec = (date - Date.now()) / 1000;
        const fmt = new Intl.RelativeTimeFormat(document.documentElement.lang || 'fr', { numeric: 'auto' });

        this.element.textContent = this._format(fmt, diffSec);
    }

    _format(fmt, diffSec) {
        const abs = Math.abs(diffSec);
        if (abs < 60)        return fmt.format(Math.round(diffSec), 'second');
        if (abs < 3600)      return fmt.format(Math.round(diffSec / 60), 'minute');
        if (abs < 86400)     return fmt.format(Math.round(diffSec / 3600), 'hour');
        if (abs < 2592000)   return fmt.format(Math.round(diffSec / 86400), 'day');
        if (abs < 31536000)  return fmt.format(Math.round(diffSec / 2592000), 'month');
        return fmt.format(Math.round(diffSec / 31536000), 'year');
    }
}
