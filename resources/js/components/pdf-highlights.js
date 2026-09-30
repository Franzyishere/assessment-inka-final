import { pdfCaretAt, selectPdfText } from './pdf-text-selection';

export function createPdfHighlights(root) {
    const endpoint = root.dataset.highlightsUrl;
    if (!endpoint) return null;
    let marks = [], ready = false, active = false, revision = 0, saved = 0, running = false, timer, permanentError = false;
    const storageKey = 'pdf-highlight-draft:' + (root.dataset.highlightsKey || endpoint);
    const bar = root.querySelector('.pdf-annotation-tools');
    bar.hidden = false;
    const toggle = bar.querySelector('button');
    const eraser = document.createElement('button');
    eraser.type = 'button'; eraser.textContent = 'Penghapus';
    eraser.className = toggle.className; eraser.setAttribute('aria-pressed', 'false');
    eraser.title = 'Drag untuk menghapus tanda yang tersentuh';
    toggle.after(eraser);
    let erasing = false, erasePointer = null, lastErase = null, selectionStarted = false;
    const status = bar.querySelector('[role="status"]');
    const paint = () => {
        root.dataset.highlightActive = String(active);
        root.dataset.eraseActive = String(erasing);
        root.querySelectorAll('.pdf-page').forEach(page => {
            const layer = page.querySelector('.pdf-highlights');
            layer.replaceChildren();
            marks.forEach((mark, index) => {
                if (mark.page !== Number(page.dataset.page)) return;
                const rect = document.createElement('button');
                rect.type = 'button'; rect.className = 'pdf-highlight';
                rect.title = 'Hapus highlight'; rect.setAttribute('aria-label', 'Hapus highlight');
                Object.assign(rect.style, { left: mark.x * 100 + '%', top: mark.y * 100 + '%', width: mark.width * 100 + '%', height: mark.height * 100 + '%' });
                rect.tabIndex = -1;
                layer.append(rect);
            });
        });
    };
    const backup = () => { try { sessionStorage.setItem(storageKey, JSON.stringify(marks)); } catch {} };
    const save = async () => {
        if (running || saved === revision || !ready || permanentError) return;
        running = true; const sent = revision;
        status.textContent = 'Menyimpan highlight…';
        try {
            const response = await fetch(endpoint, { method: 'PUT', credentials: 'same-origin', headers: {
                'Content-Type': 'application/json', Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }, body: JSON.stringify({ highlights: marks }) });
            if (!response.ok) {
                permanentError = [401, 403, 404, 419, 422].includes(response.status);
                throw new Error('save');
            }
            saved = sent;
            if (revision === sent) { sessionStorage.removeItem(storageKey); status.textContent = 'Highlight tersimpan'; }
        } catch {
            status.textContent = permanentError ? 'Highlight belum tersimpan. Periksa sesi atau muat ulang halaman.' : 'Highlight belum tersimpan. Mencoba kembali…';
        } finally {
            running = false;
            if (!permanentError && saved !== revision && root.isConnected) { clearTimeout(timer); timer = setTimeout(save, 3000); }
        }
    };
    const changed = () => {
        revision++; backup(); paint(); status.textContent = 'Perubahan highlight belum tersimpan…'; clearTimeout(timer); timer = setTimeout(save, 500);
    };
    toggle.disabled = true;
    eraser.disabled = true;
    toggle.onclick = () => {
        active = !active; erasing = false;
        toggle.setAttribute('aria-pressed', String(active)); eraser.setAttribute('aria-pressed', 'false'); paint();
        window.getSelection()?.removeAllRanges();
    };
    eraser.onclick = () => {
        erasing = !erasing; active = false;
        toggle.setAttribute('aria-pressed', 'false'); eraser.setAttribute('aria-pressed', String(erasing)); paint();
        window.getSelection()?.removeAllRanges();
    };
    const eraseAt = event => {
        const from = lastErase || [event.clientX, event.clientY];
        const steps = Math.max(1, Math.ceil(Math.hypot(event.clientX - from[0], event.clientY - from[1]) / 4));
        const pages = [...root.querySelectorAll('.pdf-page')].map(page => ({ number: Number(page.dataset.page), bounds: page.getBoundingClientRect() }));
        const previous = marks.length;
        marks = marks.filter(mark => {
            const page = pages.find(item => item.number === mark.page);
            if (!page) return true;
            const b = page.bounds, left = b.left + mark.x * b.width, top = b.top + mark.y * b.height;
            for (let i = 0; i <= steps; i++) {
                const x = from[0] + (event.clientX - from[0]) * i / steps;
                const y = from[1] + (event.clientY - from[1]) * i / steps;
                if (x >= left - 6 && x <= left + mark.width * b.width + 6 && y >= top - 6 && y <= top + mark.height * b.height + 6) return false;
            }
            return true;
        });
        lastErase = [event.clientX, event.clientY];
        if (previous !== marks.length) changed();
    };
    // Scanned pages do not have selectable text: allow a rectangular highlighter.
    let area = null, textDrag = null;
    root.addEventListener('pointerdown', event => {
        const page = event.target.closest('.pdf-page');
        selectionStarted = false;
        if (!ready || event.button !== 0) return;
        if (erasing && event.target.closest('.pdf-pages-scroll-container')) {
            erasePointer = event.pointerId; lastErase = null;
            root.setPointerCapture(event.pointerId); event.preventDefault(); eraseAt(event); return;
        }
        if (!page) return;
        if (active) {
            window.getSelection()?.removeAllRanges();
            selectionStarted = !!event.target.closest('.textLayer');
            const layer = page.querySelector('.textLayer');
            if (layer?.querySelector('span')) {
                const anchor = pdfCaretAt(layer, event.clientX, event.clientY);
                if (anchor) {
                    textDrag = { anchor, layer, id: event.pointerId };
                    selectionStarted = true;
                    selectPdfText(anchor, anchor);
                    root.setPointerCapture(event.pointerId); event.preventDefault(); return;
                }
            }
        }
        if (!active || !ready || event.button !== 0 || !page || page.querySelector('.textLayer span') || event.target.closest('.pdf-highlight')) return;
        const bounds = page.getBoundingClientRect();
        const point = { x: Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width)), y: Math.max(0, Math.min(1, (event.clientY - bounds.top) / bounds.height)) };
        const preview = document.createElement('div'); preview.className = 'pdf-highlight';
        page.querySelector('.pdf-highlights').append(preview);
        area = { page: Number(page.dataset.page), bounds, start: point, preview, id: event.pointerId };
        root.setPointerCapture(event.pointerId); event.preventDefault();
    });
    root.addEventListener('pointermove', event => {
        if (erasing && erasePointer === event.pointerId) { eraseAt(event); return; }
        if (textDrag?.id === event.pointerId) {
            const end = pdfCaretAt(textDrag.layer, event.clientX, event.clientY);
            if (end) selectPdfText(textDrag.anchor, end);
            event.preventDefault(); return;
        }
        if (!area || area.id !== event.pointerId) return;
        const x = Math.max(0, Math.min(1, (event.clientX - area.bounds.left) / area.bounds.width));
        const y = Math.max(0, Math.min(1, (event.clientY - area.bounds.top) / area.bounds.height));
        area.mark = { page: area.page, x: Math.min(x, area.start.x), y: Math.min(y, area.start.y), width: Math.abs(x - area.start.x), height: Math.abs(y - area.start.y) };
        const mark = area.mark;
        Object.assign(area.preview.style, { left: mark.x * 100 + '%', top: mark.y * 100 + '%', width: mark.width * 100 + '%', height: mark.height * 100 + '%' });
    });
    const finishArea = event => {
        textDrag = null;
        if (event.type === 'pointercancel') { selectionStarted = false; window.getSelection()?.removeAllRanges(); }
        if (erasePointer === event.pointerId) { erasePointer = null; lastErase = null; }
        if (!area) return;
        const mark = area.mark; area.preview.remove(); area = null;
        if (event.type === 'pointerup' && mark?.width > .002 && mark.height > .002 && marks.length < 2000) { marks.push(mark); changed(); }
    };
    root.addEventListener('pointerup', finishArea);
    root.addEventListener('pointercancel', finishArea);
    root.addEventListener('pointerup', () => {
        if (!active || !ready || !selectionStarted) return;
        selectionStarted = false;
        setTimeout(() => {
            const selection = window.getSelection();
            if (!selection?.rangeCount || selection.isCollapsed) return;
            const range = selection.getRangeAt(0);
            if (!root.contains(range.commonAncestorContainer)) return;
            const additions = [];
            root.querySelectorAll('.pdf-page').forEach(page => {
                const bounds = page.getBoundingClientRect();
                // Element rectangles may cover an entire span/paragraph even
                // when only some characters are selected. Measure text only.
                const textLayer = page.querySelector('.textLayer');
                const walker = document.createTreeWalker(textLayer, NodeFilter.SHOW_TEXT);
                const rects = [];
                while (walker.nextNode()) {
                    const node = walker.currentNode;
                    if (!node.textContent.trim() || !range.intersectsNode(node)) continue;
                    const selectedText = document.createRange();
                    selectedText.selectNodeContents(node);
                    if (range.startContainer === node) selectedText.setStart(node, range.startOffset);
                    if (range.endContainer === node) selectedText.setEnd(node, range.endOffset);
                    if (!selectedText.collapsed) rects.push(...selectedText.getClientRects());
                }
                for (const rect of rects) {
                    const left = Math.max(rect.left, bounds.left), right = Math.min(rect.right, bounds.right);
                    const top = Math.max(rect.top, bounds.top), bottom = Math.min(rect.bottom, bounds.bottom);
                    if (right - left < 1 || bottom - top < 1) continue;
                    const mark = { page: Number(page.dataset.page), x: (left - bounds.left) / bounds.width, y: (top - bounds.top) / bounds.height, width: (right - left) / bounds.width, height: (bottom - top) / bounds.height };
                    if (!additions.some(other => JSON.stringify(other) === JSON.stringify(mark))) additions.push(mark);
                }
            });
            selection.removeAllRanges();
            if (marks.length + additions.length > 2000) { status.textContent = 'Batas highlight tercapai. Hapus sebagian tanda terlebih dahulu.'; return; }
            if (additions.length) { marks.push(...additions); changed(); }
        }, 0);
    });
    fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(response => { if (!response.ok) throw new Error('load'); return response.json(); })
        .then(data => {
            marks = data.highlights || []; ready = true; toggle.disabled = false; eraser.disabled = false;
            try {
                const pending = sessionStorage.getItem(storageKey);
                if (pending) { marks = JSON.parse(pending); changed(); }
            } catch {}
            paint();
        }).catch(() => { status.textContent = 'Highlight gagal dimuat. Muat ulang sebelum menandai.'; });
    return { paint };
}
