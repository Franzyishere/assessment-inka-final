const ns = 'http://www.w3.org/2000/svg';
const clone = value => JSON.parse(JSON.stringify(value));
const element = (tag, attrs) => {
    const node = document.createElementNS(ns, tag);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
    return node;
};

export function initializeAnswerDiagrams() {
    document.querySelectorAll('[data-answer-diagram]').forEach(root => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';
        const editable = root.dataset.editable === 'true';
        const input = root.querySelector('[data-diagram-input]');
        const svg = root.querySelector('svg');
        const textInput = root.querySelector('[data-diagram-text]');
        const status = root.querySelector('[data-diagram-status]');
        let objects;
        try { objects = JSON.parse(input.value || '[]'); } catch { objects = []; }
        if (!Array.isArray(objects)) objects = [];
        let tool = 'select', selected = -1, gesture = null;
        const undo = [], redo = [];
        const message = text => { if (status) status.textContent = text; };
        const snapshot = () => {
            undo.push(clone(objects));
            if (undo.length > 30) undo.shift();
            redo.length = 0;
        };
        const point = event => {
            const p = new DOMPoint(event.clientX, event.clientY).matrixTransform(svg.getScreenCTM().inverse());
            return [Math.round(Math.max(0, Math.min(1000, p.x))), Math.round(Math.max(0, Math.min(600, p.y)))];
        };
        function render() {
            svg.replaceChildren();
            objects.forEach((object, index) => {
                const group = element('g', { 'data-object': index, fill: 'none', stroke: selected === index && editable ? '#b91c1c' : '#111827', 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' });
                const points = object.points;
                if (object.type === 'text') {
                    const text = element('text', { x: points[0][0], y: points[0][1], fill: '#111827', stroke: 'none', 'font-size': 20, 'font-family': 'Arial, sans-serif' });
                    text.textContent = object.text;
                    group.append(text);
                } else {
                    const path = element('polyline', { points: points.map(p => p.join(',')).join(' ') });
                    const hit = path.cloneNode();
                    hit.setAttribute('stroke', 'transparent');
                    hit.setAttribute('stroke-width', '18');
                    group.append(hit, path);
                    if (object.type === 'arrow' && points.length === 2) {
                        const [a, b] = points;
                        const angle = Math.atan2(b[1] - a[1], b[0] - a[0]);
                        const head = [-0.5, 0.5].map(offset => [b[0] - 16 * Math.cos(angle + offset), b[1] - 16 * Math.sin(angle + offset)]);
                        group.append(element('polyline', { points: [head[0], b, head[1]].map(p => p.join(',')).join(' ') }));
                    }
                }
                svg.append(group);
            });
            input.value = JSON.stringify(objects);
            root.querySelectorAll('[data-diagram-tool]').forEach(button => {
                const active = button.dataset.diagramTool === tool;
                button.setAttribute('aria-pressed', String(active));
                button.style.backgroundColor = active ? '#fee2e2' : '';
            });
            root.querySelector('[data-diagram-action="undo"]')?.toggleAttribute('disabled', !undo.length);
            root.querySelector('[data-diagram-action="redo"]')?.toggleAttribute('disabled', !redo.length);
            root.querySelector('[data-diagram-action="delete"]')?.toggleAttribute('disabled', selected < 0);
        }
        render();
        if (!editable) return;
        root.querySelectorAll('[data-diagram-tool]').forEach(button => button.addEventListener('click', () => {
            tool = button.dataset.diagramTool;
            selected = -1;
            render();
        }));
        root.querySelectorAll('[data-diagram-action]').forEach(button => button.addEventListener('click', () => {
            const action = button.dataset.diagramAction;
            if (action === 'undo' && undo.length) { redo.push(clone(objects)); objects = undo.pop(); }
            if (action === 'redo' && redo.length) { undo.push(clone(objects)); objects = redo.pop(); }
            if (action === 'delete' && selected >= 0) { snapshot(); objects.splice(selected, 1); }
            if (action === 'fishbone') {
                if (objects.length > 143) return message('Maksimal 150 objek. Hapus objek yang tidak diperlukan.');
                snapshot();
                objects.push({ type: 'arrow', points: [[100, 300], [900, 300]] });
                [250, 450, 650].forEach(x => {
                    objects.push({ type: 'line', points: [[x - 100, 100], [x, 300]] });
                    objects.push({ type: 'line', points: [[x - 100, 500], [x, 300]] });
                });
            }
            selected = -1;
            render();
            message('Perubahan diagram belum disimpan. Klik tombol Simpan pada jawaban.');
        }));
        svg.addEventListener('pointerdown', event => {
            if (event.button !== 0 || gesture) return;
            const start = point(event);
            if (tool === 'select') {
                const target = event.target.closest('[data-object]');
                selected = target ? Number(target.dataset.object) : -1;
                if (selected >= 0 && objects[selected].type === 'text') textInput.value = objects[selected].text;
                if (selected >= 0) { snapshot(); gesture = { start, original: clone(objects[selected]), id: event.pointerId }; }
            } else {
                if (objects.length >= 150) return message('Maksimal 150 objek.');
                if (tool === 'text' && !textInput.value.trim()) return message('Isi teks textbox terlebih dahulu.');
                snapshot();
                selected = objects.length;
                objects.push({ type: tool, points: tool === 'text' || tool === 'pen' ? [start] : [start, start], ...(tool === 'text' ? { text: textInput.value.trim() } : {}) });
                if (tool !== 'text') gesture = { start, id: event.pointerId };
            }
            if (gesture) svg.setPointerCapture(event.pointerId);
            render();
        });
        svg.addEventListener('pointermove', event => {
            if (!gesture || gesture.id !== event.pointerId) return;
            const p = point(event), object = objects[selected];
            if (tool === 'select') {
                const points = gesture.original.points;
                const dx = Math.max(-Math.min(...points.map(p => p[0])), Math.min(1000 - Math.max(...points.map(p => p[0])), p[0] - gesture.start[0]));
                const dy = Math.max(-Math.min(...points.map(p => p[1])), Math.min(600 - Math.max(...points.map(p => p[1])), p[1] - gesture.start[1]));
                object.points = points.map(([x, y]) => [x + dx, y + dy]);
            } else if (tool === 'pen') {
                if (object.points.length < 1000) object.points.push(p);
            } else object.points[1] = p;
            render();
        });
        const finish = () => {
            if (!gesture) return;
            gesture = null;
            render();
            message('Perubahan diagram belum disimpan. Klik tombol Simpan pada jawaban.');
        };
        svg.addEventListener('pointerup', finish);
        svg.addEventListener('pointercancel', finish);
        svg.addEventListener('lostpointercapture', finish);
        textInput.addEventListener('change', () => {
            if (tool !== 'select' || selected < 0 || objects[selected].type !== 'text') return;
            if (!textInput.value.trim()) return message('Textbox tidak boleh kosong. Gunakan Hapus objek untuk menghapusnya.');
            snapshot();
            objects[selected].text = textInput.value.trim();
            render();
            message('Perubahan diagram belum disimpan. Klik tombol Simpan pada jawaban.');
        });
    });
}
