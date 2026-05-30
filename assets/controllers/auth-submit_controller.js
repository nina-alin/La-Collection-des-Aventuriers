import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'label'];

    submit(event) {
        if (this.element.dataset.submitted === 'true') {
            event.preventDefault();
            return;
        }

        this.element.dataset.submitted = 'true';

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.setAttribute('aria-disabled', 'true');
        }

        if (this.hasLabelTarget) {
            this.labelTarget.innerHTML =
                '<span class="spinner" aria-hidden="true"></span> Envoi en cours…';
        }
    }
}
