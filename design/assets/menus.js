/* Shared dropdown menus — Notifications + User
   Requires markup with #notif-trigger / #notif-menu / #user-trigger / #user-menu / #menu-backdrop */
(function () {
  function init() {
    const triggers = [
      { btn: document.getElementById('notif-trigger'), menu: document.getElementById('notif-menu') },
      { btn: document.getElementById('user-trigger'),  menu: document.getElementById('user-menu')  },
    ].filter(t => t.btn && t.menu);
    if (!triggers.length) return;

    const backdrop = document.getElementById('menu-backdrop');
    const isMobile = () => window.matchMedia('(max-width: 719px)').matches;

    function openMenu(t) {
      triggers.forEach(o => { if (o !== t) closeMenu(o); });
      t.btn.setAttribute('aria-expanded', 'true');
      t.menu.classList.add('is-open');
      if (isMobile() && backdrop) backdrop.classList.add('is-open');
      setTimeout(() => {
        const first = t.menu.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
        if (first) first.focus({ preventScroll: true });
      }, 60);
    }
    function closeMenu(t) {
      t.btn.setAttribute('aria-expanded', 'false');
      t.menu.classList.remove('is-open');
      if (backdrop && !triggers.some(o => o.menu.classList.contains('is-open'))) {
        backdrop.classList.remove('is-open');
      }
    }
    function toggle(t) {
      if (t.menu.classList.contains('is-open')) closeMenu(t);
      else openMenu(t);
    }

    triggers.forEach(t => {
      t.btn.addEventListener('click', (e) => { e.stopPropagation(); toggle(t); });
    });

    document.addEventListener('click', (e) => {
      triggers.forEach(t => {
        if (!t.menu.classList.contains('is-open')) return;
        if (t.menu.contains(e.target) || t.btn.contains(e.target)) return;
        closeMenu(t);
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      triggers.forEach(t => {
        if (t.menu.classList.contains('is-open')) { closeMenu(t); t.btn.focus(); }
      });
    });

    if (backdrop) {
      backdrop.addEventListener('click', () => { triggers.forEach(closeMenu); });
    }

    // Mark all read
    const markAll = document.querySelector('[data-action="mark-all-read"]');
    if (markAll) markAll.addEventListener('click', (e) => {
      e.stopPropagation();
      document.querySelectorAll('.notif-item.unread').forEach(it => it.classList.remove('unread'));
      const badge = document.querySelector('#notif-menu .count-badge');
      if (badge) badge.textContent = '0';
      const trig = document.getElementById('notif-trigger');
      if (trig) {
        const dot = trig.querySelector('.dot');
        if (dot) dot.style.display = 'none';
        trig.classList.remove('has-unread');
      }
    });

    // Theme switch inside the user menu (kept in sync with global toggle)
    const sw = document.getElementById('theme-switch-menu');
    const name = document.getElementById('theme-name');
    function sync() {
      const cur = document.documentElement.getAttribute('data-theme') || 'light';
      if (sw) sw.checked = cur === 'dark';
      if (name) name.textContent = cur === 'dark' ? 'Grimoire' : 'Parchemin';
    }
    sync();
    if (sw) sw.addEventListener('change', () => {
      const next = sw.checked ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('lca.theme', next); } catch (_) {}
      sync();
    });
    new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
