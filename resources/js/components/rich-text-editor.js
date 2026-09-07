import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import { Color, FontSize, TextStyle } from '@tiptap/extension-text-style';
import FontFamily from '@tiptap/extension-font-family';
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table';

const commands = {
    bold: editor => editor.chain().focus().toggleBold().run(),
    italic: editor => editor.chain().focus().toggleItalic().run(),
    underline: editor => editor.chain().focus().toggleUnderline().run(),
    strike: editor => editor.chain().focus().toggleStrike().run(),
    bulletList: editor => editor.chain().focus().toggleBulletList().run(),
    orderedList: editor => editor.chain().focus().toggleOrderedList().run(),
    undo: editor => editor.chain().focus().undo().run(),
    redo: editor => editor.chain().focus().redo().run(),
    insertTable: editor => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    addRowAfter: editor => editor.chain().focus().addRowAfter().run(),
    addColumnAfter: editor => editor.chain().focus().addColumnAfter().run(),
    deleteRow: editor => editor.chain().focus().deleteRow().run(),
    deleteColumn: editor => editor.chain().focus().deleteColumn().run(),
    deleteTable: editor => editor.chain().focus().deleteTable().run(),
};

export function initializeRichTextEditors() {
    document.querySelectorAll('[data-rich-text-editor]').forEach(root => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';

        const input = root.querySelector('[data-editor-input]');
        const surface = root.querySelector('[data-editor-surface]');
        const error = root.querySelector('[data-editor-error]');
        const required = root.dataset.required === 'true';

        const editor = new Editor({
            element: surface,
            content: input.value || '',
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
            ],
            editorProps: {
                attributes: {
                    class: 'tiptap-answer-surface',
                    spellcheck: 'true',
                },
            },
            onUpdate: ({ editor: current }) => {
                input.value = current.isEmpty ? '' : current.getHTML();
                if (!current.isEmpty) error?.classList.add('hidden');
            },
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
