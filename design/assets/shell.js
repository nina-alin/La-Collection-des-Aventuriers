/* Shared shell: theme toggle (persistent) + active nav highlight */
(function () {
  const KEY = "lca.theme";
  const saved = localStorage.getItem(KEY);
  const sys = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  document.documentElement.setAttribute("data-theme", saved || sys);

  function setTheme(t) {
    document.documentElement.setAttribute("data-theme", t);
    localStorage.setItem(KEY, t);
    document.querySelectorAll("[data-theme-label]").forEach(el => {
      el.textContent = t === "dark" ? "Grimoire" : "Parchemin";
    });
    document.querySelectorAll("[data-theme-icon]").forEach(el => {
      el.textContent = t === "dark" ? "☾" : "☀";
    });
  }

  window.addEventListener("DOMContentLoaded", () => {
    setTheme(document.documentElement.getAttribute("data-theme"));

    document.querySelectorAll("[data-theme-toggle]").forEach(btn => {
      btn.addEventListener("click", () => {
        const cur = document.documentElement.getAttribute("data-theme");
        setTheme(cur === "dark" ? "light" : "dark");
      });
    });

    // Highlight current page in nav based on filename
    const path = location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".ds-nav-link, .ds-nav-mobile a").forEach(a => {
      const href = a.getAttribute("href").split("/").pop();
      if (href === path) a.setAttribute("aria-current", "page");
    });
  });
})();
