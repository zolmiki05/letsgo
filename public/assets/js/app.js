/**
 * app.js – Letsgo client-side scripts.
 *
 * Responsibilities:
 *  1. Theme toggle (dark / light) with localStorage persistence
 *  2. Slider value display updates
 *  3. Status option radio button highlight sync
 */

// ── Theme ──────────────────────────────────────────────────────────────────

const THEME_KEY = 'letsgo_theme';

/**
 * Apply a theme by setting data-theme on <html> and updating the toggle icon.
 * @param {'dark'|'light'} theme
 */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const icon = document.getElementById('themeIcon');
    if (icon) {
        // ◑ = half moon (switch to dark), ☀ = sun (switch to light)
        icon.textContent = theme === 'dark' ? '☀' : '◑';
    }
}

/** Toggle between dark and light, persisting the choice. */
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next    = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
}

/** Initialise the icon on page load (theme was already applied inline in <head>). */
function initThemeIcon() {
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    applyTheme(theme);
}

// ── Slider value display ───────────────────────────────────────────────────

/**
 * Update a slider's companion value display.
 * Called via the slider's oninput attribute in the view.
 *
 * @param {HTMLInputElement} slider  The range input element
 * @param {string}           valId  The ID of the <span> showing the value
 */
function updateVal(slider, valId) {
    const el = document.getElementById(valId);
    if (el) el.textContent = slider.value;
}

// ── Status option radio highlight ──────────────────────────────────────────

function initStatusOptions() {
    const options = document.querySelectorAll('.status-option');
    options.forEach(function (label) {
        const radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        if (radio.checked) label.classList.add('selected');
        radio.addEventListener('change', function () {
            options.forEach(function (l) { l.classList.remove('selected'); });
            if (radio.checked) label.classList.add('selected');
        });
    });
}

// ── Init ───────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initThemeIcon();
    initStatusOptions();
});
