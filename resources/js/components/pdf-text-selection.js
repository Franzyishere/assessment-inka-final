// Resolve a caret from rendered glyphs, not the browser's word/line snapping.
export function pdfCaretAt(layer, x, y) {
    const walker = document.createTreeWalker(layer, NodeFilter.SHOW_TEXT);
    let nearest = null, distance = Infinity;
    while (walker.nextNode()) {
        const node = walker.currentNode;
        if (!node.textContent.trim()) continue;
        const range = document.createRange(); range.selectNodeContents(node);
        const rect = range.getBoundingClientRect();
        if (!rect.width || !rect.height) continue;
        const dx = Math.max(rect.left - x, 0, x - rect.right);
        const dy = Math.max(rect.top - y, 0, y - rect.bottom);
        const score = dy * dy * 100 + dx * dx;
        if (score < distance) { nearest = node; distance = score; }
    }
    if (!nearest) return null;
    const range = document.createRange();
    let offset = 0; distance = Infinity;
    for (let i = 0; i < nearest.length; i++) {
        range.setStart(nearest, i); range.setEnd(nearest, i + 1);
        const rect = range.getBoundingClientRect();
        const middle = rect.left + rect.width / 2;
        const score = Math.abs(middle - x);
        if (score < distance) { distance = score; offset = x < middle ? i : i + 1; }
    }
    return { node: nearest, offset };
}

export function selectPdfText(anchor, end) {
    const a = document.createRange(), b = document.createRange();
    a.setStart(anchor.node, anchor.offset); a.collapse(true);
    b.setStart(end.node, end.offset); b.collapse(true);
    const forward = a.compareBoundaryPoints(Range.START_TO_START, b) <= 0;
    const range = document.createRange();
    const start = forward ? anchor : end, finish = forward ? end : anchor;
    range.setStart(start.node, start.offset); range.setEnd(finish.node, finish.offset);
    const selection = window.getSelection();
    selection.removeAllRanges(); selection.addRange(range);
}
