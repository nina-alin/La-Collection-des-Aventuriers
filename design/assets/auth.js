/* La Collection des Aventuriers — comportements du bloc auth
   - bascule afficher/masquer le mot de passe
   - jauge de robustesse + checklist d'exigences
   - validation légère côté client (démo maquette) */
(function () {
  "use strict";

  /* ---- afficher / masquer ---- */
  document.querySelectorAll("[data-toggle-pw]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var sel = btn.getAttribute("data-toggle-pw");
      var input = document.querySelector(sel);
      if (!input) return;
      var show = input.type === "password";
      input.type = show ? "text" : "password";
      btn.setAttribute("aria-pressed", String(show));
      btn.setAttribute("aria-label", show ? "Masquer le mot de passe" : "Afficher le mot de passe");
    });
  });

  /* ---- score de robustesse ---- */
  function scorePassword(pw) {
    var score = 0;
    if (!pw) return 0;
    if (pw.length >= 8) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    if (pw.length >= 12 && score >= 3) score = 4;
    return Math.min(4, score);
  }
  var LABELS = ["—", "Fragile", "Correct", "Solide", "Inviolable"];

  document.querySelectorAll("[data-pw-source]").forEach(function (input) {
    var meter = document.querySelector(input.getAttribute("data-pw-meter") || "");
    var reqsBox = document.querySelector(input.getAttribute("data-pw-reqs") || "");
    var label = meter ? meter.querySelector(".lvl") : null;

    function refresh() {
      var pw = input.value;
      var s = scorePassword(pw);
      if (meter) {
        meter.setAttribute("data-score", String(s));
        if (label) label.textContent = LABELS[s];
      }
      if (reqsBox) {
        reqsBox.querySelectorAll("[data-req]").forEach(function (li) {
          var rule = li.getAttribute("data-req");
          var ok = false;
          if (rule === "len") ok = pw.length >= 8;
          else if (rule === "case") ok = /[a-z]/.test(pw) && /[A-Z]/.test(pw);
          else if (rule === "num") ok = /\d/.test(pw);
          else if (rule === "sym") ok = /[^A-Za-z0-9]/.test(pw);
          li.classList.toggle("met", ok);
        });
      }
    }
    input.addEventListener("input", refresh);
    refresh();
  });

  /* ---- confirmation visuelle (démo) : sur submit, bascule l'état ---- */
  document.querySelectorAll("[data-auth-demo]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var target = form.getAttribute("data-success-target");
      var formView = form.getAttribute("data-form-view");
      if (target && formView) {
        var t = document.querySelector(target);
        var f = document.querySelector(formView);
        if (t && f) { f.hidden = true; t.hidden = false; t.scrollIntoView ? null : null; }
      } else {
        // navigation par défaut vers le tableau de bord
        var go = form.getAttribute("data-redirect");
        if (go) window.location.href = go;
      }
    });
  });
})();
