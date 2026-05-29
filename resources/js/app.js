import './bootstrap';

// ── Dark Mode ──────────────────────────────────────────────────────────────
// Expose globally so onclick handlers in Blade work
window.toggleTheme = function () {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    if (isDark) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};
