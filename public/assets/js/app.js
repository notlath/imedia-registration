/**
 * IMedia Registration — Admin UI bootstrap.
 *
 * Pure vanilla JS. No bundler, no framework. Six responsibilities:
 *   1. Theme toggle (light / dark / auto). Persists to localStorage.
 *   2. Watch prefers-color-scheme and update charts on change.
 *   3. Chart.js palette injection: read the brand colors from CSS custom
 *      properties at render time so dark mode flips correctly.
 *   4. Sidebar drawer on mobile (the menu button toggles it).
 *   5. Native <dialog> open/close buttons (data-imreg-open-dialog / data-imreg-close-dialog).
 *   6. Confirm-before-submit for delete / bulk forms (data-imreg-confirm).
 *   7. Flash auto-dismiss (6s, reduced-motion-aware).
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------
  // 1. Theme toggle
  // ---------------------------------------------------------------------

  var STORAGE_KEY = 'imreg-theme';
  var THEMES = { LIGHT: 'light', DARK: 'dark', AUTO: 'auto' };

  function currentTheme() {
    try {
      var v = localStorage.getItem(STORAGE_KEY);
      return v === THEMES.LIGHT || v === THEMES.DARK || v === THEMES.AUTO ? v : THEMES.AUTO;
    } catch (e) {
      return THEMES.AUTO;
    }
  }

  function applyTheme(theme) {
    var dark = theme === THEMES.DARK
      || (theme === THEMES.AUTO
          && window.matchMedia
          && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
  }

  function persistTheme(theme) {
    try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) { /* localStorage may be blocked */ }
    applyTheme(theme);
  }

  function nextTheme(current) {
    return current === THEMES.LIGHT ? THEMES.DARK : (current === THEMES.DARK ? THEMES.AUTO : THEMES.LIGHT);
  }

  function initThemeToggle() {
    var btn = document.querySelector('[data-imreg-theme-toggle]');
    if (!btn) return;
    var labelMap = {
      light: 'Switch to dark mode',
      dark:  'Use system preference',
      auto:  'Switch to light mode',
    };
    function update() {
      var t = currentTheme();
      btn.setAttribute('aria-label', labelMap[t]);
      btn.setAttribute('title', labelMap[t]);
    }
    update();
    btn.addEventListener('click', function () {
      persistTheme(nextTheme(currentTheme()));
      update();
      // Re-paint charts with the new palette.
      refreshCharts();
    });
    // React to OS preference changes only when in 'auto'.
    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      var onChange = function () { if (currentTheme() === THEMES.AUTO) { applyTheme(THEMES.AUTO); refreshCharts(); } };
      if (mq.addEventListener) mq.addEventListener('change', onChange);
      else if (mq.addListener) mq.addListener(onChange);
    }
  }

  // ---------------------------------------------------------------------
  // 2. Chart.js palette injection
  // ---------------------------------------------------------------------

  var chartInstances = [];

  function readVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  function palette() {
    var isDark = document.documentElement.classList.contains('dark');
    return {
      primary:        readVar('--color-primary'),
      secondary:      readVar('--color-secondary'),
      tertiary:       readVar('--color-tertiary'),
      onSurface:      readVar('--color-on-surface'),
      outline:        readVar('--color-outline-variant'),
      surfaceLowest:  readVar('--color-surface-container-lowest'),
      statusPalette:  isDark
        ? [
            readVar('--color-status-pending-fg'),
            readVar('--color-status-tentative-fg'),
            readVar('--color-status-confirm-fg'),
            readVar('--color-status-forfeit-fg'),
            readVar('--color-status-reschedule-fg'),
          ]
        : [
            readVar('--color-status-pending-bg'),
            readVar('--color-status-tentative-bg'),
            readVar('--color-status-confirm-bg'),
            readVar('--color-status-forfeit-bg'),
            readVar('--color-status-reschedule-bg'),
          ],
    };
  }

  function mergePalette(cfg) {
    var p = palette();
    if (!cfg || !cfg.data || !cfg.data.datasets) return cfg;
    var ds = cfg.data.datasets;
    if (cfg.type === 'line') {
      ds[0].borderColor     = p.primary;
      ds[0].backgroundColor = p.primary + '1a'; // 10% alpha
    } else if (cfg.type === 'bar') {
      ds[0].backgroundColor = p.primary;
      ds[0].borderColor     = p.primary;
    } else if (cfg.type === 'doughnut' || cfg.type === 'pie') {
      ds[0].backgroundColor = p.statusPalette;
      ds[0].borderColor     = p.surfaceLowest;
    }
    if (cfg.options && cfg.options.scales) {
      if (cfg.options.scales.y) {
        cfg.options.scales.y.ticks = cfg.options.scales.y.ticks || {};
        cfg.options.scales.y.ticks.color = p.onSurface;
        cfg.options.scales.y.grid = { color: p.outline };
      }
      if (cfg.options.scales.x) {
        cfg.options.scales.x.ticks = cfg.options.scales.x.ticks || {};
        cfg.options.scales.x.ticks.color = p.onSurface;
        cfg.options.scales.x.grid = { color: p.outline };
      }
    }
    if (cfg.options && cfg.options.plugins && cfg.options.plugins.legend && cfg.options.plugins.legend.labels) {
      cfg.options.plugins.legend.labels.color = p.onSurface;
    }
    return cfg;
  }

  function initCharts() {
    if (typeof window.Chart === 'undefined') return;
    var scripts = document.querySelectorAll('script[type="application/json"][data-chart-config]');
    scripts.forEach(function (script) {
      var id = script.getAttribute('data-chart-config');
      var canvas = document.getElementById(id);
      if (!canvas) return;
      var cfg;
      try { cfg = JSON.parse(script.textContent || '{}'); }
      catch (e) { console.warn('imreg: bad chart config for', id, e); return; }
      try {
        var instance = new window.Chart(canvas.getContext('2d'), mergePalette(cfg));
        chartInstances.push(instance);
      } catch (e) {
        console.warn('imreg: chart init failed for', id, e);
      }
    });
  }

  function refreshCharts() {
    if (chartInstances.length === 0) return;
    // The palette has changed; we need to re-read the configs and re-apply.
    // Easiest path: destroy and rebuild.
    var scripts = document.querySelectorAll('script[type="application/json"][data-chart-config]');
    chartInstances.forEach(function (c, i) { try { c.destroy(); } catch (e) {} });
    chartInstances = [];
    initCharts();
  }

  // ---------------------------------------------------------------------
  // 3. Mobile sidebar drawer
  // ---------------------------------------------------------------------

  function initDrawer() {
    var shell = document.querySelector('.imreg-shell');
    if (!shell) return;
    var toggle = document.querySelector('[data-imreg-drawer-toggle]');
    var backdrop = document.querySelector('[data-imreg-drawer-backdrop]');
    if (!toggle || !backdrop) return;

    function set(open) {
      shell.setAttribute('data-sidebar-open', open ? 'true' : 'false');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    }

    toggle.addEventListener('click', function () {
      set(shell.getAttribute('data-sidebar-open') !== 'true');
    });
    backdrop.addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && shell.getAttribute('data-sidebar-open') === 'true') {
        set(false);
      }
    });
  }

  // ---------------------------------------------------------------------
  // 4. Native <dialog> open/close buttons
  // ---------------------------------------------------------------------

  function initDialogs() {
    document.querySelectorAll('[data-imreg-open-dialog]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-imreg-open-dialog');
        var dlg = document.getElementById(id);
        if (dlg && typeof dlg.showModal === 'function') dlg.showModal();
      });
    });
    document.querySelectorAll('[data-imreg-close-dialog]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var dlg = btn.closest('dialog');
        if (dlg && typeof dlg.close === 'function') dlg.close();
      });
    });
  }

  // ---------------------------------------------------------------------
  // 5. Confirm-before-submit (delete / bulk)
  // ---------------------------------------------------------------------

  function initConfirms() {
    document.querySelectorAll('form[data-imreg-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var msg = form.getAttribute('data-imreg-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
      });
    });
  }

  // ---------------------------------------------------------------------
  // 6. Flash auto-dismiss (6s, reduced-motion-aware)
  // ---------------------------------------------------------------------

  function initFlashDismiss() {
    var flashes = document.querySelectorAll('.imreg-flash');
    if (flashes.length === 0) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    flashes.forEach(function (f) {
      window.setTimeout(function () {
        if (reduce) {
          f.remove();
          return;
        }
        f.style.transition = 'opacity 0.3s ease';
        f.style.opacity = '0';
        window.setTimeout(function () { f.remove(); }, 350);
      }, 6000);
    });
  }

  // ---------------------------------------------------------------------
  // Bootstrap
  // ---------------------------------------------------------------------

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    initThemeToggle();
    initCharts();
    initDrawer();
    initDialogs();
    initConfirms();
    initFlashDismiss();
  });
})();
