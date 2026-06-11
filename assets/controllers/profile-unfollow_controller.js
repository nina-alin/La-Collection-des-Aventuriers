import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String, csrfToken: String };

    async unfollow() {
        const formData = new FormData();
        formData.append('_token', this.csrfTokenValue);

        try {
            const res = await fetch(this.urlValue, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('server error');

            this.element.style.transition = 'opacity 0.3s, transform 0.3s';
            this.element.style.opacity = '0';
            this.element.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.element.remove();
                this.dispatch('success');
            }, 300);
        } catch {
            this.dispatch('error');
        }
    }
}
