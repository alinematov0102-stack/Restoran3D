// Internal language switcher — Google Translate is intentionally not used.
(function () {
  window.setLanguage = function (lang) {
    if (!['uz','ru','en'].includes(lang)) lang = 'uz';
    document.cookie = 'ff_lang=' + lang + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax';
    localStorage.setItem('ff-lang', lang);
    const url = new URL(window.location.href);
    url.searchParams.delete('lang');
    window.location.href = url.toString();
  };
  document.addEventListener('DOMContentLoaded', function () {
    const current = document.cookie.match(/(?:^|; )ff_lang=([^;]+)/)?.[1] || 'uz';
    document.documentElement.lang = current;
    document.querySelectorAll('#langSwitch button').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.lang === current);
    });
  });
})();
