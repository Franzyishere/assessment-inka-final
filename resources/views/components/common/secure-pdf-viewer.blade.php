@props([
    'url',
    'watermark' => null,
    'highlightsUrl' => null,
    'highlightsKey' => null,
    'height' => 'h-[52vh] min-h-[460px] max-h-[580px]',
])

@php
    $defaultWatermark = 'PT INKA (PERSERO) HUMAN CAPITAL';
    $finalWatermark = $watermark ?: $defaultWatermark;
@endphp

<div data-secure-pdf-viewer
     data-pdf-url="{{ $url }}"
     data-highlights-url="{{ $highlightsUrl }}"
     data-highlights-key="{{ $highlightsKey }}"
     data-watermark="{{ $finalWatermark }}"
     class="w-full {{ $height }} overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800 select-none"
     oncontextmenu="return false;"
     ondragstart="return false;">
    <noscript>
        <div class="p-6 text-center text-sm text-gray-500">
            Aktifkan JavaScript pada browser Anda untuk melihat materi assessment ini.
        </div>
    </noscript>
</div>
