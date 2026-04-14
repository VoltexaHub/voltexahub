/**
 * Quote-reply: clicking a Quote button inserts a markdown blockquote of the
 * source post into the thread reply textarea (or its EasyMDE instance).
 */

function buildQuote(author, body) {
    const lines = String(body).split(/\r?\n/).map((l) => '> ' + l).join('\n');
    const who = author ? `**${author}** wrote:` : '_Previously:_';
    return `> ${who}\n>\n${lines}\n\n`;
}

function targetTextarea() {
    return document.querySelector('form[action*="/posts"] textarea[name="body"]')
        || document.querySelector('textarea[name="body"]');
}

function insertQuote(text) {
    const ta = targetTextarea();
    if (!ta) return false;

    if (ta.__mde) {
        const mde = ta.__mde;
        const cm = mde.codemirror;
        const current = mde.value();
        const newValue = current ? current.replace(/\s*$/, '\n\n') + text : text;
        mde.value(newValue);
        cm.focus();
        cm.setCursor(cm.lineCount(), 0);
    } else {
        ta.value = (ta.value ? ta.value.replace(/\s*$/, '\n\n') : '') + text;
        ta.focus();
        ta.selectionStart = ta.selectionEnd = ta.value.length;
    }
    return true;
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-quote]');
    if (!btn) return;
    e.preventDefault();

    const author = btn.dataset.quoteAuthor || '';
    const body = btn.dataset.quoteBody || '';
    if (!body) return;

    if (insertQuote(buildQuote(author, body))) {
        const anchor = document.querySelector('[data-reply-form]') || targetTextarea();
        if (anchor && 'scrollIntoView' in anchor) {
            anchor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
