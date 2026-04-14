import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

const init = () => {
    document.querySelectorAll('textarea[data-markdown]').forEach((el) => {
        if (el.dataset.mdeReady) return;
        el.dataset.mdeReady = '1';
        const editor = new EasyMDE({
            element: el,
            spellChecker: false,
            status: ['lines', 'words'],
            autosave: { enabled: false },
            toolbar: ['bold', 'italic', 'strikethrough', '|', 'heading', 'quote', 'code', 'unordered-list', 'ordered-list', '|', 'link', 'image', '|', 'preview', 'guide'],
            minHeight: '180px',
        });
        // Expose the instance so other scripts (quote, etc.) can interact with it.
        el.__mde = editor;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
