import './echo';

const container = document.querySelector('[data-thread-posts]');
if (container) {
    const threadId = container.dataset.threadId;

    window.Echo.channel(`threads.${threadId}`)
        .listen('.post.created', (payload) => {
            if (document.getElementById(`post-${payload.id}`)) return;

            const article = document.createElement('article');
            article.className = 'border-b vx-hairline py-7';
            article.id = `post-${payload.id}`;
            article.style.borderLeft = '2px solid var(--accent)';
            article.style.paddingLeft = '1rem';
            article.style.marginLeft = '-1rem';

            const authorHtml = payload.author
                ? `<a href="${payload.author.profile_url}" class="shrink-0">
                       <img src="${payload.author.avatar_url}" alt="" class="w-10 h-10 rounded-full border vx-hairline" />
                   </a>
                   <div>
                       <a href="${payload.author.profile_url}" class="vx-display text-[0.95rem] font-medium vx-heading hover:text-[color:var(--accent)]">${escapeHtml(payload.author.name)}</a>
                       <p class="vx-meta normal-case tracking-normal text-[0.7rem] mt-0.5">
                           <span style="color:var(--accent);">new</span>
                           <span class="opacity-60 mx-1">·</span>
                           <span>${escapeHtml(payload.created_at_formatted)}</span>
                       </p>
                   </div>`
                : '<p class="vx-muted italic">[deleted]</p>';

            article.innerHTML = `
                <header class="flex items-center gap-3 mb-4">${authorHtml}</header>
                <div class="pl-[3.25rem] vx-prose">${payload.body_html}</div>
            `;

            container.appendChild(article);
        });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
