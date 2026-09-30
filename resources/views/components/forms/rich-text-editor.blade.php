@props(['name', 'value' => '', 'required' => false, 'document' => false, 'legacyDiagram' => [], 'draftKey' => ''])

<div data-rich-text-editor data-draft-key="{{ $draftKey }}" data-required="{{ $required ? 'true' : 'false' }}" class="{{ $document ? 'document-answer-editor' : 'overflow-hidden' }} min-w-0 max-w-full rounded-xl border border-gray-300 bg-white shadow-theme-xs focus-within:border-brand-400 focus-within:ring-3 focus-within:ring-brand-500/10">
    <textarea data-editor-input name="{{ $name }}" class="hidden">{{ $value }}</textarea>
    @if($document)
    <details open data-answer-toolbar class="answer-toolbar-sticky">
        <summary class="answer-toolbar-toggle"><span>Toolbar jawaban</span><span class="toolbar-collapse-label">Minimalkan −</span><span class="toolbar-expand-label">Tampilkan +</span></summary>
    @else
    <div data-answer-toolbar>
    @endif
    @if($document)
        <textarea data-legacy-diagram hidden>{{ is_string($legacyDiagram) ? $legacyDiagram : json_encode($legacyDiagram) }}</textarea>
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-white px-3 py-2" aria-label="Sisipkan ke dokumen">
            <span class="mr-1 text-xs font-semibold text-gray-500">Sisipkan</span>
            <button type="button" data-insert-shape="text" class="editor-tool-btn px-3 text-xs">Textbox</button>
            <button type="button" data-insert-shape="fishbone" class="editor-tool-btn px-3 text-xs">Fishbone</button>
            <button type="button" data-insert-shape="drawing" class="editor-tool-btn px-3 text-xs">Garis / Panah / Pen</button>
            <div data-shape-tools class="w-full"></div>
        </div>
    @endif
    <div class="relative flex max-w-full flex-wrap items-center gap-1 overflow-visible border-b border-gray-200 bg-gray-50 p-2.5" aria-label="Toolbar editor jawaban">
        <select data-editor-font aria-label="Jenis font" class="h-9 rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700"><option value="">Font</option><option value="Arial">Arial</option><option value="Calibri">Calibri</option><option value="Georgia">Georgia</option><option value="Times New Roman">Times New Roman</option></select>
        <select data-editor-size aria-label="Ukuran font" class="h-9 rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700"><option value="">Ukuran</option>@foreach(['12px','14px','16px','18px','20px','24px','28px'] as $size)<option value="{{ $size }}">{{ str_replace('px', '', $size) }}</option>@endforeach</select>
        <input data-editor-color type="color" value="#000000" aria-label="Warna teks" class="h-9 w-9 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        @foreach(['bold' => 'B', 'italic' => 'I', 'underline' => 'U', 'strike' => 'S'] as $command => $label)<button type="button" data-editor-command="{{ $command }}" class="editor-tool-btn {{ $command === 'italic' ? 'italic' : '' }} {{ $command === 'underline' ? 'underline' : '' }} {{ $command === 'strike' ? 'line-through' : '' }}" title="{{ ucfirst($command) }}">{{ $label }}</button>@endforeach
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        @foreach(['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan', 'justify' => 'Rata'] as $alignment => $label)<button type="button" data-editor-align="{{ $alignment }}" class="editor-tool-btn px-2 text-[10px]" title="Rata {{ strtolower($label) }}">{{ $label }}</button>@endforeach
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        <button type="button" data-editor-command="bulletList" class="editor-tool-btn" title="Daftar poin">•</button><button type="button" data-editor-command="orderedList" class="editor-tool-btn text-xs" title="Daftar nomor">1.</button>
        <details data-editor-table-menu class="relative"><summary class="editor-tool-btn flex cursor-pointer list-none items-center justify-center gap-1 px-2 text-xs">Tabel <span class="text-[9px] text-gray-400">▼</span></summary><div class="absolute left-0 z-50 mt-2 grid w-44 gap-1 rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg">@foreach(['insertTable' => 'Buat tabel 3 × 3', 'addRowAfter' => 'Tambah baris', 'addColumnAfter' => 'Tambah kolom', 'deleteRow' => 'Hapus baris', 'deleteColumn' => 'Hapus kolom', 'deleteTable' => 'Hapus tabel'] as $command => $label)<button type="button" data-editor-command="{{ $command }}" class="rounded-lg px-3 py-2 text-left text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-700">{{ $label }}</button>@endforeach</div></details>
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        <button type="button" data-editor-command="undo" class="editor-tool-btn" title="Undo">↶</button><button type="button" data-editor-command="redo" class="editor-tool-btn" title="Redo">↷</button>
    </div>
    @if($document)
    </details>
    @else
    </div>
    @endif
    <div data-editor-surface class="min-h-72"></div>
    <p data-editor-error class="hidden border-t border-error-100 bg-error-50 px-4 py-2 text-xs text-error-600">Jawaban wajib diisi sebelum disimpan.</p>
</div>
