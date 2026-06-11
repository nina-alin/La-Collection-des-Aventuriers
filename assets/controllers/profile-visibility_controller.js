import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String, csrfToken: String };
    static targets = ['toggle', 'label'];

    async toggle() {
        const previousState = this.toggleTarget.dataset.state;
        const isNowPublic = previousState !== 'public';

        this.toggleTarget.dataset.state = isNowPublic ? 'public' : 'private';
        if (this.hasLabelTarget) {
            this.labelTarget.textContent = isNowPublic ? 'Publique' : 'Privée';
        }

        try {
            const formData = new FormData();
            formData.append('_token', this.csrfTokenValue);

            const res = await fetch(this.urlValue, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('server error');

            const data = await res.json();
            const actual = data.isPublic ? 'public' : 'private';
            this.toggleTarget.dataset.state = actual;
            if (this.hasLabelTarget) {
                this.labelTarget.textContent = data.isPublic ? 'Publique' : 'Privée';
            }
            this.dispatch('success', { detail: { isPublic: data.isPublic } });
        } catch {
            this.toggleTarget.dataset.state = previousState;
            if (this.hasLabelTarget) {
                this.labelTarget.textContent = previousState === 'public' ? 'Publique' : 'Privée';
            }
            this.dispatch('error');
        }
    }
}
