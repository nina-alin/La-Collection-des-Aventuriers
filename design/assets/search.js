/* Shared search autocomplete — works on every page with <form class="sh-search">
   No per-page setup beyond the existing form. */
(function () {
  const STORAGE_KEY = 'lca.recentSearches';
  const MAX_RECENTS = 5;
  const MAX_PER_GROUP = 4;

  /* Path prefix so links resolve correctly whether we're at project root
     (dashboard.html) or inside /pages/ */
  const inPagesFolder = /\/pages\//.test(location.pathname);
  const rootPrefix = inPagesFolder ? '../' : '';
  const pagesPrefix = inPagesFolder ? '' : 'pages/';

  /* ---------- Dataset (mock — would come from server) ---------- */
  const DATA = [
    // Livres
    { type: 'livre',  title: 'Le Sorcier de la Montagne de Feu',   sub: 'LCA-0042 · 1982 · S. Jackson & I. Livingstone', thumb: { kind: 'cover', tone: 'cuir' },   href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'Le Manoir de l\u2019Enfer',          sub: 'LCA-0214 · 1985 · Steve Jackson',               thumb: { kind: 'cover', tone: 'encre' },  href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'La Cit\u00e9 des Voleurs',           sub: 'LCA-0089 · 1983 · Ian Livingstone',             thumb: { kind: 'cover', tone: 'sang' },   href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'La For\u00eat de la Mal\u00e9diction', sub: 'LCA-0156 · 1984 · Ian Livingstone',           thumb: { kind: 'cover', tone: 'mousse' }, href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'Le Labyrinthe de la Mort',           sub: 'LCA-0107 · 1984 · Ian Livingstone',             thumb: { kind: 'cover', tone: 'mousse' }, href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'L\u2019Or Sombre des Mages',         sub: 'LCA-0301 · 1989 · Joe Dever',                   thumb: { kind: 'cover', tone: 'or' },     href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'La Citadelle Sans Soleil',           sub: 'LCA-0188 · 1986 · Peter Darvill-Evans',         thumb: { kind: 'cover', tone: 'encre' },  href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'Le Repaire des Vampires',            sub: 'LCA-0241 · 1988 · Keith Martin',                thumb: { kind: 'cover', tone: 'sang' },   href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'L\u2019\u0152il d\u2019\u00c9meraude', sub: 'LCA-0167 · 1985 · Ian Livingstone',           thumb: { kind: 'cover', tone: 'mousse' }, href: pagesPrefix + 'livre.html' },
    { type: 'livre',  title: 'Loup Solitaire \u2014 Les Ma\u00eetres des T\u00e9n\u00e8bres', sub: 'LCA-0319 · 1984 · Joe Dever', thumb: { kind: 'cover', tone: 'cuir' }, href: pagesPrefix + 'livre.html' },

    // Auteurs
    { type: 'auteur', title: 'Steve Jackson',     sub: 'auteur \u00b7 47 fiches',  thumb: { kind: 'avatar', label: 'SJ', tone: 'encre' },   href: pagesPrefix + 'catalogue.html?auteur=jackson' },
    { type: 'auteur', title: 'Ian Livingstone',   sub: 'auteur \u00b7 89 fiches',  thumb: { kind: 'avatar', label: 'IL', tone: 'encre' },   href: pagesPrefix + 'catalogue.html?auteur=livingstone' },
    { type: 'auteur', title: 'Joe Dever',         sub: 'auteur \u00b7 28 fiches',  thumb: { kind: 'avatar', label: 'JD', tone: 'mousse' },  href: pagesPrefix + 'catalogue.html?auteur=dever' },
    { type: 'auteur', title: 'Peter Darvill-Evans', sub: 'auteur \u00b7 12 fiches', thumb: { kind: 'avatar', label: 'PD', tone: 'cuir' },   href: pagesPrefix + 'catalogue.html?auteur=darvill' },
    { type: 'auteur', title: 'Dave Morris',       sub: 'auteur \u00b7 18 fiches',  thumb: { kind: 'avatar', label: 'DM', tone: 'or' },      href: pagesPrefix + 'catalogue.html?auteur=morris' },

    // Illustrateurs
    { type: 'illu',   title: 'Iain McCaig',       sub: 'illustrateur \u00b7 34 couvertures', thumb: { kind: 'avatar', label: 'IM', tone: 'or' },     href: pagesPrefix + 'catalogue.html?illu=mccaig' },
    { type: 'illu',   title: 'Russ Nicholson',    sub: 'illustrateur \u00b7 41 couvertures', thumb: { kind: 'avatar', label: 'RN', tone: 'or' },     href: pagesPrefix + 'catalogue.html?illu=nicholson' },
    { type: 'illu',   title: 'Tim Sell',          sub: 'illustrateur \u00b7 22 couvertures', thumb: { kind: 'avatar', label: 'TS', tone: 'or' },     href: pagesPrefix + 'catalogue.html?illu=sell' },

    // Collections / Séries
    { type: 'collec', title: 'D\u00e9fis Fantastiques', sub: 'collection \u00b7 59 num\u00e9ros \u00b7 Solar/Gallimard', thumb: { kind: 'icon', glyph: 'collection' }, href: pagesPrefix + 'catalogue.html?collec=df' },
    { type: 'collec', title: 'Loup Solitaire',          sub: 'collection \u00b7 28 tomes \u00b7 Joe Dever',              thumb: { kind: 'icon', glyph: 'collection' }, href: pagesPrefix + 'catalogue.html?collec=ls' },
    { type: 'collec', title: 'Sortil\u00e8ges',         sub: 'collection \u00b7 4 tomes \u00b7 Bragelonne',              thumb: { kind: 'icon', glyph: 'collection' }, href: pagesPrefix + 'catalogue.html?collec=sortileges' },
    { type: 'serie',  title: 'Quest \u00b7 La Voie du Tigre', sub: 's\u00e9rie \u00b7 6 tomes \u00b7 M. Smith & J. Thomson', thumb: { kind: 'icon', glyph: 'series' }, href: pagesPrefix + 'catalogue.html?serie=voie-tigre' },

    // Éditeurs
    { type: 'edit',   title: 'Gallimard \u00b7 Folio Junior', sub: '\u00e9diteur \u00b7 412 fiches', thumb: { kind: 'icon', glyph: 'publisher' }, href: pagesPrefix + 'catalogue.html?edit=gallimard' },
    { type: 'edit',   title: 'Solar',                         sub: '\u00e9diteur \u00b7 328 fiches', thumb: { kind: 'icon', glyph: 'publisher' }, href: pagesPrefix + 'catalogue.html?edit=solar' },
    { type: 'edit',   title: 'Bragelonne',                    sub: '\u00e9diteur \u00b7 187 fiches', thumb: { kind: 'icon', glyph: 'publisher' }, href: pagesPrefix + 'catalogue.html?edit=bragelonne' },
  ];

  const TYPE_LABELS = {
    livre:  { label: 'Livres',         emoji: 'Grimoire' },
    auteur: { label: 'Auteurs',        emoji: 'Plume' },
    illu:   { label: 'Illustrateurs',  emoji: 'Pinceau' },
    collec: { label: 'Collections',    emoji: 'Reli\u00e9' },
    serie:  { label: 'S\u00e9ries',    emoji: 'Saga' },
    edit:   { label: '\u00c9diteurs',  emoji: 'Maison' },
  };
  const TYPE_ORDER = ['livre', 'auteur', 'collec', 'serie', 'illu', 'edit'];
  const TYPE_PIP = {
    livre: 'Livre', auteur: 'Auteur', illu: 'Illustrateur',
    collec: 'Collection', serie: 'S\u00e9rie', edit: '\u00c9diteur',
  };

  const ICONS = {
    collection: '<svg viewBox="0 0 24 24"><path d="M4 4h6v16H4zM10 4h4v16h-4zM14 4h6v16h-6z"/></svg>',
    series:     '<svg viewBox="0 0 24 24"><path d="M4 6a2 2 0 0 1 2-2h11v16H6a2 2 0 0 1-2-2z"/><path d="M17 4v16M9 8h5M9 12h5"/></svg>',
    publisher:  '<svg viewBox="0 0 24 24"><path d="M4 4h16v6H4zM4 14h16v6H4z"/></svg>',
    clock:      '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    close:      '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M6 18 18 6"/></svg>',
    arrow:      '<svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>',
    search:     '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  };

  /* ---------- LocalStorage for recent queries ---------- */
  function getRecents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (_) { return []; }
  }
  function setRecents(list) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)); } catch (_) {}
  }
  function pushRecent(query) {
    const q = query.trim();
    if (!q) return;
    const next = [q, ...getRecents().filter(r => r.toLowerCase() !== q.toLowerCase())].slice(0, MAX_RECENTS);
    setRecents(next);
  }
  function removeRecent(query) {
    setRecents(getRecents().filter(r => r !== query));
  }

  /* ---------- Highlight ---------- */
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'
    })[c]);
  }
  function highlight(text, query) {
    if (!query) return escapeHtml(text);
    const safe = escapeHtml(text);
    const tokens = query.trim().split(/\s+/).filter(Boolean).map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    if (!tokens.length) return safe;
    const re = new RegExp('(' + tokens.join('|') + ')', 'gi');
    return safe.replace(re, '<mark>$1</mark>');
  }

  /* ---------- Filter ---------- */
  function normalize(s) {
    return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }
  function score(item, q) {
    const t = normalize(item.title);
    const s = normalize(item.sub);
    if (t.startsWith(q)) return 100;
    if (t.includes(q)) return 60;
    if (s.includes(q)) return 20;
    return 0;
  }
  function filterData(query) {
    const q = normalize(query);
    if (!q) return [];
    return DATA
      .map(it => ({ it, sc: score(it, q) }))
      .filter(x => x.sc > 0)
      .sort((a, b) => b.sc - a.sc)
      .map(x => x.it);
  }

  /* ---------- Render ---------- */
  function thumbHtml(t) {
    if (!t) return '<span class="thumb icon">' + ICONS.search + '</span>';
    if (t.kind === 'cover')   return '<span class="thumb cover ' + (t.tone || 'cuir') + '"></span>';
    if (t.kind === 'avatar')  return '<span class="thumb avatar" data-tone="' + (t.tone || 'cuir') + '">' + escapeHtml(t.label || '?') + '</span>';
    if (t.kind === 'icon')    return '<span class="thumb icon">' + (ICONS[t.glyph] || ICONS.search) + '</span>';
    return '<span class="thumb icon">' + ICONS.search + '</span>';
  }

  function renderEmpty(dd) {
    const recents = getRecents();
    let html = '';
    if (recents.length) {
      html += '<div class="search-group" data-grp="recents">';
      html += '  <div class="search-group-label">Recherches r\u00e9centes <span class="grp-count">' + recents.length + '</span></div>';
      recents.forEach((q, i) => {
        html += '<button type="button" class="search-recent" data-recent="' + escapeHtml(q) + '" data-idx="' + i + '">';
        html += '  <span class="clock">' + ICONS.clock + '</span>';
        html += '  <span class="rec-text">' + escapeHtml(q) + '</span>';
        html += '  <span class="remove" data-remove="' + escapeHtml(q) + '" aria-label="Oublier cette recherche">' + ICONS.close + '</span>';
        html += '</button>';
      });
      html += '</div>';
    }

    // Curated "popular" suggestions — items the wiki team wants surfaced
    const popular = [
      DATA.find(d => d.title.startsWith('Le Sorcier')),
      DATA.find(d => d.title === 'Loup Solitaire'),
      DATA.find(d => d.title === 'Steve Jackson'),
      DATA.find(d => d.title === 'D\u00e9fis Fantastiques'),
    ].filter(Boolean);

    html += '<div class="search-group" data-grp="popular">';
    html += '  <div class="search-group-label">Souvent consult\u00e9s <span class="grp-count">' + popular.length + '</span></div>';
    popular.forEach(it => {
      html += renderResultRow(it, '');
    });
    html += '</div>';

    return html;
  }

  function renderResultRow(item, query) {
    return [
      '<a class="search-result" href="' + item.href + '" data-result="1" data-query-target="' + escapeHtml(item.title) + '">',
        thumbHtml(item.thumb),
        '<span class="info">',
          '<span class="ttl">' + highlight(item.title, query) + '</span>',
          '<span class="sub">' + highlight(item.sub, query) + '</span>',
        '</span>',
        '<span class="type-pip ' + item.type + '">' + (TYPE_PIP[item.type] || item.type) + '</span>',
      '</a>'
    ].join('');
  }

  function renderResults(items, query) {
    const grouped = {};
    items.forEach(it => {
      if (!grouped[it.type]) grouped[it.type] = [];
      grouped[it.type].push(it);
    });

    let html = '';
    TYPE_ORDER.forEach(type => {
      const arr = grouped[type];
      if (!arr || !arr.length) return;
      const shown = arr.slice(0, MAX_PER_GROUP);
      html += '<div class="search-group" data-grp="' + type + '">';
      html += '  <div class="search-group-label">' + TYPE_LABELS[type].label + ' <span class="grp-count">' + arr.length + '</span></div>';
      shown.forEach(it => { html += renderResultRow(it, query); });
      html += '</div>';
    });

    return html;
  }

  function renderNoResults(query) {
    return [
      '<div class="search-empty">',
      '  <div class="glyph">' + ICONS.search + '</div>',
      '  <div class="ttl">Aucun grimoire ne correspond</div>',
      '  <div class="sub">Rien pour <strong>\u00ab\u202f' + escapeHtml(query) + '\u202f\u00bb</strong>. Essaie un autre terme ou ouvre une <strong>suggestion</strong>.</div>',
      '</div>'
    ].join('');
  }

  function buildHead(query, total) {
    const left = query
      ? '<span class="count"><strong>' + total + '</strong> r\u00e9sultat' + (total > 1 ? 's' : '') + ' pour \u00ab\u202f' + escapeHtml(query) + '\u202f\u00bb</span>'
      : '<span class="count">Commence \u00e0 \u00e9crire\u2026</span>';
    const hint = '<span class="search-dd-hint">Navigation <kbd>\u2191</kbd><kbd>\u2193</kbd> <kbd>\u23ce</kbd></span>';
    return '<div class="search-dd-head">' + left + hint + '</div>';
  }

  function buildFoot(query) {
    const target = rootPrefix + 'pages/catalogue.html' + (query ? '?q=' + encodeURIComponent(query) : '');
    return [
      '<div class="search-dd-foot">',
        '<a href="' + target + '">Recherche avanc\u00e9e dans le Catalogue ' + ICONS.arrow + '</a>',
        '<span class="legend"><kbd>esc</kbd> fermer</span>',
      '</div>'
    ].join('');
  }

  /* ---------- Bind one form ---------- */
  function bindForm(form) {
    if (form.dataset.searchBound) return;
    form.dataset.searchBound = '1';
    const input = form.querySelector('input[type="search"], input');
    if (!input) return;

    // Container
    const dd = document.createElement('div');
    dd.className = 'search-dropdown';
    dd.setAttribute('role', 'listbox');
    dd.innerHTML = '';
    form.appendChild(dd);

    let activeIdx = -1;
    let currentItems = []; // {el, query?, recent?}
    let lastQuery = '';

    function gatherInteractive() {
      currentItems = Array.from(dd.querySelectorAll('[data-result], [data-recent]'));
      // Reset active state
      currentItems.forEach(el => el.classList.remove('is-active'));
      activeIdx = -1;
    }

    function setActive(idx) {
      if (!currentItems.length) return;
      activeIdx = (idx + currentItems.length) % currentItems.length;
      currentItems.forEach((el, i) => el.classList.toggle('is-active', i === activeIdx));
      const el = currentItems[activeIdx];
      if (el) el.scrollIntoView({ block: 'nearest' });
    }

    function open() {
      dd.classList.add('is-open');
    }
    function close() {
      dd.classList.remove('is-open');
      activeIdx = -1;
    }

    function render(query) {
      lastQuery = query;
      const q = query.trim();
      let body = '';
      let total = 0;
      if (!q) {
        body = renderEmpty(dd);
      } else {
        const items = filterData(q);
        total = items.length;
        body = items.length ? renderResults(items, q) : renderNoResults(q);
      }
      dd.innerHTML = buildHead(q, total) + '<div class="search-dd-body">' + body + '</div>' + buildFoot(q);
      gatherInteractive();
    }

    // Events on input
    input.addEventListener('focus', () => { render(input.value); open(); });
    input.addEventListener('input', () => { render(input.value); open(); });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!dd.classList.contains('is-open')) { render(input.value); open(); }
        setActive(activeIdx + 1);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(activeIdx - 1);
      } else if (e.key === 'Enter') {
        const el = currentItems[activeIdx];
        if (el) {
          e.preventDefault();
          el.click();
        } else if (input.value.trim()) {
          e.preventDefault();
          pushRecent(input.value);
          location.href = rootPrefix + 'pages/catalogue.html?q=' + encodeURIComponent(input.value.trim());
        }
      } else if (e.key === 'Escape') {
        if (dd.classList.contains('is-open')) {
          e.preventDefault();
          close();
          input.blur();
        }
      }
    });

    // Form submit (e.g. when user presses Enter without a highlighted item)
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = input.value.trim();
      if (!q) return;
      pushRecent(q);
      location.href = rootPrefix + 'pages/catalogue.html?q=' + encodeURIComponent(q);
    });

    // Click on a result row → store recent, let the browser follow the link
    dd.addEventListener('click', (e) => {
      const remove = e.target.closest('[data-remove]');
      if (remove) {
        e.preventDefault();
        e.stopPropagation();
        removeRecent(remove.dataset.remove);
        render(input.value);
        return;
      }
      const recent = e.target.closest('[data-recent]');
      if (recent) {
        e.preventDefault();
        input.value = recent.dataset.recent;
        render(input.value);
        input.focus();
        return;
      }
      const res = e.target.closest('[data-result]');
      if (res) {
        const q = input.value.trim();
        if (q) pushRecent(q);
        // let the anchor navigate normally
      }
    });

    // Click outside → close
    document.addEventListener('click', (e) => {
      if (form.contains(e.target)) return;
      close();
    });
  }

  function init() {
    document.querySelectorAll('form.sh-search').forEach(bindForm);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
