/* ff-3d.js - FastFood bo'limi uchun premium 3D animatsiyalar.
   Faqat kosmetik: sahifa mantig'iga tegmaydi. Fayl ulanmasa ham sayt ishlayveradi. */
(function () {
  'use strict';

  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var cards = [].slice.call(document.querySelectorAll('.product-grid .food-card'));

    /* ---------- 1. Sahifaga kirish (scroll bilan birin-ketin) ---------- */
    cards.forEach(function (c, i) { c.style.setProperty('--i', i % 8); });

    if (!('IntersectionObserver' in window) || reduce) {
      cards.forEach(function (c) { c.classList.add('ff-in'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        var visible = entries.filter(function (e) { return e.isIntersecting; })
          .sort(function (a, b) {
            return (a.target.compareDocumentPosition(b.target) & 4) ? -1 : 1;
          });
        visible.forEach(function (e, k) {
          e.target.style.animationDelay = (k * 90) + 'ms';
          e.target.classList.add('ff-in');
          io.unobserve(e.target);
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      cards.forEach(function (c) { io.observe(c); });
      // Xavfsizlik: nimadir xato ketsa, kartochkalar yashirin qolib ketmasin
      setTimeout(function () {
        cards.forEach(function (c) { c.classList.add('ff-in'); });
      }, 4000);
    }

    /* ---------- 2. Kartochka: sichqoncha bilan 3D egilish ---------- */
    if (finePointer && !reduce) {
      cards.forEach(function (card) {
        var glare = document.createElement('span');
        glare.className = 'ff-glare';
        card.appendChild(glare);

        var raf = 0, px = 0.5, py = 0.5;

        function apply() {
          raf = 0;
          var dx = px - 0.5, dy = py - 0.5;
          card.style.setProperty('--ry', (dx * 14).toFixed(2) + 'deg');
          card.style.setProperty('--rx', (-dy * 11).toFixed(2) + 'deg');
          card.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
          card.style.setProperty('--my', (py * 100).toFixed(1) + '%');
          card.style.setProperty('--sx', (-dx * 34).toFixed(1) + 'px');
          card.style.setProperty('--sy', (-dy * 22).toFixed(1) + 'px');
          card.style.setProperty('--ix', (-dx * 16).toFixed(1) + 'px');
          card.style.setProperty('--iy', (-dy * 12).toFixed(1) + 'px');
        }

        function reset() {
          ['--rx', '--ry', '--sx', '--sy', '--ix', '--iy'].forEach(function (v) {
            card.style.removeProperty(v);
          });
          card.style.setProperty('--mx', '50%');
          card.style.setProperty('--my', '50%');
        }

        card.addEventListener('pointerenter', function (e) {
          if (e.pointerType && e.pointerType !== 'mouse') return;
          card.classList.add('ff-hot');
        });
        card.addEventListener('pointermove', function (e) {
          if (e.pointerType && e.pointerType !== 'mouse') return;
          var r = card.getBoundingClientRect();
          px = (e.clientX - r.left) / r.width;
          py = (e.clientY - r.top) / r.height;
          if (!raf) raf = requestAnimationFrame(apply);
        });
        card.addEventListener('pointerleave', function () {
          card.classList.remove('ff-hot');
          if (raf) { cancelAnimationFrame(raf); raf = 0; }
          reset();
        });
      });
    }

    /* ---------- 3. Hero: parallax ---------- */
    var hero = document.querySelector('.hero-fit-brand');
    if (hero && finePointer && !reduce) {
      var hraf = 0, hx = 0, hy = 0;
      var heroSection = hero.closest('.hero-fit') || hero;
      heroSection.addEventListener('pointermove', function (e) {
        var r = hero.getBoundingClientRect();
        hx = Math.max(-1, Math.min(1, ((e.clientX - r.left) / r.width - 0.5) * 2));
        hy = Math.max(-1, Math.min(1, ((e.clientY - r.top) / r.height - 0.5) * 2));
        if (!hraf) hraf = requestAnimationFrame(function () {
          hraf = 0;
          hero.style.setProperty('--hx', hx.toFixed(3));
          hero.style.setProperty('--hy', hy.toFixed(3));
        });
      });
      heroSection.addEventListener('pointerleave', function () {
        hero.style.setProperty('--hx', 0);
        hero.style.setProperty('--hy', 0);
      });
    }

    /* ---------- 4. Modal: rasmni sichqoncha bilan egish ---------- */
    var stage = document.getElementById('pmStage');
    if (stage && finePointer && !reduce) {
      stage.addEventListener('pointermove', function (e) {
        if (stage.classList.contains('mode-3d')) return; // 3D rejimda model o'zi aylanadi
        var r = stage.getBoundingClientRect();
        var dx = (e.clientX - r.left) / r.width - 0.5;
        var dy = (e.clientY - r.top) / r.height - 0.5;
        stage.style.setProperty('--ry', (dx * 10).toFixed(2) + 'deg');
        stage.style.setProperty('--rx', (-dy * 8).toFixed(2) + 'deg');
      });
      stage.addEventListener('pointerleave', function () {
        stage.style.setProperty('--ry', '0deg');
        stage.style.setProperty('--rx', '0deg');
      });
    }

    /* ---------- 5. "Savatga" bosilganda taom savat tomon uchib boradi ---------- */
    var flying = false;
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('.food-card a.btn[href*="action=add"]');
      if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      var card = a.closest('.food-card');
      var img = card && card.querySelector('.ff-media img');
      var cart = document.querySelector('header a[href*="cart.php"]');
      if (!img || !cart || reduce || !Element.prototype.animate) return; // oddiy o'tish

      e.preventDefault();
      if (flying) return;
      flying = true;
      var href = a.href;

      var from = img.getBoundingClientRect();
      var to = cart.getBoundingClientRect();
      var size = 96;
      var ghost = document.createElement('div');
      ghost.className = 'ff-fly';
      ghost.style.left = (from.left + from.width / 2 - size / 2) + 'px';
      ghost.style.top = (from.top + from.height / 2 - size / 2) + 'px';
      ghost.style.width = ghost.style.height = size + 'px';
      ghost.style.backgroundImage = 'url("' + (img.currentSrc || img.src).replace(/"/g, '%22') + '")';
      document.body.appendChild(ghost);

      var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
      var dy = (to.top + to.height / 2) - (from.top + from.height / 2);

      var anim = ghost.animate([
        { transform: 'perspective(600px) translate(0,0) scale(1) rotateY(0deg)', opacity: 1 },
        { transform: 'perspective(600px) translate(' + dx * 0.45 + 'px,' + (dy * 0.45 - 90) + 'px) scale(.8) rotateY(180deg)', opacity: 1, offset: 0.5 },
        { transform: 'perspective(600px) translate(' + dx + 'px,' + dy + 'px) scale(.12) rotateY(360deg)', opacity: 0.7 }
      ], { duration: 720, easing: 'cubic-bezier(.5,0,.3,1)', fill: 'forwards' });

      function go() { ghost.remove(); cart.classList.add('ff-bump'); window.location.href = href; }
      anim.onfinish = go;
      setTimeout(function () { if (flying) go(); }, 1500); // zaxira
    });
  });
})();
