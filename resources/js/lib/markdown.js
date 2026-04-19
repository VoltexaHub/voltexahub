import { marked } from 'marked'
import DOMPurify from 'dompurify'
import hljs from 'highlight.js'

marked.use({
    renderer: {
        code(token) {
            const lang = token.lang || 'plaintext'
            const language = hljs.getLanguage(lang) ? lang : 'plaintext'
            const highlighted = hljs.highlight(token.text, { language }).value
            return `<pre><code class="hljs language-${language}">${highlighted}</code></pre>`
        }
    }
})

export function renderMarkdown(text) {
    return DOMPurify.sanitize(marked.parse(text || ''))
}
