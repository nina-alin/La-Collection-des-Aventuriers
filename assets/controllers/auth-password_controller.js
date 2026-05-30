import { Controller } from '@hotwired/stimulus';

const SCORE_LABELS = ['—', 'Fragile', 'Correct', 'Solide', 'Inviolable'];

function scorePassword(pw) {
    let score = 0;
    if (!pw) return 0;
    if (pw.length >= 8) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    if (pw.length >= 12 && score >= 3) score = 4;
    return Math.min(4, score);
}

export default class extends Controller {
    static targets = ['input', 'toggle', 'meter', 'meterLabel', 'reqs'];

    connect() {
        if (this.hasInputTarget) {
            this._refresh();
        }
    }

    toggle(event) {
        const btn = event.currentTarget;
        const input = this.inputTarget;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    }

    score() {
        this._refresh();
    }

    _refresh() {
        const pw = this.inputTarget.value;
        const s = scorePassword(pw);

        if (this.hasMeterTarget) {
            this.meterTarget.setAttribute('data-score', String(s));
        }
        if (this.hasMeterLabelTarget) {
            this.meterLabelTarget.textContent = SCORE_LABELS[s];
        }
        if (this.hasReqsTarget) {
            this.reqsTarget.querySelectorAll('[data-req]').forEach((li) => {
                const rule = li.getAttribute('data-req');
                let ok = false;
                if (rule === 'len') ok = pw.length >= 8;
                else if (rule === 'case') ok = /[a-z]/.test(pw) && /[A-Z]/.test(pw);
                else if (rule === 'num') ok = /\d/.test(pw);
                else if (rule === 'sym') ok = /[^A-Za-z0-9]/.test(pw);
                li.classList.toggle('met', ok);
            });
        }
    }
}
