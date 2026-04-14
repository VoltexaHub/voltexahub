/**
 * Lightweight @mention autocomplete for EasyMDE (CodeMirror 5).
 *
 * Hooks every textarea with data-markdown once its EasyMDE instance has attached
 * (markdown-editor.js stores it at textarea.__mde). When the user types '@'
 * followed by handle characters, fetch /mentions/search and show a floating
 * picker anchored at the caret. Arrow keys navigate, Enter/Tab insert.
 */

const HANDLE_PATTERN = /@([A-Za-z0-9][A-Za-z0-9._\-]{0,31})$/;

let picker = null;
let results = [];
let activeIndex = 0;
let activeCM = null;
let activeTriggerPos = null; // { line, ch } — position of the '@' character
let debounceTimer = null;

function ensurePicker() {
    if (picker) return picker;
    picker = document.createElement('div');
    picker.className = 'vx-mention-picker';
    picker.style.cssText = `
        position: absolute;
        z-index: 1000;
        min-width: 220px;
        max-width: 320px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        padding: 0.25rem;
        display: none;
        font-family: 'Inter Tight', system-ui, sans-serif;
        font-size: 0.875rem;
    `;
    document.body.appendChild(picker);
    return picker;
}

function hidePicker() {
    if (picker) picker.style.display = 'none';
    results = [];
    activeIndex = 0;
    activeCM = null;
    activeTriggerPos = null;
}

function renderPicker() {
    const p = ensurePicker();
    if (results.length === 0) {
        hidePicker();
        return;
    }
    p.innerHTML = results.map((r, i) => `
        <div class="vx-mention-item" data-index="${i}" style="
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.375rem;
            cursor: pointer;
            ${i === activeIndex ? 'background: var(--accent-weak);' : ''}
        ">
            <img src="${r.avatar_url}" alt="" style="width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;" />
            <div style="min-width: 0; flex: 1;">
                <div style="color: var(--accent); font-weight: 500; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">@${r.handle}</div>
                <div style="color: var(--text-muted); font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(r.name)}</div>
            </div>
        </div>
    `).join('');

    p.querySelectorAll('.vx-mention-item').forEach((el) => {
        el.addEventListener('mouseenter', () => {
            activeIndex = parseInt(el.dataset.index, 10);
            renderPicker();
        });
        el.addEventListener('mousedown', (e) => {
            e.preventDefault();
            activeIndex = parseInt(el.dataset.index, 10);
            insertMention();
        });
    });

    p.style.display = 'block';
}

function positionPicker(cm) {
    const cursor = cm.getCursor();
    const coords = cm.cursorCoords(cursor, 'page');
    const p = ensurePicker();
    p.style.left = coords.left + 'px';
    p.style.top = (coords.bottom + 4) + 'px';
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

async function fetchResults(query) {
    try {
        const res = await fetch(`/mentions/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) return [];
        const data = await res.json();
        return data.results || [];
    } catch {
        return [];
    }
}

function insertMention() {
    if (!activeCM || !results[activeIndex]) return;
    const handle = results[activeIndex].handle;
    const cursor = activeCM.getCursor();
    // Replace from triggerPos (the '@') through the current cursor with '@handle '
    activeCM.replaceRange('@' + handle + ' ', activeTriggerPos, cursor);
    hidePicker();
}

function onCursorActivity(cm) {
    const cursor = cm.getCursor();
    const line = cm.getLine(cursor.line).slice(0, cursor.ch);
    const m = line.match(HANDLE_PATTERN);
    if (!m) {
        hidePicker();
        return;
    }
    const query = m[1];
    activeCM = cm;
    activeTriggerPos = { line: cursor.line, ch: cursor.ch - query.length - 1 };

    if (query.length === 0) {
        hidePicker();
        return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        results = await fetchResults(query);
        activeIndex = 0;
        positionPicker(cm);
        renderPicker();
    }, 120);
}

function onKeyDown(cm, e) {
    if (!picker || picker.style.display === 'none' || results.length === 0) return;
    if (e.key === 'ArrowDown') {
        activeIndex = (activeIndex + 1) % results.length;
        renderPicker();
        e.preventDefault();
    } else if (e.key === 'ArrowUp') {
        activeIndex = (activeIndex - 1 + results.length) % results.length;
        renderPicker();
        e.preventDefault();
    } else if (e.key === 'Enter' || e.key === 'Tab') {
        insertMention();
        e.preventDefault();
    } else if (e.key === 'Escape') {
        hidePicker();
        e.preventDefault();
    }
}

function attach(cm) {
    cm.on('cursorActivity', () => onCursorActivity(cm));
    cm.on('keydown', (cm, e) => onKeyDown(cm, e));
    cm.on('blur', () => setTimeout(hidePicker, 150));
}

function bindAll() {
    document.querySelectorAll('textarea[data-markdown]').forEach((el) => {
        if (el.__mentionsBound) return;
        if (!el.__mde) return;
        el.__mentionsBound = true;
        attach(el.__mde.codemirror);
    });
}

// Poll briefly — EasyMDE may initialize after our script runs.
let ticks = 0;
const poll = setInterval(() => {
    bindAll();
    if (++ticks > 40) clearInterval(poll);  // ~4s
}, 100);

document.addEventListener('click', (e) => {
    if (picker && !picker.contains(e.target)) hidePicker();
});
