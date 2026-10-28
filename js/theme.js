// theme.js - Light/Dark rejimni almashtirish va saqlash
(function () {
  const saved = localStorage.getItem("ff-theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);

  window.toggleTheme = function () {
    const current = document.documentElement.getAttribute("data-theme");
    const next = current === "light" ? "dark" : "light";
    document.documentElement.setAttribute("data-theme", next);
    localStorage.setItem("ff-theme", next);
    document.querySelectorAll(".theme-toggle-icon").forEach((el) => {
      el.textContent = next === "light" ? "🌙" : "☀️";
    });
  };

  document.addEventListener("DOMContentLoaded", function () {
    const theme = document.documentElement.getAttribute("data-theme");
    document.querySelectorAll(".theme-toggle-icon").forEach((el) => {
      el.textContent = theme === "light" ? "🌙" : "☀️";
    });
  });
})();
