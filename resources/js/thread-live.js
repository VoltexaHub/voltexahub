import './echo';

const container = document.querySelector('[data-thread-posts]');
if (container) {
    const threadId = container.dataset.threadId;

    window.Echo.channel(`threads.${threadId}`)
        .listen('.post.created', (payload) => {
            if (document.getElementById(`post-${payload.id}`)) return;

            const article = document.createElement('article');
            article.className = 'vx-card overflow-hidden ring-indigo-300/60 dark:ring-indigo-700/60';
            article.id = `post-${payload.id}`;

            const authorHtml = payload.author
                ? `<a href="${payload.author.profile_url}" class="flex items-center gap-2.5 hover:opacity-90">
                       <img src="${payload.author.avatar_url}" alt="" class="w-8 h-8 rounded-full ring-1 ring-slate-200 dark:ring-slate-700" />
                       <span class="font-medium vx-heading hover:text-indigo-500">${escapeHtml(payload.author.name)}</span>
                   </a>`
                : '<span class="vx-muted">[deleted]</span>';

            article.innerHTML = `
                <header class="vx-card-header flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2.5">${authorHtml}</div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">New</span>
                        <span class="vx-subtle tabular-nums">${escapeHtml(payload.created_at_formatted)}</span>
                    </div>
                </header>
                <div class="px-5 py-5 prose prose-sm max-w-none prose-indigo dark:prose-invert">${payload.body_html}</div>
            `;

            container.appendChild(article);
        });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
