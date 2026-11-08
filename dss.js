(function () {
  'use strict';

  const DSS = {
    load: function (selector, mode, extraContext) {
      const el = document.querySelector(selector);
      if (!el) return;

      const prev = el.innerHTML;
      el.innerHTML = '<div class="dss-loading">Loading…</div>';

      // Lightweight, dependency-free placeholder behaviour.
      // When a real Ollama backend is wired up, swap this for a fetch() call.
      window.setTimeout(function () {
        const note = document.createElement('p');
        note.className = 'dss-err';
        note.textContent = 'Decision support is not connected in this build. Configure the DSS backend to enable AI responses.';
        el.innerHTML = '';
        el.appendChild(note);

        if (extraContext) {
          const ctx = document.createElement('p');
          ctx.className = 'dss-warn';
          ctx.textContent = 'Last context: ' + extraContext;
          el.appendChild(ctx);
        }
      }, 450);

      return { selector: selector, mode: mode, prev: prev };
    }
  };

  window.DSS = DSS;

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-dss]').forEach(function (el) {
      if (!el.textContent.trim()) {
        const p = document.createElement('p');
        p.className = 'dss-empty';
        p.textContent = 'Ready. Click refresh to load.';
        el.appendChild(p);
      }
    });
  });
})();
