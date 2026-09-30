@props(['value' => [], 'editable' => false])
<div @if($editable) data-answer-diagram @endif data-editable="{{ $editable ? 'true' : 'false' }}" class="my-4 min-w-0 overflow-hidden rounded-xl border border-gray-300 bg-white">
    <textarea data-diagram-input @if($editable) name="diagram" @endif hidden>{{ is_string($value) ? $value : json_encode($value) }}</textarea>
    <div class="border-b border-gray-200 p-3">
        <h3 class="text-sm font-semibold text-gray-900">Area diagram</h3>
        @if($editable)
            <div class="mt-2 flex flex-wrap gap-2" role="toolbar" aria-label="Alat diagram">
                @foreach(['select' => 'Pilih / Geser', 'line' => 'Garis', 'arrow' => 'Panah', 'pen' => 'Pen', 'text' => 'Textbox'] as $tool => $label)
                    <button type="button" data-diagram-tool="{{ $tool }}" aria-pressed="false" class="rounded border border-gray-300 px-3 py-2 text-xs text-gray-800">{{ $label }}</button>
                @endforeach
                <button type="button" data-diagram-action="fishbone" class="rounded border border-gray-300 px-3 py-2 text-xs">Pola fishbone</button>
                <button type="button" data-diagram-action="undo" class="rounded border border-gray-300 px-3 py-2 text-xs">Urungkan</button>
                <button type="button" data-diagram-action="redo" class="rounded border border-gray-300 px-3 py-2 text-xs">Ulangi</button>
                <button type="button" data-diagram-action="delete" class="rounded border border-gray-300 px-3 py-2 text-xs text-red-700">Hapus objek</button>
            </div>
            <label class="mt-3 block text-xs text-gray-700">Teks untuk textbox (maks. 200 karakter)
                <input data-diagram-text maxlength="200" type="text" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="Isi teks, pilih Textbox, lalu klik area diagram">
            </label>
            <p data-diagram-status role="status" class="mt-2 text-xs text-gray-600">Diagram disimpan bersama jawaban. Tetap isi penjelasan pada kolom teks.</p>
        @endif
    </div>
    <svg data-diagram-canvas viewBox="0 0 1000 600" role="img" aria-label="Diagram jawaban" class="block w-full bg-white" style="touch-action: none; min-height: 180px">
        @unless($editable)
            @foreach($value as $object)
                @if($object['type'] === 'text')
                    <text x="{{ $object['points'][0][0] }}" y="{{ $object['points'][0][1] }}" fill="#111827" font-size="20" font-family="Arial, sans-serif">{{ $object['text'] }}</text>
                @else
                    <polyline points="{{ collect($object['points'])->map(fn ($point) => implode(',', $point))->implode(' ') }}" fill="none" stroke="#111827" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    @if($object['type'] === 'arrow')
                        @php
                            [$a, $b] = $object['points'];
                            $angle = atan2($b[1] - $a[1], $b[0] - $a[0]);
                        @endphp
                        <polyline points="{{ $b[0] - 16 * cos($angle - .5) }},{{ $b[1] - 16 * sin($angle - .5) }} {{ $b[0] }},{{ $b[1] }} {{ $b[0] - 16 * cos($angle + .5) }},{{ $b[1] - 16 * sin($angle + .5) }}" fill="none" stroke="#111827" stroke-width="2.5" />
                    @endif
                @endif
            @endforeach
        @endunless
    </svg>
</div>
