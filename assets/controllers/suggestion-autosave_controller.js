import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['label', 'wizard']

    connect() {
        this._lastSaved = null
        this._timer = null
        if (this.hasWizardTarget) {
            this._onRender = this._onLiveRender.bind(this)
            this.wizardTarget.addEventListener('live:render', this._onRender)
        }
    }

    disconnect() {
        clearInterval(this._timer)
        if (this.hasWizardTarget && this._onRender) {
            this.wizardTarget.removeEventListener('live:render', this._onRender)
        }
    }

    _onLiveRender() {
        this._lastSaved = Date.now()
        this._updateLabel()
        clearInterval(this._timer)
        this._timer = setInterval(() => this._updateLabel(), 10_000)
    }

    _updateLabel() {
        if (!this.hasLabelTarget || this._lastSaved === null) return
        const seconds = Math.round((Date.now() - this._lastSaved) / 1000)
        const ago = seconds < 60
            ? `il y a ${seconds} s`
            : `il y a ${Math.round(seconds / 60)} min`
        this.labelTarget.textContent = `Sauvegarde auto · ${ago}`
    }
}
