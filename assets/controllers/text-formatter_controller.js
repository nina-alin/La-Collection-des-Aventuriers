import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['textarea'];

    bold()        { this.#wrap('**', '**'); }
    italic()      { this.#wrap('_', '_'); }
    quote()       { this.#prefixLines('> '); }
    spoiler()     { this.#wrap('||', '||'); }

    #wrap(open, close) {
        const ta = this.textareaTarget;
        const { selectionStart: s, selectionEnd: e, value } = ta;
        const selected = value.slice(s, e);
        const replacement = open + (selected || 'texte') + close;
        ta.setRangeText(replacement, s, e, 'select');
        ta.dispatchEvent(new Event('input'));
        ta.focus();
    }

    #prefixLines(prefix) {
        const ta = this.textareaTarget;
        const { selectionStart: s, selectionEnd: e, value } = ta;
        const lineStart = value.lastIndexOf('\n', s - 1) + 1;
        const block = value.slice(lineStart, e);
        const prefixed = block.split('\n').map(l => prefix + l).join('\n');
        ta.setRangeText(prefixed, lineStart, e, 'select');
        ta.dispatchEvent(new Event('input'));
        ta.focus();
    }
}
