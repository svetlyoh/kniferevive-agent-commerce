(function () {
  'use strict';

  const drawer = document.querySelector('[data-kr-drawer]');
  const backdrop = document.querySelector('[data-kr-drawer-backdrop]');
  const openButtons = Array.from(document.querySelectorAll('[data-kr-open-menu]'));
  const closeButton = document.querySelector('[data-kr-close-menu]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let returnFocus = null;
  let closeTimer = null;

  document.querySelectorAll('[data-kr-price-filter]').forEach(function (form) {
    const prices = JSON.parse(form.getAttribute('data-prices') || '[]');
    const minRange = form.querySelector('[data-kr-price-min-range]');
    const maxRange = form.querySelector('[data-kr-price-max-range]');
    const minNumber = form.querySelector('[data-kr-price-min-number]');
    const maxNumber = form.querySelector('[data-kr-price-max-number]');
    const countLabel = form.querySelector('[data-kr-price-count]');
    const apply = form.querySelector('[data-kr-price-apply]');
    if (!minRange || !maxRange || !minNumber || !maxNumber || !countLabel || !apply) return;

    const floor = Number(minRange.min);
    const ceiling = Number(maxRange.max);
    const clamp = function (value) {
      return Math.max(floor, Math.min(ceiling, Number.isFinite(value) ? value : floor));
    };
    const update = function (source) {
      let min = clamp(Number(minRange.value));
      let max = clamp(Number(maxRange.value));
      if (source === minNumber) min = clamp(Number(minNumber.value));
      if (source === maxNumber) max = clamp(Number(maxNumber.value));
      if (source === minRange || source === minNumber) min = Math.min(min, max);
      if (source === maxRange || source === maxNumber) max = Math.max(max, min);

      minRange.value = String(min);
      maxRange.value = String(max);
      minNumber.value = String(min);
      maxNumber.value = String(max);
      minRange.style.zIndex = source === minRange || source === minNumber ? '2' : '1';
      maxRange.style.zIndex = source === maxRange || source === maxNumber ? '2' : '1';
      form.querySelector('.kr-price-filter__range').style.setProperty('--kr-price-low', (100 * (min - floor) / (ceiling - floor)) + '%');
      form.querySelector('.kr-price-filter__range').style.setProperty('--kr-price-high', (100 * (max - floor) / (ceiling - floor)) + '%');

      const count = prices.filter(function (price) { return price >= min && price <= max; }).length;
      countLabel.textContent = count + (count === 1 ? ' listing' : ' listings');
      apply.textContent = 'Show ' + count + (count === 1 ? ' listing' : ' listings');
      apply.disabled = count === 0;
    };

    [minRange, maxRange].forEach(function (input) {
      input.addEventListener('input', function () { update(input); });
      input.addEventListener('change', function () { update(input); });
    });
    [minNumber, maxNumber].forEach(function (input) {
      input.addEventListener('change', function () { update(input); });
    });
    form.addEventListener('submit', function (event) {
      const active = document.activeElement;
      update(active === minNumber || active === maxNumber ? active : null);
      if (apply.disabled) event.preventDefault();
    });
    update(null);
  });

  if (drawer && backdrop && closeButton) {
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const expandRequestedSection = function (section) {
      if (!section) return;
      const item = drawer.querySelector('[data-kr-menu-label="' + window.CSS.escape(section) + '"]');
      if (!item) return;
      const toggle = item.querySelector(':scope > .kr-menu-row > .kr-submenu-toggle');
      if (toggle && toggle.getAttribute('aria-expanded') !== 'true') {
        toggle.click();
      }
      item.scrollIntoView({ block: 'center', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    };

    const openDrawer = function (trigger, section) {
      window.clearTimeout(closeTimer);
      returnFocus = trigger || document.activeElement;
      const scroller = drawer.querySelector('.kr-drawer-scroll');
      if (scroller) scroller.scrollTop = 0;
      if (!section) {
        drawer.querySelectorAll('.kr-submenu-toggle[aria-expanded="true"]').forEach(function (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
          const panel = document.getElementById(toggle.getAttribute('aria-controls'));
          if (panel) panel.hidden = true;
        });
      }
      drawer.hidden = false;
      backdrop.hidden = false;
      document.body.classList.add('kr-drawer-open');
      openButtons.forEach(function (button) { button.setAttribute('aria-expanded', 'true'); });
      window.requestAnimationFrame(function () {
        drawer.classList.add('is-open');
        backdrop.classList.add('is-open');
        closeButton.focus({ preventScroll: true });
        expandRequestedSection(section);
      });
    };

    const closeDrawer = function () {
      if (drawer.hidden) return;
      drawer.classList.remove('is-open');
      backdrop.classList.remove('is-open');
      document.body.classList.remove('kr-drawer-open');
      openButtons.forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });
      const finish = function () {
        drawer.hidden = true;
        backdrop.hidden = true;
        if (returnFocus && typeof returnFocus.focus === 'function') {
          returnFocus.focus({ preventScroll: true });
        }
      };
      if (reducedMotion.matches) {
        finish();
      } else {
        closeTimer = window.setTimeout(finish, 240);
      }
    };

    openButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        openDrawer(button, button.getAttribute('data-kr-open-section'));
      });
    });
    closeButton.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    drawer.addEventListener('click', function (event) {
      const expandLink = event.target.closest('[data-kr-expand-submenu]');
      if (expandLink) {
        const expandButton = expandLink.closest('.kr-menu-row')?.querySelector('.kr-submenu-toggle');
        if (expandButton) { event.preventDefault(); expandButton.click(); return; }
      }
      const toggle = event.target.closest('.kr-submenu-toggle');
      if (toggle) {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        const willOpen = toggle.getAttribute('aria-expanded') !== 'true';
        const menu = toggle.closest('ul');
        if (willOpen && menu) {
          menu.querySelectorAll(':scope > .kr-menu-item > .kr-menu-row > .kr-submenu-toggle[aria-expanded="true"]').forEach(function (openToggle) {
            if (openToggle === toggle) return;
            openToggle.setAttribute('aria-expanded', 'false');
            const openPanel = document.getElementById(openToggle.getAttribute('aria-controls'));
            if (openPanel) openPanel.hidden = true;
          });
        }
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (panel) panel.hidden = !willOpen;
        return;
      }
      if (event.target.closest('a[href]')) {
        closeDrawer();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (drawer.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer();
        return;
      }
      if (event.key !== 'Tab') return;
      const focusable = Array.from(drawer.querySelectorAll(focusableSelector)).filter(function (element) {
        return !element.closest('[hidden]') && element.getClientRects().length > 0;
      });
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  const cartItemCountFromCookie = function () {
    const match = document.cookie.match(/(?:^|;\s*)woocommerce_items_in_cart=([^;]+)/);
    if (!match) return 0;
    const count = Number.parseInt(decodeURIComponent(match[1]), 10);
    return Number.isFinite(count) && count > 0 ? count : 0;
  };
  const syncHeaderCartCount = function () {
    const count = cartItemCountFromCookie();
    document.querySelectorAll('.kr-cart-link').forEach(function (link) {
      const countElement = link.querySelector('.kr-cart-count');
      if (countElement) countElement.textContent = String(count);
      link.setAttribute('aria-label', 'Cart, ' + count + (count === 1 ? ' item' : ' items'));
    });
  };
  const refreshCartFragments = function () {
    window.setTimeout(syncHeaderCartCount, 0);
    if (typeof window.jQuery === 'function') {
      window.jQuery(document.body).trigger('wc_fragment_refresh');
    }
  };
  document.body.addEventListener('wc-blocks_added_to_cart', refreshCartFragments);
  document.body.addEventListener('wc-blocks_removed_from_cart', refreshCartFragments);
  window.addEventListener('wc-blocks_store_sync_required', refreshCartFragments);

  const mobileFooter = window.matchMedia('(max-width: 767px)');
  const footerGroups = Array.from(document.querySelectorAll('.kr-footer-group'));
  const syncFooterGroups = function () {
    footerGroups.forEach(function (group) {
      const summary = group.querySelector(':scope > summary');
      if (mobileFooter.matches) {
        if (summary) {
          summary.removeAttribute('tabindex');
          summary.removeAttribute('aria-disabled');
        }
        if (group.dataset.krTouched !== 'true') group.open = false;
      } else {
        group.open = true;
        if (summary) {
          summary.setAttribute('tabindex', '-1');
          summary.setAttribute('aria-disabled', 'true');
        }
      }
    });
  };
  footerGroups.forEach(function (group) {
    group.addEventListener('toggle', function () {
      if (mobileFooter.matches) group.dataset.krTouched = 'true';
    });
  });
  syncFooterGroups();
  if (typeof mobileFooter.addEventListener === 'function') {
    mobileFooter.addEventListener('change', syncFooterGroups);
  }
})();
