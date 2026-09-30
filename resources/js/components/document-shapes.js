const ns = 'http://www.w3.org/2000/svg';
const copy = value => JSON.parse(JSON.stringify(value));
const el = (tag, attrs = {}) => {
    const node = document.createElementNS(ns, tag);
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
    return node;
};

export function fishbone() {
    const group = `fishbone-${crypto.randomUUID()}`;
    const shapes = [{ type: 'arrow', points: [[40, 300], [780, 300]] }, { type: 'text', points: [[780, 255]], width: 200, height: 90, text: '' }];
    [200, 420, 640].forEach((x, i) => {
        shapes.push({ type: 'line', points: [[x - 60, 130], [x + 45, 300]] }, { type: 'line', points: [[x - 60, 470], [x + 45, 300]] });
        shapes.push({ type: 'text', points: [[x - 130, 60]], width: 190, height: 70, text: '' });
        shapes.push({ type: 'text', points: [[x - 130, 470]], width: 190, height: 70, text: '' });
    });
    return shapes.map(shape => ({ ...shape, group }));
}

export function shapeBounds(shapes) {
    if (!shapes.length) return { left: 0, top: 0, right: 0, bottom: 0 };
    return {
        left: Math.min(...shapes.map(s => Math.min(...s.points.map(p => p[0])))),
        top: Math.min(...shapes.map(s => Math.min(...s.points.map(p => p[1])))),
        right: Math.max(...shapes.map(s => Math.max(...s.points.map(p => p[0])) + (s.type === 'text' ? textboxLayout(s).width : 0))),
        bottom: Math.max(...shapes.map(s => Math.max(...s.points.map(p => p[1])) + (s.type === 'text' ? textboxLayout(s).height : 0))),
    };
}

export function textboxLayout(shape) {
    const width = shape.width || 220;
    const limit = Math.max(5, Math.floor((width - 20) / 11));
    const lines = [];
    for (const paragraph of (shape.text || '').split('\n')) {
        let line = '';
        for (const char of paragraph) {
            if (line.length >= limit) { lines.push(line); line = ''; }
            line += char;
        }
        lines.push(line);
    }
    return { width, height: Math.max(shape.height || 90, lines.length * 23 + 20), lines };
}

export function sheetHeight(shapes) {
    return Math.max(120, ...shapes.map(shape => Math.max(...shape.points.map(p => p[1])) + (shape.type === 'text' ? textboxLayout(shape).height : 0) + 30));
}

export function drawShapes(svg, shapes, selected = -1, selection = new Set(selected >= 0 ? [selected] : [])) {
    svg.replaceChildren();
    shapes.forEach((shape, index) => {
        const g = el('g', { 'data-shape': index, fill: 'none', stroke: selection.has(index) ? '#b91c1c' : '#111827', 'stroke-width': 2, 'stroke-linejoin': 'round' });
        if (shape.type === 'text') {
            const [x, y] = shape.points[0], { width, height, lines } = textboxLayout(shape);
            g.append(el('rect', { x, y, width, height, rx: 4, fill: 'white' }));
            const text = el('text', { x: x + 10, y: y + 25, fill: '#111827', stroke: 'none', 'font-family': 'Arial, sans-serif', 'font-size': 18 });
            const size = 18;
            text.setAttribute('font-size', size);
            text.setAttribute('y', y + 10 + size);
            lines.forEach((line, i) => {
                const span = el('tspan', { x: x + 10, dy: i ? 23 : 0 });
                span.textContent = line;
                text.append(span);
            });
            if (shape.text) g.append(text);
            if (index === selected && selection.size === 1) g.append(el('rect', { x: x + width - 12, y: y + height - 12, width: 12, height: 12, fill: '#b91c1c', 'data-resize': 'true' }));
        } else {
            const path = el('polyline', { points: shape.points.map(p => p.join(',')).join(' '), 'stroke-linecap': 'round' });
            const hit = path.cloneNode();
            hit.setAttribute('stroke', 'transparent'); hit.setAttribute('stroke-width', 18);
            g.append(hit, path);
            if (shape.type === 'arrow') {
                const [a, b] = shape.points, angle = Math.atan2(b[1] - a[1], b[0] - a[0]);
                const ends = [-.5, .5].map(offset => [b[0] - 16 * Math.cos(angle + offset), b[1] - 16 * Math.sin(angle + offset)]);
                g.append(el('polyline', { points: [ends[0], b, ends[1]].map(p => p.join(',')).join(' ') }));
            }
        }
        svg.append(g);
    });
    if (selection.size > 1) {
        const bounds = shapeBounds([...selection].map(index => shapes[index]).filter(Boolean));
        svg.append(el('rect', { x: bounds.left, y: bounds.top, width: bounds.right - bounds.left, height: bounds.bottom - bounds.top, fill: 'none', stroke: '#b91c1c', 'stroke-dasharray': '8 6', 'pointer-events': 'none' }));
    }
}

export function createShapeSheet(initial, onChange, host) {
    let shapes = copy(initial), selected = -1, tool = 'select', gesture = null, field = null, lastPress = null;
    let selection = new Set();
    const dom = document.createElement('div');
    dom.className = 'document-shapes'; dom.contentEditable = 'false';
    dom.tabIndex = 0;
    const toolbar = document.createElement('div'); toolbar.className = 'document-shapes-tools';
    const panel = document.createElement('div'); panel.className = 'document-shapes-panel';
    const svg = el('svg', { viewBox: '0 0 1000 600', role: 'img', 'aria-label': 'Bentuk dalam lembar jawaban' });
    panel.append(svg); dom.append(panel);
    const note = document.createElement('p'); note.className = 'document-shapes-hint';
    note.setAttribute('role', 'status');
    toolbar.append(note);
    let logicalHeight = 800;
    const sizeSheet = () => {
        const scale = (host?.clientWidth || 1000) / 1000;
        if (host) host.style.setProperty('--answer-unit', `${scale}px`);
        if (host) host.style.minHeight = `${sheetHeight(shapes) * scale}px`;
        logicalHeight = Math.max(sheetHeight(shapes), (host?.clientHeight || 0) / scale);
        svg.setAttribute('viewBox', `0 0 1000 ${logicalHeight}`);
        svg.style.height = `${logicalHeight * scale}px`;
    };
    const render = () => {
        sizeSheet();
        selection = new Set([...selection].filter(index => index < shapes.length));
        drawShapes(svg, shapes, selected, selection);
        if (gesture?.area) {
            const [a, b] = [gesture.start, gesture.end || gesture.start];
            svg.append(el('rect', { x: Math.min(a[0], b[0]), y: Math.min(a[1], b[1]), width: Math.abs(a[0] - b[0]), height: Math.abs(a[1] - b[1]), fill: '#ef444420', stroke: '#b91c1c', 'stroke-dasharray': '5 4', 'pointer-events': 'none' }));
        }
        dom.dataset.mode = tool;
        toolbar.querySelectorAll('[data-tool]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.tool === tool)));
    };
    const commit = () => { onChange(copy(shapes)); render(); };
    const finishText = () => {
        if (!field) return;
        shapes[field.index].text = field.node.value;
        field.node.remove(); field = null; commit();
    };
    const editText = index => {
        finishText(); selected = index; selection = new Set([index]);
        const shape = shapes[index];
        const input = document.createElement('textarea');
        input.maxLength = 2000; input.value = shape.text || ''; input.setAttribute('aria-label', 'Isi textbox');
        const placeInput = () => {
            const scale = (host?.clientWidth || 1000) / 1000;
            Object.assign(input.style, { left: `${shape.points[0][0] * scale}px`, top: `${shape.points[0][1] * scale}px`, width: `${textboxLayout(shape).width * scale}px`, height: `${textboxLayout(shape).height * scale}px`, fontSize: `${18 * scale}px`, lineHeight: `${23 * scale}px` });
        };
        placeInput();
        panel.append(input); field = { index, node: input };
        input.addEventListener('input', () => { shape.text = input.value; shapes[index].text = input.value; placeInput(); sizeSheet(); onChange(copy(shapes)); });
        input.addEventListener('blur', finishText);
        input.focus(); input.select();
    };
    Object.entries({ select: 'Pilih / Geser', group: 'Pilih fishbone', all: 'Pilih semua objek', line: 'Garis', arrow: 'Panah', pen: 'Pen', delete: 'Hapus objek' }).forEach(([key, title]) => {
        const button = document.createElement('button'); button.type = 'button'; button.textContent = title;
        button.dataset.tool = key;
        button.addEventListener('click', () => {
            finishText();
            if (key === 'delete') { shapes = shapes.filter((_, i) => !selection.has(i)); selected = -1; selection.clear(); commit(); return; }
            if (key === 'group' || key === 'all') {
                const group = shapes[selected]?.group || (selected < 0 ? shapes.findLast(s => s.group)?.group : null);
                if (key === 'group' && !group) { note.textContent = 'Objek ini belum memiliki grup. Gunakan Pilih semua objek atau Shift + klik.'; return; }
                selection = new Set(shapes.map((s, i) => key === 'all' || s.group === group ? i : -1).filter(i => i >= 0));
                selected = [...selection][0] ?? -1; tool = 'select'; lastPress = null; render(); dom.focus({ preventScroll: true }); return;
            }
            tool = key; selected = -1; selection.clear(); render();
        }); toolbar.append(button);
    });
    const point = event => {
        const p = new DOMPoint(event.clientX, event.clientY).matrixTransform(svg.getScreenCTM().inverse());
        return [Math.round(Math.max(0, Math.min(1000, p.x))), Math.round(Math.max(0, Math.min(20000, p.y)))];
    };
    svg.addEventListener('pointerdown', event => {
        if (event.button !== 0 || gesture) return;
        finishText();
        dom.focus({ preventScroll: true });
        const start = point(event);
        if (tool === 'marquee' || (tool === 'select' && !event.target.closest('[data-shape]'))) {
            selection.clear(); selected = -1;
            gesture = { start, area: true, id: event.pointerId };
            svg.setPointerCapture(event.pointerId); render(); return;
        }
        if (tool === 'select') {
            const target = event.target.closest('[data-shape]'); selected = target ? +target.dataset.shape : -1;
            if (event.shiftKey && selected >= 0) {
                selection.has(selected) ? selection.delete(selected) : selection.add(selected);
                lastPress = null; render(); return;
            }
            if (!selection.has(selected)) selection = new Set(selected >= 0 ? [selected] : []);
            if (selection.size === 1 && !event.target.hasAttribute('data-resize') && selected >= 0 && shapes[selected].type === 'text' && lastPress?.index === selected && event.timeStamp - lastPress.time < 450) {
                lastPress = null; render(); editText(selected); event.preventDefault(); return;
            }
            lastPress = { index: selected, time: event.timeStamp };
            if (selected >= 0) gesture = { start, originals: [...selection].map(index => ({ index, shape: copy(shapes[index]) })), resize: selection.size === 1 && event.target.hasAttribute('data-resize') };
        } else {
            if (shapes.length >= 1500) { note.textContent = 'Maksimal 1500 objek per lembar jawaban.'; return; }
            selected = shapes.length;
            selection = new Set([selected]);
            if (tool === 'text') {
                shapes.push({ type: 'text', points: [[Math.min(780, start[0]), start[1]]], width: 220, height: 90, text: '' });
                commit(); tool = 'select'; editText(selected); return;
            }
            shapes.push({ type: tool, points: tool === 'pen' ? [start] : [start, start] });
            gesture = { start };
        }
        if (gesture) { gesture.id = event.pointerId; svg.setPointerCapture(event.pointerId); }
        render();
    });
    svg.addEventListener('pointermove', event => {
        if (!gesture || gesture.id !== event.pointerId) return;
        if (gesture.area) {
            const p = point(event); gesture.end = p;
            const left = Math.min(p[0], gesture.start[0]), right = Math.max(p[0], gesture.start[0]);
            const top = Math.min(p[1], gesture.start[1]), bottom = Math.max(p[1], gesture.start[1]);
            selection = new Set(shapes.map((shape, index) => {
                const bounds = shapeBounds([shape]);
                return bounds.left <= right && bounds.right >= left && bounds.top <= bottom && bounds.bottom >= top ? index : -1;
            }).filter(index => index >= 0));
            render(); return;
        }
        const p = point(event), shape = shapes[selected];
        if (Math.abs(p[0] - gesture.start[0]) + Math.abs(p[1] - gesture.start[1]) > 4) lastPress = null;
        if (gesture.resize) {
            shape.width = Math.max(80, Math.min(800, 1000 - shape.points[0][0], p[0] - shape.points[0][0]));
            shape.height = Math.max(40, Math.min(4000, p[1] - shape.points[0][1]));
        } else if (gesture.originals) {
            const bounds = shapeBounds(gesture.originals.map(item => item.shape));
            const dx = Math.max(-bounds.left, Math.min(1000 - bounds.right, p[0] - gesture.start[0]));
            const dy = Math.max(-bounds.top, Math.min(20000 - bounds.bottom, p[1] - gesture.start[1]));
            gesture.originals.forEach(({ index, shape: original }) => {
                shapes[index].points = original.points.map(([x, y]) => [x + dx, y + dy]);
            });
        } else if (tool === 'pen') { if (shape.points.length < 1000) shape.points.push(p); }
        else shape.points[1] = p;
        render();
    });
    const finish = () => {
        if (!gesture) return;
        const area = gesture.area; gesture = null;
        if (area) { selected = [...selection][0] ?? -1; tool = 'select'; render(); }
        else commit();
    };
    svg.addEventListener('pointerup', finish); svg.addEventListener('pointercancel', finish); svg.addEventListener('lostpointercapture', finish);
    svg.addEventListener('dblclick', event => {
        const target = event.target.closest('[data-shape]');
        const index = target ? +target.dataset.shape : selected;
        if (index >= 0 && shapes[index]?.type === 'text') editText(index);
    });
    const clearSelection = event => {
        if (dom.contains(event.target) || event.target.closest('[data-shape-tools], [data-editor-command], [data-insert-shape]')) return;
        selected = -1; selection.clear(); lastPress = null; render();
    };
    document.addEventListener('pointerdown', clearSelection, true);
    dom.addEventListener('keydown', event => {
        if (!['Backspace', 'Delete'].includes(event.key) || event.target.closest('textarea, input') || !selection.size) return;
        event.preventDefault(); event.stopPropagation();
        shapes = shapes.filter((_, index) => !selection.has(index));
        selected = -1; selection.clear(); commit();
    });
    render();
    const observer = host ? new ResizeObserver(() => sizeSheet()) : null;
    if (host) observer.observe(host);
    return { dom, toolbar, update: value => { shapes = copy(value); if (!field) render(); }, destroy: () => { document.removeEventListener('pointerdown', clearSelection, true); observer?.disconnect(); field?.node.remove(); dom.remove(); toolbar.remove(); if (host) host.style.minHeight = ''; } };
}

export function initializeDocumentPreviews() {
    document.querySelectorAll('[data-answer-scene]').forEach(node => {
        if (node.closest('[data-rich-text-editor]') || node.dataset.rendered) return;
        try {
            const shapes = JSON.parse(node.dataset.answerScene);
            const height = sheetHeight(shapes);
            const svg = el('svg', { viewBox: `0 0 1000 ${height}`, role: 'img', 'aria-label': 'Diagram jawaban' });
            drawShapes(svg, shapes); node.replaceChildren(svg); node.dataset.rendered = 'true';
            if (node.dataset.answerLayer === 'true') {
                const host = node.parentElement;
                host.classList.add('document-answer-sheet');
                const resize = () => { host.style.minHeight = `${height * host.clientWidth / 1000}px`; host.style.setProperty('--answer-unit', `${host.clientWidth / 1000}px`); };
                resize();
                const observer = new ResizeObserver(() => {
                    if (!node.isConnected) { observer.disconnect(); return; }
                    resize();
                });
                observer.observe(host);
            }
        } catch { node.textContent = 'Diagram tidak dapat ditampilkan.'; }
    });
}
