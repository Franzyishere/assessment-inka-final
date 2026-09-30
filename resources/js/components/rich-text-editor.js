import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import { Color, FontSize, TextStyle } from '@tiptap/extension-text-style';
import FontFamily from '@tiptap/extension-font-family';
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table';
import { DocumentShape, DocumentSpacer } from './document-shape-node';
import { fishbone, shapeBounds } from './document-shapes';
import { createAnswerDraft } from './answer-draft';

const commands = {
    bold: editor => editor.chain().focus().toggleBold().run(),
    italic: editor => editor.chain().focus().toggleItalic().run(),
    underline: editor => editor.chain().focus().toggleUnderline().run(),
    strike: editor => editor.chain().focus().toggleStrike().run(),
    bulletList: editor => editor.chain().focus().toggleBulletList().run(),
    orderedList: editor => editor.chain().focus().toggleOrderedList().run(),
    undo: editor => stableHistory(editor, 'undo'),
    redo: editor => stableHistory(editor, 'redo'),
    insertTable: editor => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    addRowAfter: editor => editor.chain().focus().addRowAfter().run(),
    addColumnAfter: editor => editor.chain().focus().addColumnAfter().run(),
    deleteRow: editor => editor.chain().focus().deleteRow().run(),
    deleteColumn: editor => editor.chain().focus().deleteColumn().run(),
    deleteTable: editor => editor.chain().focus().deleteTable().run(),
};

function stableHistory(editor, command) {
    // Commit an active shape textbox before undoing its document transaction.
    if (document.activeElement?.matches('.document-shapes textarea')) document.activeElement.blur();
    const scrolls = [];
    for (let node = editor.view.dom; node; node = node.parentElement) {
        scrolls.push([node, node.scrollLeft, node.scrollTop]);
    }
    const x = window.scrollX, y = window.scrollY;
    editor.chain().focus(undefined, { scrollIntoView: false })[command]().run();
    const restore = () => {
        scrolls.forEach(([node, left, top]) => { node.scrollLeft = left; node.scrollTop = top; });
        window.scrollTo({ left: x, top: y, behavior: 'instant' });
    };
    restore(); requestAnimationFrame(restore);
}

export function initializeRichTextEditors() {
    document.querySelectorAll('[data-rich-text-editor]').forEach(root => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';

        const input = root.querySelector('[data-editor-input]');
        const surface = root.querySelector('[data-editor-surface]');
        const sessionHeader = root.closest('.assessment-workspace')?.querySelector('[data-assessment-session-header]');
        if (sessionHeader && root.classList.contains('document-answer-editor')) {
            const updateToolbarOffset = () => {
                const top = parseFloat(getComputedStyle(sessionHeader).top) || 0;
                root.style.setProperty('--answer-toolbar-top', (sessionHeader.getBoundingClientRect().height + top) + 'px');
            };
            const observer = new ResizeObserver(() => {
                if (!root.isConnected) { observer.disconnect(); return; }
                updateToolbarOffset();
            });
            observer.observe(sessionHeader);
            updateToolbarOffset();
        }
        const error = root.querySelector('[data-editor-error]');
        const required = root.dataset.required === 'true';
        const legacy = root.querySelector('[data-legacy-diagram]');
        const draft = createAnswerDraft(root, input);
        let legacyMigrated = false;
        let initialContent = input.value || '';
        if (legacy) {
            try {
                const shapes = JSON.parse(legacy.value || '[]');
                if (!Array.isArray(shapes)) throw new Error('Invalid legacy diagram');
                legacyMigrated = true;
                if (shapes.length) {
                    const placeholder = document.createElement('span');
                    placeholder.dataset.answerScene = JSON.stringify(shapes);
                    initialContent += placeholder.outerHTML + '<p></p>';
                }
            } catch { /* Invalid legacy data is still retained in the database. */ }
        }
        initialContent = draft.restore(initialContent);
        // Upgrade legacy floating scenes to in-flow blocks without losing any objects.
        const parsed = document.createElement('div');
        parsed.innerHTML = initialContent;
        parsed.querySelectorAll('[data-answer-scene]').forEach(marker => {
            if (marker.dataset.answerFlow === 'true') return;
            try {
                const scene = JSON.parse(marker.dataset.answerScene || '[]');
                const top = shapeBounds(scene).top;
                marker.dataset.answerScene = JSON.stringify(scene.map(shape => ({
                    ...shape, points: shape.points.map(([x, y]) => [x, y - top + 30]),
                })));
                marker.dataset.answerFlow = 'true';
                marker.removeAttribute('data-answer-layer');
            } catch { /* Keep malformed legacy content for server validation. */ }
        });
        parsed.querySelectorAll('[data-answer-space]').forEach(spacer => spacer.remove());
        initialContent = parsed.innerHTML;

        const editor = new Editor({
            element: surface,
            content: initialContent,
            extensions: [
                StarterKit,
                TextStyle,
                Color,
                FontSize,
                FontFamily,
                TextAlign.configure({ types: ['heading', 'paragraph'] }),
                Table.configure({ resizable: true }),
                TableRow,
                TableHeader,
                TableCell,
                DocumentShape,
                DocumentSpacer,
            ],
            editorProps: {
                attributes: {
                    class: 'tiptap-answer-surface',
                    spellcheck: 'true',
                },
            },
            onUpdate: ({ editor: current }) => {
                input.value = current.isEmpty ? '' : current.getHTML();
                draft.changed(input.value);
                if (!current.isEmpty) error?.classList.add('hidden');
            },
        });
        root.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey) && ['z', 'y'].includes(event.key.toLowerCase())) {
                event.preventDefault(); event.stopPropagation();
                stableHistory(editor, event.key.toLowerCase() === 'y' || event.shiftKey ? 'redo' : 'undo');
            }
        }, true);
        root.querySelectorAll('[data-editor-command]').forEach(button => button.addEventListener('mousedown', event => event.preventDefault()));
        input.value = editor.isEmpty ? '' : editor.getHTML();
        draft.changed(input.value);
        // Clear the separate legacy field only when it has been incorporated into this document.
        if (legacyMigrated) {
            const migrated = document.createElement('input');
            migrated.type = 'hidden'; migrated.name = 'diagram'; migrated.value = '[]';
            root.append(migrated);
        }
        root.querySelectorAll('[data-insert-shape]').forEach(button => {
            button.addEventListener('click', () => {
                const kind = button.dataset.insertShape;
                const scene = kind === 'fishbone' ? fishbone() : kind === 'text'
                    ? [{ type: 'text', points: [[120, 100]], width: 240, height: 100, text: '' }]
                    : [];
                const top = shapeBounds(scene).top;
                const additions = scene.map(shape => ({ ...shape, points: shape.points.map(([x, y]) => [x, y - top + 30]) }));
                editor.chain().insertContentAt(editor.state.doc.content.size, [
                    { type: 'documentShape', attrs: { scene: additions, flow: true } },
                    { type: 'paragraph' },
                ]).run();
            });
        });

        root.querySelectorAll('[data-editor-command]').forEach(button => {
            button.addEventListener('click', () => {
                commands[button.dataset.editorCommand]?.(editor);

                const tableMenu = button.closest('[data-editor-table-menu]');
                if (tableMenu) tableMenu.open = false;
            });
        });
        root.querySelector('[data-editor-font]')?.addEventListener('change', event => {
            const chain = editor.chain().focus();
            event.target.value ? chain.setFontFamily(event.target.value).run() : chain.unsetFontFamily().run();
        });
        root.querySelector('[data-editor-size]')?.addEventListener('change', event => {
            const chain = editor.chain().focus();
            event.target.value ? chain.setFontSize(event.target.value).run() : chain.unsetFontSize().run();
        });
        root.querySelector('[data-editor-color]')?.addEventListener('input', event => editor.chain().focus().setColor(event.target.value).run());
        root.querySelectorAll('[data-editor-align]').forEach(button => {
            button.addEventListener('click', () => editor.chain().focus().setTextAlign(button.dataset.editorAlign).run());
        });

        root.closest('form')?.addEventListener('submit', event => {
            input.value = editor.isEmpty ? '' : editor.getHTML();
            if (required && editor.isEmpty) {
                event.preventDefault();
                error?.classList.remove('hidden');
                surface.focus();
            }
        });
    });
}
