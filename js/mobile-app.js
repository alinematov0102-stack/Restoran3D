// mobile-app.js - telefon uchun: yon menyu, savatga tez qo'shish (sahifa yangilanmaydi), xabar
(function () {
  'use strict';
  var body = document.body;
  window.ffDrawer = function (open) {
    body.classList.toggle('m-drawer-open', !!open);
    var d = document.getElementById('mDrawer');
    if (d) d.setAttribute('aria-hidden', open ? 'false' : 'true');
  };
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.ffDrawer(false); });
  window.addEventListener('resize', function () { if (window.innerWidth > 820) window.ffDrawer(false); });

  var toastTimer;
  function toast() {
    var t = document.getElementById('mToast'); if (!t) return;
    t.classList.add('show'); clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('show'); }, 1600);
  }
  function setCount(n) {
    document.querySelectorAll('[data-cart-count]').forEach(function (el) {
      el.textContent = n; if (el.hasAttribute('hidden') || el.classList.contains('m-fab-badge')) el.hidden = !(n > 0);
    });
    document.querySelectorAll('.cart-count').forEach(function (el) { el.textContent = n; });
  }

  // "Savatga qo'shish" havolalari: sahifani qayta yuklamasdan qo'shamiz
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href*="cart.php?action=add"]');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();
    if (a.classList.contains('is-loading')) return;
    a.classList.add('is-loading');
    var url = a.getAttribute('href') + (a.getAttribute('href').indexOf('?') > -1 ? '&' : '?') + 'ajax=1';
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        a.classList.remove('is-loading');
        if (j && j.ok) { setCount(j.count); toast(); if (navigator.vibrate) navigator.vibrate(15); }
        else window.location.href = a.href;
      })
      .catch(function () { window.location.href = a.href; });
  });

  // Faol tabni ko'rinadigan joyga suramiz
  document.addEventListener('DOMContentLoaded', function () {
    var on = document.querySelector('.m-tabbar a.on');
    if (on && on.scrollIntoView) { try { on.scrollIntoView({ inline: 'center', block: 'nearest' }); } catch (e) {} }
  });
})();
