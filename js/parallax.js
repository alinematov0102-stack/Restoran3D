/* parallax.js - butun loyiha uchun parallax, atmosfera foni va scroll animatsiyalari.
   Faqat kosmetik: sahifa mantig'iga tegmaydi. Ulanmasa ham sayt ishlayveradi.

   Ishlatish (HTML da):
     data-px="0.25"      scroll tezligi: musbat = sekinroq, manfiy = tezroq ko'rinadi (chuqurlik)
     data-px-m="24"      sichqoncha bilan siljish (px)
     data-px-max="110"   maksimal siljish (px)                                              */
(function () {
  'use strict';

  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  var root = document.documentElement;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var isAdminPanel = !!document.querySelector('.admin-content');
    var vh = window.innerHeight;

    /* ---------- 1. Atmosfera foni (admin ish paneldan tashqari hamma sahifada) ---------- */
    if (!isAdminPanel) {
      root.classList.add('px-on');
      var bg = document.createElement('div');
      bg.className = 'px-bg';
      bg.setAttribute('aria-hidden', 'true');
      var html = '' +
        '<i class="px-orb o1" data-px="0.10" data-px-m="30"></i>' +
        '<i class="px-orb o2" data-px="-0.08" data-px-m="-40"></i>' +
        '<i class="px-orb o3" data-px="0.14" data-px-m="24"></i>' +
        '<i class="px-ring r1" data-px="0.12" data-px-m="-18"></i>' +
        '<i class="px-ring r2" data-px="-0.10" data-px-m="22"></i>';
      for (var d = 0; d < 16; d++) {
        var size = 3 + Math.random() * 6;
        html += '<i class="px-dust" data-px-dust="' + (0.18 + Math.random() * 0.4).toFixed(2) + '" ' +
          'style="left:' + (Math.random() * 100).toFixed(1) + '%;width:' + size.toFixed(1) + 'px;height:' + size.toFixed(1) + 'px;' +
          'animation-delay:' + (-Math.random() * 4).toFixed(1) + 's" data-ph="' + Math.floor(Math.random() * 900) + '"></i>';
      }
      html += '<i class="px-grain"></i>';
      bg.innerHTML = html;
      document.body.insertBefore(bg, document.body.firstChild);
    }

    /* ---------- 2. Header: scroll bo'lganda ixchamlashadi ---------- */
    var header = document.querySelector('header');
    function onScrollHeader() {
      if (header) header.classList.toggle('is-scrolled', window.pageYOffset > 24);
    }

    /* ---------- 3. Scroll bilan paydo bo'lish ---------- */
    var targets = document.querySelectorAll(
      '.food-section-head, .food-categories, .categories, .container > h1, .container > h2, .hero-subtitle,' +
      '.cart-page > *, .checkout-page > *, .myorders-head, .my-order, .form-box, .success-box,' +
      '.page-topline, .metric-strip > div, .admin-panel'
    );
    var list = [].slice.call(targets).filter(function (el) { return !el.closest('.food-card'); });
    if (!reduce && 'IntersectionObserver' in window && list.length) {
      root.classList.add('px-js');
      var io = new IntersectionObserver(function (entries) {
        var vis = entries.filter(function (e) { return e.isIntersecting; })
          .sort(function (a, b) { return (a.target.compareDocumentPosition(b.target) & 4) ? -1 : 1; });
        vis.forEach(function (e, k) {
          var t = e.target;
          t.style.transitionDelay = Math.min(k, 6) * 70 + 'ms';
          t.classList.add('px-in');
          io.unobserve(t);
          setTimeout(function () { t.style.transitionDelay = ''; }, 1400);
        });
      }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
      list.forEach(function (el) { el.classList.add('px-reveal'); io.observe(el); });
      setTimeout(function () { list.forEach(function (el) { el.classList.add('px-in'); }); }, 4500); // zaxira
    }

    /* ---------- 4. Parallax dvigateli ---------- */
    if (reduce) { onScrollHeader(); window.addEventListener('scroll', onScrollHeader, { passive: true }); return; }

    var items = [];
    function collect() {
      items = [];
      [].slice.call(document.querySelectorAll('[data-px], [data-px-m], [data-px-dust]')).forEach(function (el) {
        var inBg = !!el.closest('.px-bg');
        items.push({
          el: el,
          s: parseFloat(el.getAttribute('data-px')) || 0,
          m: parseFloat(el.getAttribute('data-px-m')) || 0,
          max: parseFloat(el.getAttribute('data-px-max')) || 0,
          dust: parseFloat(el.getAttribute('data-px-dust')) || 0,
          ph: parseFloat(el.getAttribute('data-ph')) || 0,
          fixed: inBg,
          base: 0, h: 0
        });
      });
    }
    function measure() {
      vh = window.innerHeight;
      var sy = window.pageYOffset;
      items.forEach(function (it) {
        if (it.fixed) return;
        var prev = it.el.style.translate;
        it.el.style.translate = 'none';
        var r = it.el.getBoundingClientRect();
        it.base = r.top + sy + r.height / 2;
        it.h = r.height;
        it.el.style.translate = prev;
      });
    }

    var tx = 0, ty = 0, mx = 0, my = 0, raf = 0;
    function frame() {
      raf = 0;
      var sy = window.pageYOffset;
      mx += (tx - mx) * 0.08;
      my += (ty - my) * 0.08;
      for (var i = 0; i < items.length; i++) {
        var it = items[i], y = 0;
        if (it.dust) {
          y = -(((sy * it.dust) + it.ph) % (vh + 120)) - 10;
        } else if (it.fixed) {
          y = -sy * it.s;
        } else {
          var dlt = sy + vh / 2 - it.base;
          if (Math.abs(dlt) > vh * 1.5 + it.h) continue;      // ko'rinmayotganlarni hisoblamaymiz
          y = dlt * it.s;
        }
        if (it.max) y = Math.max(-it.max, Math.min(it.max, y));
        var x = fine ? mx * it.m : 0;
        var yy = y + (fine ? my * it.m * 0.6 : 0);
        it.el.style.translate = x.toFixed(1) + 'px ' + yy.toFixed(1) + 'px';
      }
      if (Math.abs(tx - mx) > 0.002 || Math.abs(ty - my) > 0.002) raf = requestAnimationFrame(frame);
    }
    function request() { if (!raf) raf = requestAnimationFrame(frame); }

    collect(); measure(); request();
    window.addEventListener('scroll', function () { onScrollHeader(); request(); }, { passive: true });
    window.addEventListener('resize', function () { measure(); request(); });
    window.addEventListener('load', function () { measure(); request(); });
    setTimeout(function () { measure(); request(); }, 1200);
    onScrollHeader();

    if (fine) {
      window.addEventListener('pointermove', function (e) {
        if (e.pointerType && e.pointerType !== 'mouse') return;
        tx = (e.clientX / window.innerWidth - 0.5) * 2;
        ty = (e.clientY / window.innerHeight - 0.5) * 2;
        request();
      }, { passive: true });
    }
  });
})();
