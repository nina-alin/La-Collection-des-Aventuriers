import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['min', 'max', 'fill', 'handleMin', 'handleMax', 'labelMin', 'labelMax'];
    static values  = { min: Number, max: Number, step: { type: Number, default: 1 } };

    connect() {
        this._update();
        this.minTarget.addEventListener('input', this._onMinInput);
        this.maxTarget.addEventListener('input', this._onMaxInput);
    }

    disconnect() {
        this.minTarget.removeEventListener('input', this._onMinInput);
        this.maxTarget.removeEventListener('input', this._onMaxInput);
    }

    _onMinInput = () => {
        const min  = parseInt(this.minTarget.value, 10);
        const max  = parseInt(this.maxTarget.value, 10);
        if (min > max) this.minTarget.value = max;
        this._update();
        this._dispatchChange();
    };

    _onMaxInput = () => {
        const min = parseInt(this.minTarget.value, 10);
        const max = parseInt(this.maxTarget.value, 10);
        if (max < min) this.maxTarget.value = min;
        this._update();
        this._dispatchChange();
    };

    _update() {
        const rangeMin = this.minValue;
        const rangeMax = this.maxValue;
        const valMin   = parseInt(this.minTarget.value, 10);
        const valMax   = parseInt(this.maxTarget.value, 10);
        const span     = rangeMax - rangeMin || 1;

        const pctMin = ((valMin - rangeMin) / span) * 100;
        const pctMax = ((valMax - rangeMin) / span) * 100;

        if (this.hasFillTarget) {
            this.fillTarget.style.left  = `${pctMin}%`;
            this.fillTarget.style.right = `${100 - pctMax}%`;
        }
        if (this.hasHandleMinTarget) this.handleMinTarget.style.left = `${pctMin}%`;
        if (this.hasHandleMaxTarget) this.handleMaxTarget.style.left  = `${pctMax}%`;
        if (this.hasLabelMinTarget)  this.labelMinTarget.textContent  = valMin;
        if (this.hasLabelMaxTarget)  this.labelMaxTarget.textContent  = valMax;
    }

    _dispatchChange() {
        this.element.dispatchEvent(new CustomEvent('range-slider:change', {
            bubbles: true,
            detail: {
                min: parseInt(this.minTarget.value, 10),
                max: parseInt(this.maxTarget.value, 10),
            },
        }));
    }
}
