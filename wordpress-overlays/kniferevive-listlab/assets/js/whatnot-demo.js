(() => {
  'use strict';
  const refresh = () => {
    const now = Date.now();
    document.querySelectorAll('.kr-whatnot-demo[data-whatnot-start]').forEach((element) => {
      const starts = Date.parse(element.dataset.whatnotStart || '');
      if (Number.isFinite(starts) && now >= starts) element.hidden = true;
    });
  };
  refresh();
  document.addEventListener('DOMContentLoaded', refresh);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  setInterval(refresh, 30000);
})();
