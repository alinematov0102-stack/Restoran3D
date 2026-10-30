// universal-i18n.js - sahifadagi HAMMA matnni tanlangan tilga o'tkazadi.
// Matn, placeholder, title, tugma, select ro'yxati va keyin qo'shilgan (dinamik) kontentni ham qamrab oladi.
(function () {
  'use strict';
  var LANGS = ['uz', 'ru', 'en'];
  var SRC = (document.currentScript && document.currentScript.src) || '';
  var ENDPOINT = SRC ? new URL('../translate_ui.php', SRC).href : '/translate_ui.php';
  var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, TEXTAREA: 1, CODE: 1, PRE: 1, 'MODEL-VIEWER': 1, SVG: 1 };
  var ATTRS = ['placeholder', 'title', 'aria-label', 'alt'];
  var LETTER = /[A-Za-zА-Яа-яЁёЎўҚқҒғҲҳ]/;
  var CYR = /[А-Яа-яЁё]/;
  var BATCH = 60, PARALLEL = 3, MAX_LEN = 300;
  var cache = {}, done = {}, pending = {}, timer = null, busy = false, observer = null, lang = 'uz', cacheKey = '';

  function getLang() { var m = document.cookie.match(/(?:^|; )ff_lang=([^;]+)/); return m ? m[1] : 'uz'; }
  function skipEl(el) {
    if (!el || SKIP_TAGS[el.tagName]) return true;
    return !!(el.closest && el.closest('[data-no-translate],.no-translate,.notranslate,[contenteditable="true"]'));
  }
  function want(t) {
    if (!t || t.length > MAX_LEN || !LETTER.test(t)) return false;
    if (done[t]) return false;                 // bu allaqachon tarjima natijasi
    if (lang === 'ru' && CYR.test(t)) return false;
    return true;
  }
  // Sahifadagi tarjima qilinadigan joylarni yig'adi
  function scan() {
    var items = [];
    var w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    while (w.nextNode()) {
      var n = w.currentNode, p = n.parentElement;
      if (!p || skipEl(p)) continue;
      var t = n.nodeValue.trim();
      if (want(t)) items.push({ node: n, text: t });
    }
    document.querySelectorAll('[placeholder],[title],[aria-label],[alt]').forEach(function (el) {
      if (skipEl(el)) return;
      ATTRS.forEach(function (a) {
        var v = el.getAttribute(a);
        if (v && want(v.trim())) items.push({ el: el, attr: a, text: v.trim() });
      });
    });
    document.querySelectorAll('input[type=submit],input[type=button]').forEach(function (el) {
      var v = el.value; if (v && want(v.trim())) items.push({ el: el, attr: 'value', text: v.trim() });
    });
    return items;
  }
  function applyItems(items) {
    if (observer) observer.disconnect();
    items.forEach(function (it) {
      var tr = cache[it.text];
      if (!tr || tr === it.text) return;
      if (it.node) { if (it.node.nodeValue.indexOf(it.text) > -1) it.node.nodeValue = it.node.nodeValue.replace(it.text, tr); }
      else if (it.el.getAttribute(it.attr) !== null || it.attr === 'value') {
        if (it.attr === 'value') it.el.value = tr; else it.el.setAttribute(it.attr, tr);
      }
      done[tr] = 1;
    });
    document.documentElement.lang = lang;
    observe();
  }
  function save() {
    try {
      var keys = Object.keys(cache);
      if (keys.length > 4000) { keys.slice(0, keys.length - 3000).forEach(function (k) { delete cache[k]; }); }
      localStorage.setItem(cacheKey, JSON.stringify(cache));
    } catch (e) {}
  }
  function post(texts) {
    return fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ target: lang, texts: texts }) })
      .then(function (r) { return r.json(); })
      .then(function (j) { var tr = j.translations || {}; Object.keys(tr).forEach(function (k) { cache[k] = tr[k]; }); })
      .catch(function () {});
  }
  function run() {
    if (busy) { schedule(); return; }
    lang = getLang();
    if (LANGS.indexOf(lang) < 0 || lang === 'uz') return;
    busy = true;
    var items = scan();
    applyItems(items.filter(function (i) { return cache[i.text]; }));   // keshdagini darhol qo'llaymiz
    var miss = {};
    items.forEach(function (i) { if (!cache[i.text] && !pending[i.text]) miss[i.text] = 1; });
    var list = Object.keys(miss);
    if (!list.length) { busy = false; return; }
    list.forEach(function (t) { pending[t] = 1; });
    var batches = [];
    for (var i = 0; i < list.length; i += BATCH) batches.push(list.slice(i, i + BATCH));
    var idx = 0;
    function worker() {
      if (idx >= batches.length) return Promise.resolve();
      var b = batches[idx++];
      return post(b).then(function () {
        b.forEach(function (t) { delete pending[t]; });
        applyItems(scan().filter(function (it) { return cache[it.text]; }));
        save();
        return worker();
      });
    }
    var ws = []; for (var k = 0; k < Math.min(PARALLEL, batches.length); k++) ws.push(worker());
    Promise.all(ws).then(function () { busy = false; });
  }
  function schedule() { clearTimeout(timer); timer = setTimeout(run, 250); }
  function observe() {
    if (!observer) observer = new MutationObserver(schedule);
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  }
  function init() {
    lang = getLang();
    if (lang === 'uz') return;
    cacheKey = 'ff-ui-' + lang + '-v4';
    try { cache = JSON.parse(localStorage.getItem(cacheKey) || '{}') || {}; } catch (e) { cache = {}; }
    Object.keys(cache).forEach(function (k) { done[cache[k]] = 1; });
    run(); observe();
  }
  window.FFUniversalTranslate = run;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
