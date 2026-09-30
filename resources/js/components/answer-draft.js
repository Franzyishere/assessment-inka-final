export function createAnswerDraft(root, input) {
    const key = root.dataset.draftKey;
    const form = root.closest('form');
    if (!key || !form) return { restore: value => value, changed() {} };
    const status = document.createElement('p'); status.className = 'px-3 py-2 text-xs text-gray-600'; status.setAttribute('role', 'status');
    root.append(status);
    let timer, running = null, manual = false, resuming = false, lastSaved = input.value;
    const backup = html => {
        try { sessionStorage.setItem(key, JSON.stringify({ html, at: Date.now() })); }
        catch { status.textContent = 'Cadangan browser tidak tersedia. Tunggu konfirmasi draft tersimpan sebelum refresh.'; }
    };
    const clear = () => { try { sessionStorage.removeItem(key); } catch {} };
    const save = () => {
        clearTimeout(timer);
        if (manual || running || input.value === lastSaved) return running || Promise.resolve();
        const html = input.value;
        const body = new FormData(form);
        body.set('response', html); body.set('draft_only', '1'); body.delete('submit_after_save');
        status.textContent = 'Menyimpan draft…';
        running = fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body })
            .then(async response => {
                const result = await response.json();
                if (!response.ok || !result.draft_saved) throw new Error('not saved');
                lastSaved = html;
                if (input.value === html) clear();
                else backup(input.value);
                status.textContent = 'Draft tersimpan otomatis.';
            }).catch(() => {
                backup(input.value);
                status.textContent = 'Draft belum tersimpan di server. Periksa koneksi dan simpan kembali sebelum keluar.';
            }).finally(() => {
                running = null;
                if (!manual && input.value !== lastSaved) timer = setTimeout(save, 5000);
            });
        return running;
    };
    form.addEventListener('submit', event => {
        if (resuming) { resuming = false; manual = false; return; }
        clearTimeout(timer); backup(input.value);
        if (running) {
            event.preventDefault(); event.stopImmediatePropagation(); manual = true;
            const submitter = event.submitter;
            running.finally(() => { resuming = true; form.requestSubmit(submitter); });
        }
    }, true);
    form.addEventListener('answer-saved', () => { lastSaved = input.value; clear(); status.textContent = 'Jawaban tersimpan.'; });
    window.addEventListener('online', save);
    return {
        restore(value) {
            try {
                const draft = JSON.parse(sessionStorage.getItem(key) || 'null');
                if (draft && typeof draft.html === 'string' && Date.now() - draft.at < 86400000) {
                    status.textContent = 'Draft dipulihkan dari tab ini.'; return draft.html;
                }
            } catch {}
            return value;
        },
        changed(html) {
            backup(html); clearTimeout(timer);
            if (html !== lastSaved) { status.textContent = 'Perubahan belum tersimpan…'; timer = setTimeout(save, 800); }
        },
    };
}
