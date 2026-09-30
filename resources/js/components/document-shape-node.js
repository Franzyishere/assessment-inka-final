import { Node } from '@tiptap/core';
import { TextSelection } from '@tiptap/pm/state';
import { createShapeSheet } from './document-shapes';

export const DocumentSpacer = Node.create({
    name: 'documentSpacer', group: 'block', atom: true, selectable: false,
    addAttributes() { return { height: { default: 0, parseHTML: element => Number(element.dataset.answerSpace) || 0 } }; },
    parseHTML() { return [{ tag: 'p[data-answer-space]' }]; },
    renderHTML({ node }) { return ['p', { 'data-answer-space': node.attrs.height, style: `height: calc(var(--answer-unit, 1px) * ${node.attrs.height}); margin: 0;` }]; },
});

export const DocumentShape = Node.create({
    name: 'documentShape', group: 'block', atom: true, selectable: false, draggable: false,
    addAttributes() {
        return { flow: { default: true, parseHTML: element => element.dataset.answerFlow === 'true', renderHTML: () => ({ 'data-answer-flow': 'true' }) }, scene: { default: [], parseHTML: element => {
            try { return JSON.parse(element.getAttribute('data-answer-scene') || '[]'); } catch { return []; }
        }, renderHTML: attributes => ({ 'data-answer-scene': JSON.stringify(attributes.scene) }) } };
    },
    parseHTML() { return [{ tag: 'span[data-answer-scene]' }]; },
    renderHTML({ HTMLAttributes }) { return ['span', { ...HTMLAttributes, 'data-answer-flow': 'true' }]; },
    addNodeView() {
        return ({ node, editor, getPos }) => {
            const host = document.createElement('div');
            host.contentEditable = 'false';
            host.className = 'document-flow-shapes';
            const root = editor.options.element.closest('[data-rich-text-editor]');
            host.classList.add('document-answer-sheet');
            const sheet = createShapeSheet(node.attrs.scene, scene => {
                const pos = getPos();
                if (typeof pos === 'number') editor.view.dispatch(editor.state.tr.setNodeMarkup(pos, undefined, { scene, flow: true }));
            }, host);
            host.append(sheet.dom);
            (root.querySelector('[data-shape-tools]') || root).append(sheet.toolbar);
            const activate = event => {
                if (event.target.closest('textarea')) return;
                root.querySelectorAll('.document-shapes-tools').forEach(item => item.hidden = item !== sheet.toolbar);
                const pos = getPos();
                if (typeof pos === 'number') {
                    const end = Math.min(editor.state.doc.content.size, pos + editor.state.doc.nodeAt(pos).nodeSize);
                    editor.view.dispatch(editor.state.tr.setSelection(TextSelection.near(editor.state.doc.resolve(end))));
                }
            };
            host.addEventListener('pointerdown', activate, true);
            root.querySelectorAll('.document-shapes-tools').forEach(item => item.hidden = item !== sheet.toolbar);
            return {
                dom: host,
                update(updated) { if (updated.type.name !== 'documentShape') return false; sheet.update(updated.attrs.scene); return true; },
                stopEvent: event => !event.type.startsWith('drag'),
                ignoreMutation: () => true,
                destroy: sheet.destroy,
            };
        };
    },
});
