import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['grid'];

  sortByVolume() {
    const items = [...this.gridTarget.children];
    items.sort((a, b) => {
      const va = parseInt(a.dataset.volume ?? '0', 10);
      const vb = parseInt(b.dataset.volume ?? '0', 10);
      return va - vb;
    });
    items.forEach(el => this.gridTarget.appendChild(el));
    this.#updateActiveSort('volume');
  }

  sortByRating() {
    const items = [...this.gridTarget.children];
    items.sort((a, b) => {
      const ra = a.dataset.rating !== '' ? parseFloat(a.dataset.rating) : -Infinity;
      const rb = b.dataset.rating !== '' ? parseFloat(b.dataset.rating) : -Infinity;
      return rb - ra;
    });
    items.forEach(el => this.gridTarget.appendChild(el));
    this.#updateActiveSort('rating');
  }

  #updateActiveSort(active) {
    this.element.querySelectorAll('.seg button').forEach(btn => {
      btn.setAttribute('aria-pressed', btn.dataset.sort === active ? 'true' : 'false');
    });
  }
}
