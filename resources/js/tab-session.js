/**
 * Tab User Isolation
 *
 * Allows different browser tabs to be logged in as different users
 * simultaneously (e.g. teacher in Tab 1, student in Tab 2) without
 * touching the session cookie or breaking CSRF.
 *
 * ── How it works ──
 *
 * 1. A unique `tabId` is stored in sessionStorage (cleared when tab closes).
 *
 * 2. After login, we fetch /tab/token and store the encrypted user token in
 *    localStorage under the key `tab_user_<tabId>`.
 *
 * 3. Every Livewire / AJAX request automatically gets the `X-Tab-User`
 *    header with this token, so Laravel knows which user owns this tab.
 *
 * 4. The PHP middleware decrypts the token and calls Auth::onceUsingId()
 *    to override the session user for that request only.
 *
 * 5. CSRF remains untouched — Livewire reads csrf-token from the meta tag
 *    as normal and the session cookie is shared across tabs (that's fine).
 */

const TAB_KEY = 'sentar_tab_id';
const TOKEN_PREFIX = 'tab_user_';
const HEADER = 'X-Tab-User';
const TOKEN_URL = '/tab/token';

// ── Tab ID: unique per tab, lives in sessionStorage ──────────────────────────

function getTabId() {
    let id = sessionStorage.getItem(TAB_KEY);
    if (!id) {
        id = typeof crypto.randomUUID === 'function'
            ? crypto.randomUUID().replace(/-/g, '').slice(0, 16)
            : Array.from(crypto.getRandomValues(new Uint8Array(8)))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        sessionStorage.setItem(TAB_KEY, id);
    }
    return id;
}

export const tabId = getTabId();

const storageKey = () => TOKEN_PREFIX + tabId;

// ── Token storage ─────────────────────────────────────────────────────────────

function saveToken(token) {
    try { localStorage.setItem(storageKey(), token); } catch (_) { }
}

function loadToken() {
    try { return localStorage.getItem(storageKey()) || null; } catch (_) { return null; }
}

function clearToken() {
    try { localStorage.removeItem(storageKey()); } catch (_) { }
}

// ── Fetch a fresh token from the server ──────────────────────────────────────

async function fetchToken() {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await _fetch(TOKEN_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
        });
        if (!res.ok) return null;
        const { token } = await res.json();
        return token || null;
    } catch (_) {
        return null;
    }
}

// ── Initialise: fetch token if user appears to be logged in ──────────────────

async function init() {
    // If we already have a stored token for this tab, keep it
    if (loadToken()) return;

    // Heuristic: the layout only renders the sidebar when logged in
    // — if there's no csrf meta, the page hasn't loaded yet; wait.
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) return;

    // If the page has a dashboard-style structure, user is likely authed
    const isAuthed = !!document.querySelector('[data-tab-authed]')
        || location.pathname.startsWith('/teacher')
        || location.pathname.startsWith('/student')
        || location.pathname.startsWith('/parent')
        || location.pathname.startsWith('/admin')
        || location.pathname.startsWith('/dashboard')
        || location.pathname.startsWith('/profile');

    if (isAuthed) {
        const token = await fetchToken();
        if (token) saveToken(token);
    }
}

// ── Patch window.fetch to inject X-Tab-User on every request ─────────────────

const _fetch = window.fetch.bind(window);
window.fetch = async function (input, init = {}) {
    const token = loadToken();
    if (token) {
        const headers = new Headers(init.headers || {});
        headers.set(HEADER, token);
        return _fetch(input, { ...init, headers });
    }
    return _fetch(input, init);
};

// ── Patch XMLHttpRequest (fallback) ──────────────────────────────────────────

const _xhrOpen = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function (...args) {
    this.__tabPatch = true;
    return _xhrOpen.apply(this, args);
};
const _xhrSend = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.send = function (...args) {
    if (this.__tabPatch) {
        const token = loadToken();
        if (token) {
            try { this.setRequestHeader(HEADER, token); } catch (_) { }
        }
    }
    return _xhrSend.apply(this, args);
};

// ── Re-fetch token after Livewire navigate (new page may be authed) ──────────

document.addEventListener('livewire:navigated', async () => {
    // If navigated to an authed page and we have no token yet, get one
    if (!loadToken()) {
        const token = await fetchToken();
        if (token) saveToken(token);
    }
});

// ── Clear token on logout ─────────────────────────────────────────────────────
// Livewire dispatches a 'redirect' after logout; check for login page

document.addEventListener('livewire:navigating', () => {
    // will be handled by navigated if needed
});

// ── Expose for debugging ──────────────────────────────────────────────────────

window.__tabId = tabId;
window.__tabToken = { save: saveToken, load: loadToken, clear: clearToken, fetch: fetchToken };

// Bootstrap
document.addEventListener('DOMContentLoaded', init);
document.addEventListener('livewire:navigated', init);
