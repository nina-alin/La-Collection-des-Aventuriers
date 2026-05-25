import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['tab']

    connect() {
        this._panels = this._buildPanelMap()
        this._activateFirst()
    }

    selectTab(event) {
        this._activate(event.currentTarget)
    }

    handleKey(event) {
        const tabs = this.tabTargets.filter(t => !t.hidden)
        const idx = tabs.indexOf(event.currentTarget)

        if (event.key === 'ArrowRight') {
            event.preventDefault()
            tabs[(idx + 1) % tabs.length].focus()
            this._activate(tabs[(idx + 1) % tabs.length])
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault()
            tabs[(idx - 1 + tabs.length) % tabs.length].focus()
            this._activate(tabs[(idx - 1 + tabs.length) % tabs.length])
        }
    }

    _activate(tab) {
        this.tabTargets.forEach(t => {
            const active = t === tab
            t.setAttribute('aria-selected', active ? 'true' : 'false')
            t.classList.toggle('is-active', active)
            const panelId = t.getAttribute('aria-controls')
            const panel = document.getElementById(panelId)
            if (panel) panel.hidden = !active
        })
    }

    _activateFirst() {
        const tabs = this.tabTargets
        if (tabs.length > 0) this._activate(tabs[0])
    }

    _buildPanelMap() {
        const map = {}
        this.tabTargets.forEach(tab => {
            const panelId = tab.getAttribute('aria-controls')
            if (panelId) map[panelId] = document.getElementById(panelId)
        })
        return map
    }
}
