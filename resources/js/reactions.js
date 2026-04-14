/**
 * Progressive reaction toggling: intercepts form submits on .vx-react-form,
 * posts JSON, replaces the .vx-reactions bar with the server's summary.
 *
 * Falls back to full page reload if fetch fails or the server responds non-OK.
 */

const ALLOWED = ['👍', '❤️', '😂', '🎉', '🤔', '👀'];

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function render(summary, postId) {
    const used = summary.map((r) => r.emoji);
    const available = ALLOWED.filter((e) => !used.includes(e));

    const pills = summary
        .map(
            (r) => `
            <form method="POST" action="/posts/${postId}/reactions" class="vx-react-form">
                <input type="hidden" name="_token" value="${csrfToken()}" />
                <input type="hidden" name="emoji" value="${r.emoji}" />
                <button type="submit"
                        class="vx-react-pill inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-mono tabular-nums border transition-colors ${
                            r.mine
                                ? 'border-[color:var(--accent)] bg-[color:var(--accent-weak)] text-[color:var(--accent)]'
                                : 'vx-hairline vx-muted hover:border-[color:var(--accent)] hover:text-[color:var(--accent)]'
                        }"
                        data-emoji="${r.emoji}">
                    <span class="text-[0.95rem] leading-none">${r.emoji}</span>
                    <span class="vx-react-count">${r.count}</span>
                </button>
            </form>`,
        )
        .join('');

    const picker =
        available.length > 0
            ? `<details class="relative">
                  <summary class="list-none cursor-pointer inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs border vx-hairline vx-subtle hover:border-[color:var(--accent)] hover:text-[color:var(--accent)] transition-colors">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
                      React
                  </summary>
                  <div class="absolute z-10 mt-2 vx-card p-1.5 flex gap-1 shadow-sm">
                      ${available
                          .map(
                              (e) => `
                              <form method="POST" action="/posts/${postId}/reactions" class="vx-react-form">
                                  <input type="hidden" name="_token" value="${csrfToken()}" />
                                  <input type="hidden" name="emoji" value="${e}" />
                                  <button type="submit" class="vx-react-add w-8 h-8 rounded-full hover:bg-[color:var(--surface-mute)] text-lg leading-none transition-colors" data-emoji="${e}">${e}</button>
                              </form>`,
                          )
                          .join('')}
                  </div>
              </details>`
            : '';

    return pills + picker;
}

async function handleSubmit(form, e) {
    const bar = form.closest('.vx-reactions');
    if (!bar) return;
    const postId = bar.dataset.postId;
    const emoji = form.querySelector('input[name="emoji"]')?.value;
    if (!postId || !emoji) return;

    e.preventDefault();

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ emoji }).toString(),
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('reaction failed');
        const data = await res.json();
        bar.innerHTML = render(data.summary, data.post_id);
    } catch (err) {
        form.submit();
    }
}

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form instanceof HTMLFormElement && form.classList.contains('vx-react-form')) {
        handleSubmit(form, e);
    }
});
