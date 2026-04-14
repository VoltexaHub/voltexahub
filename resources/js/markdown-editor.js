import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

const init = () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const authed = document.querySelector('meta[name="vx-user"]')?.getAttribute('content') === '1';

    document.querySelectorAll('textarea[data-markdown]').forEach((el) => {
        if (el.dataset.mdeReady) return;
        el.dataset.mdeReady = '1';

        const editor = new EasyMDE({
            element: el,
            spellChecker: false,
            status: ['lines', 'words'],
            autosave: { enabled: false },
            toolbar: [
                'bold', 'italic', 'strikethrough', '|',
                'heading', 'quote', 'code',
                'unordered-list', 'ordered-list', '|',
                'link',
                ...(authed ? ['upload-image'] : ['image']),
                '|', 'preview', 'guide',
            ],
            minHeight: '180px',
            uploadImage: authed,
            imageAccept: 'image/png, image/jpeg, image/gif, image/webp',
            imageMaxSize: 5 * 1024 * 1024,
            imageUploadFunction: async function (file, onSuccess, onError) {
                try {
                    const body = new FormData();
                    body.append('image', file);
                    body.append('_token', csrf);
                    const res = await fetch('/uploads/image', {
                        method: 'POST',
                        body,
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (!res.ok) {
                        const j = await res.json().catch(() => ({}));
                        throw new Error(j.message || `Upload failed (${res.status})`);
                    }
                    const json = await res.json();
                    onSuccess(json.data.filePath);
                } catch (err) {
                    onError(err.message || 'Upload failed');
                }
            },
        });
        el.__mde = editor;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
