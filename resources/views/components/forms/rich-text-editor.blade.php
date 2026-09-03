@props(['name', 'value' => '', 'required' => false])

<div data-rich-text-editor data-required="{{ $required ? 'true' : 'false' }}" class="min-w-0 max-w-full overflow-hidden rounded-xl border border-gray-300 bg-white shadow-theme-xs focus-within:border-brand-400 focus-within:ring-3 focus-within:ring-brand-500/10">
    <textarea data-editor-input name="{{ $name }}" class="hidden">{{ $value }}</textarea>
    <div class="flex max-w-full flex-wrap items-center gap-1 overflow-x-auto border-b border-gray-200 bg-gray-50 p-2.5" aria-label="Toolbar editor jawaban">
        <select data-editor-font aria-label="Jenis font" class="h-9 rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700"><option value="">Font</option><option value="Arial">Arial</option><option value="Calibri">Calibri</option><option value="Georgia">Georgia</option><option value="Times New Roman">Times New Roman</option></select>
        <select data-editor-size aria-label="Ukuran font" class="h-9 rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700"><option value="">Ukuran</option>@foreach(['12px','14px','16px','18px','20px','24px','28px'] as $size)<option value="{{ $size }}">{{ str_replace('px', '', $size) }}</option>@endforeach</select>
        <input data-editor-color type="color" value="#1f2937" aria-label="Warna teks" class="h-9 w-9 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        @foreach(['bold' => 'B', 'italic' => 'I', 'underline' => 'U', 'strike' => 'S'] as $command => $label)<button type="button" data-editor-command="{{ $command }}" class="editor-tool-btn {{ $command === 'italic' ? 'italic' : '' }} {{ $command === 'underline' ? 'underline' : '' }} {{ $command === 'strike' ? 'line-through' : '' }}" title="{{ ucfirst($command) }}">{{ $label }}</button>@endforeach
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        @foreach(['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan', 'justify' => 'Rata'] as $alignment => $label)<button type="button" data-editor-align="{{ $alignment }}" class="editor-tool-btn px-2 text-[10px]" title="Rata {{ strtolower($label) }}">{{ $label }}</button>@endforeach
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        <button type="button" data-editor-command="bulletList" class="editor-tool-btn" title="Daftar poin">•</button><button type="button" data-editor-command="orderedList" class="editor-tool-btn text-xs" title="Daftar nomor">1.</button>
        <details class="relative"><summary class="editor-tool-btn flex cursor-pointer list-none items-center justify-center px-2 text-xs">Tabel</summary><div class="absolute right-0 z-40 mt-2 grid w-44 gap-1 rounded-xl border border-gray-200 bg-white p-2 shadow-lg">@foreach(['insertTable' => 'Buat tabel 3 × 3', 'addRowAfter' => 'Tambah baris', 'addColumnAfter' => 'Tambah kolom', 'deleteRow' => 'Hapus baris', 'deleteColumn' => 'Hapus kolom', 'deleteTable' => 'Hapus tabel'] as $command => $label)<button type="button" data-editor-command="{{ $command }}" class="rounded-lg px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-100">{{ $label }}</button>@endforeach</div></details>
        <span class="mx-1 h-6 w-px bg-gray-300"></span>
        <button type="button" data-editor-command="undo" class="editor-tool-btn" title="Undo">↶</button><button type="button" data-editor-command="redo" class="editor-tool-btn" title="Redo">↷</button>
    </div>
    <div data-editor-surface class="min-h-72"></div>
    <p data-editor-error class="hidden border-t border-error-100 bg-error-50 px-4 py-2 text-xs text-error-600">Jawaban wajib diisi sebelum disimpan.</p>
</div>
