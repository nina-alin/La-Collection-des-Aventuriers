import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { autoDismissMs: { type: Number, default: 5000 } };

    connect() {
        this.timeoutId = setTimeout(() => this.dismiss(), this.autoDismissMsValue);
    }

    disconnect() {
        clearTimeout(this.timeoutId);
    }

    close() {
        this.dismiss();
    }

    dismiss() {
        this.element.remove();
    }
}
