# Code Paste Plugin

Enhances code blocks in forum posts with syntax highlighting (highlight.js), a copy-to-clipboard button, language label, and line numbers. Works with both Markdown fenced code blocks (` ``` `) and BBCode `[code]` tags.

## Backend

The backend plugin is minimal — code blocks are already rendered as `<pre><code>` HTML by CommonMark and s9e. This plugin:

- Registers itself in the plugin system (`plugin.json` + `CodePastePlugin.php`)
- Exposes `GET /api/plugins/code-paste/config` so the frontend can check if the plugin is enabled

### Install

Via the admin panel **Plugins** page, or manually:

```sql
INSERT INTO installed_plugins (slug, name, version, author, description, enabled, installed_at)
VALUES ('code-paste', 'Code Paste', '1.0.0', 'VoltexaHub',
        'Enhance code blocks with syntax highlighting, copy button, language labels, and line numbers.',
        1, NOW());
```

## Frontend Integration

### 1. Install highlight.js

```bash
cd /path/to/voltexaforum
npm install highlight.js
```

### 2. Create the composable

Create `src/composables/useCodePaste.js`:

```javascript
import { nextTick, watch } from 'vue'
import hljs from 'highlight.js/lib/core'

// Import only the languages you need (keeps bundle small).
// Add more as needed from highlight.js/lib/languages/*
import javascript from 'highlight.js/lib/languages/javascript'
import typescript from 'highlight.js/lib/languages/typescript'
import python from 'highlight.js/lib/languages/python'
import php from 'highlight.js/lib/languages/php'
import css from 'highlight.js/lib/languages/css'
import html from 'highlight.js/lib/languages/xml'
import json from 'highlight.js/lib/languages/json'
import bash from 'highlight.js/lib/languages/bash'
import sql from 'highlight.js/lib/languages/sql'
import java from 'highlight.js/lib/languages/java'
import csharp from 'highlight.js/lib/languages/csharp'
import cpp from 'highlight.js/lib/languages/cpp'
import go from 'highlight.js/lib/languages/go'
import rust from 'highlight.js/lib/languages/rust'
import ruby from 'highlight.js/lib/languages/ruby'
import yaml from 'highlight.js/lib/languages/yaml'
import markdown from 'highlight.js/lib/languages/markdown'
import lua from 'highlight.js/lib/languages/lua'

hljs.registerLanguage('javascript', javascript)
hljs.registerLanguage('js', javascript)
hljs.registerLanguage('typescript', typescript)
hljs.registerLanguage('ts', typescript)
hljs.registerLanguage('python', python)
hljs.registerLanguage('py', python)
hljs.registerLanguage('php', php)
hljs.registerLanguage('css', css)
hljs.registerLanguage('html', html)
hljs.registerLanguage('xml', html)
hljs.registerLanguage('json', json)
hljs.registerLanguage('bash', bash)
hljs.registerLanguage('sh', bash)
hljs.registerLanguage('shell', bash)
hljs.registerLanguage('sql', sql)
hljs.registerLanguage('java', java)
hljs.registerLanguage('csharp', csharp)
hljs.registerLanguage('cs', csharp)
hljs.registerLanguage('cpp', cpp)
hljs.registerLanguage('c', cpp)
hljs.registerLanguage('go', go)
hljs.registerLanguage('rust', rust)
hljs.registerLanguage('rs', rust)
hljs.registerLanguage('ruby', ruby)
hljs.registerLanguage('rb', ruby)
hljs.registerLanguage('yaml', yaml)
hljs.registerLanguage('yml', yaml)
hljs.registerLanguage('markdown', markdown)
hljs.registerLanguage('md', markdown)
hljs.registerLanguage('lua', lua)

/**
 * Enhance all <pre><code> blocks inside a container element.
 * Call this from onMounted / watch in MarkdownRenderer.vue.
 */
export function enhanceCodeBlocks(containerEl) {
  if (!containerEl) return

  containerEl.querySelectorAll('pre').forEach((pre) => {
    // Skip already-enhanced blocks
    if (pre.dataset.codePaste) return
    pre.dataset.codePaste = '1'

    const code = pre.querySelector('code')
    if (!code) return

    // Remove s9e's injected hljs script tag (we use our own)
    pre.querySelectorAll('script[data-hljs-style]').forEach((s) => s.remove())

    // Detect language from class="language-xxx" (CommonMark)
    const langMatch = code.className.match(/language-(\w+)/)
    const lang = langMatch ? langMatch[1] : null
    const displayLang = lang ? lang.toUpperCase() : 'CODE'

    // Syntax highlight
    if (lang && hljs.getLanguage(lang)) {
      code.innerHTML = hljs.highlight(code.textContent, { language: lang }).value
    } else {
      // Auto-detect
      const result = hljs.highlightAuto(code.textContent)
      code.innerHTML = result.value
    }

    // ── Build enhanced wrapper ──────────────────────────────────────
    pre.classList.add('code-paste-block')

    // Header bar (language label + copy button)
    const header = document.createElement('div')
    header.className = 'code-paste-header'

    const langLabel = document.createElement('span')
    langLabel.className = 'code-paste-lang'
    langLabel.textContent = displayLang

    const copyBtn = document.createElement('button')
    copyBtn.className = 'code-paste-copy'
    copyBtn.type = 'button'
    copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy'
    copyBtn.addEventListener('click', () => {
      navigator.clipboard.writeText(code.textContent).then(() => {
        copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!'
        copyBtn.classList.add('copied')
        setTimeout(() => {
          copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy'
          copyBtn.classList.remove('copied')
        }, 2000)
      })
    })

    header.appendChild(langLabel)
    header.appendChild(copyBtn)
    pre.insertBefore(header, pre.firstChild)

    // Line numbers
    const lines = code.innerHTML.split('\n')
    // Remove trailing empty line (common in fenced blocks)
    if (lines.length > 1 && lines[lines.length - 1].trim() === '') lines.pop()

    const lineNumbersDiv = document.createElement('div')
    lineNumbersDiv.className = 'code-paste-line-numbers'
    lineNumbersDiv.innerHTML = lines.map((_, i) => `<span>${i + 1}</span>`).join('\n')

    // Wrap code in a scrollable body
    const body = document.createElement('div')
    body.className = 'code-paste-body'
    body.appendChild(lineNumbersDiv)

    const codeWrap = document.createElement('div')
    codeWrap.className = 'code-paste-code'
    codeWrap.appendChild(code)

    body.appendChild(codeWrap)
    pre.appendChild(body)
  })
}

/**
 * Vue composable: auto-enhance code blocks when content changes.
 * Usage in MarkdownRenderer.vue:
 *
 *   import { useCodePaste } from '../composables/useCodePaste'
 *   useCodePaste(container, rendered)
 */
export function useCodePaste(containerRef, renderedRef) {
  const run = () => nextTick(() => enhanceCodeBlocks(containerRef.value))

  watch(renderedRef, run)
  // Also run on mount (handled by the component calling run() in onMounted)
  return { enhanceCodeBlocks: run }
}
```

### 3. Add CSS

Create `src/assets/code-paste.css` (or add to your global styles):

```css
/* ── Code Paste Plugin ─────────────────────────────────────────── */
.code-paste-block {
  position: relative;
  background: #0d1117 !important;
  border: 1px solid #1e293b;
  border-radius: 0.75rem;
  overflow: hidden;
  margin-bottom: 0.75rem;
  padding: 0 !important;
  font-size: 0.8rem;
  line-height: 1.6;
}

.code-paste-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.4rem 0.75rem;
  background: #161b22;
  border-bottom: 1px solid #1e293b;
  font-family: ui-sans-serif, system-ui, sans-serif;
}

.code-paste-lang {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #7c3aed;
  text-transform: uppercase;
}

.code-paste-copy {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.65rem;
  font-weight: 600;
  color: #8b949e;
  background: transparent;
  border: 1px solid #30363d;
  border-radius: 0.375rem;
  padding: 0.2rem 0.5rem;
  cursor: pointer;
  transition: all 0.15s;
  font-family: ui-sans-serif, system-ui, sans-serif;
}

.code-paste-copy:hover {
  color: #c9d1d9;
  border-color: #484f58;
  background: #21262d;
}

.code-paste-copy.copied {
  color: #3fb950;
  border-color: #238636;
}

.code-paste-body {
  display: flex;
  overflow-x: auto;
}

.code-paste-line-numbers {
  display: flex;
  flex-direction: column;
  padding: 0.75rem 0;
  text-align: right;
  user-select: none;
  border-right: 1px solid #1e293b;
  min-width: 2.5rem;
  flex-shrink: 0;
}

.code-paste-line-numbers span {
  padding: 0 0.65rem;
  color: #3b4252;
  font-size: 0.75rem;
  font-family: ui-monospace, monospace;
  line-height: 1.6;
}

.code-paste-code {
  flex: 1;
  overflow-x: auto;
  min-width: 0;
}

.code-paste-code code {
  display: block;
  padding: 0.75rem 1rem !important;
  background: none !important;
  color: #c9d1d9;
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 0.8rem;
  line-height: 1.6;
  white-space: pre;
}

/* ── highlight.js token colors (GitHub Dark theme) ──────────────── */
.code-paste-block .hljs-keyword,
.code-paste-block .hljs-selector-tag { color: #ff7b72; }
.code-paste-block .hljs-string,
.code-paste-block .hljs-addition { color: #a5d6ff; }
.code-paste-block .hljs-comment,
.code-paste-block .hljs-quote { color: #8b949e; font-style: italic; }
.code-paste-block .hljs-number,
.code-paste-block .hljs-literal { color: #79c0ff; }
.code-paste-block .hljs-built_in,
.code-paste-block .hljs-type { color: #ffa657; }
.code-paste-block .hljs-function,
.code-paste-block .hljs-title { color: #d2a8ff; }
.code-paste-block .hljs-variable,
.code-paste-block .hljs-template-variable { color: #ffa657; }
.code-paste-block .hljs-attr,
.code-paste-block .hljs-attribute { color: #79c0ff; }
.code-paste-block .hljs-symbol,
.code-paste-block .hljs-bullet { color: #ffa657; }
.code-paste-block .hljs-meta { color: #8b949e; }
.code-paste-block .hljs-deletion { color: #ffa198; background: rgba(248,81,73,0.1); }
.code-paste-block .hljs-addition { background: rgba(63,185,80,0.1); }
.code-paste-block .hljs-params { color: #c9d1d9; }
.code-paste-block .hljs-property { color: #79c0ff; }
.code-paste-block .hljs-selector-class { color: #7ee787; }
.code-paste-block .hljs-tag { color: #7ee787; }
.code-paste-block .hljs-name { color: #7ee787; }
.code-paste-block .hljs-regexp { color: #a5d6ff; }
```

### 4. Integrate into MarkdownRenderer.vue

In `src/components/MarkdownRenderer.vue`, add the composable:

```diff
 <script setup>
-import { computed, inject, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
+import { computed, inject, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
+import { useCodePaste } from '../composables/useCodePaste'

 // ... existing code ...

 const container = ref(null)
+const { enhanceCodeBlocks } = useCodePaste(container, rendered)

 // ... existing onMounted ...
-onMounted(() => nextTick(() => { bindSpoilers(); bindLockedContent() }))
+onMounted(() => nextTick(() => { bindSpoilers(); bindLockedContent(); enhanceCodeBlocks() }))
```

Also import the CSS in `src/main.js` (or wherever global styles are loaded):

```javascript
import './assets/code-paste.css'
```

### 5. How it works

The backend renders post content through CommonMark + s9e, producing:

- **Markdown fenced blocks**: `<pre><code class="language-javascript">...</code></pre>`
- **BBCode `[code]`**: `<pre><code>...</code></pre>` (+ an s9e script tag that gets removed)

The frontend composable runs after the HTML is injected via `v-html`. It:

1. Finds all `<pre>` elements not yet enhanced
2. Extracts the language from `class="language-xxx"` (or auto-detects)
3. Applies highlight.js syntax highlighting
4. Injects a header with language label + copy button
5. Adds line numbers
6. Wraps everything in the dark-themed `.code-paste-block` container

No backend changes to content processing are needed.
