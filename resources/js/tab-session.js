/**
 * Tab User Isolation
 *
 * Allows different browser tabs to be logged in as different users
 * simultaneously (e.g. teacher in Tab 1, student in Tab 2) without
 * touching the session cookie or breaking CSRF.
 *
 * How it works:
 * 1. Each tab has a unique tabId in sessionStorage (clears when tab closes).
 * 2. After login/navigation to an authed page, fetch /tab/token (POST) and
 *    store the encrypted user token in localStorage under tab_user_<tabId>.
 * 3. Every Livewire/AJAX request sends X-Tab-User header with the token.
 * 4. TabSessionIsolation middleware decrypts it and calls Auth::onceUsingId()
 *    so the request runs as the correct user for this tab.
 * 5. CSRF is untouched — Livewire reads it from meta[name=csrf-token] normally.
 */

const TAB_KEY = 'sentar_tab_id';
const TOKEN_PREFIX = 'tab_user_';
const HEADER = 'X-Tab-User';
const TOKEN_URL = '/tab/token';

// ── Stable per-tab ID ────────────────────────────────────────────────────────

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

// ── Token helpers ─────────────────────────────────────────────────────────────

function saveToken(t) {
    try { localStorage.setItem(storageKey(), t); } catch (_) { }
}
function loadToken() {
    try { return localStorage.getItem(storageKey()) || null; } catch (_) { return null; }
}
function clearToken() {
    try { localStorage.removeItem(storageKey()); } catch (_) { }
}

// ── Fetch fresh token from server ────────────────────────────────────────────

async function fetchToken() {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrf) return null;

        // Use the unpatched fetch to avoid infinite recursion
        const res = await _fetch(TOKEN_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
        });
        if (!res.ok) return null;                  // 401 = not authed, 403 = pending
        const json = await res.json();
        return json?.token || null;
    } catch (_) {
        return null;
    }
}

// ── Initialise ────────────────────────────────────────────────────────────────

async function init() {
    if (loadToken()) return;                       // already have one for this tab

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) return;

    // Detect authenticated pages by data attribute (set in app.blade.php) or path
    const isAuthed = !!document.querySelector('[data-tab-authed]')
        || /^\/(teacher|student|parent|admin|dashboard|profile|account)/.test(location.pathname);
    if (!isAuthed) return;

    const token = await fetchToken();
    if (token) saveToken(token);
}

// ── Patch fetch — inject X-Tab-User on every request ─────────────────────────

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

// ── Patch XHR (fallback for older libs) ──────────────────────────────────────

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

// ── Livewire navigate hooks ───────────────────────────────────────────────────

// After each navigate: try to get a token if we're now on an authed page
document.addEventListener('livewire:navigated', async () => {
    if (!loadToken()) {
        const token = await fetchToken();
        if (token) saveToken(token);
    }
});

// Before navigating away from an authed page: clear token if heading to /login
document.addEventListener('livewire:navigating', () => {
    if (location.pathname === '/login' || location.pathname === '/register') {
        clearToken();
    }
});

// ── Clear token when the standard logout form fires ──────────────────────────

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form?.action?.includes('logout')) {
        clearToken();
    }
});

// ── Bootstrap ─────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', init);

// Debug helpers
window.__tabId = tabId;
window.__tabToken = { load: loadToken, save: saveToken, clear: clearToken, fetch: fetchToken };
