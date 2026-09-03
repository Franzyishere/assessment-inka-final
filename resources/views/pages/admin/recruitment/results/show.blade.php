@extends('layouts.app')

@section('content')
@php
    $center = 320;
    $radius = 220;
    $labelRadius = 258;
    $dimensionCount = count($orderedCodes);
    $point = fn (int $index, float $distance) => [
        $center + cos(deg2rad(-90 + ($index * 360 / $dimensionCount))) * $distance,
        $center + sin(deg2rad(-90 + ($index * 360 / $dimensionCount))) * $distance,
    ];
    $polygon = collect($orderedCodes)->map(function ($code, $index) use ($point, $radius, $scores) {
        [$x, $y] = $point($index, $radius * (($scores[$code] ?? 0) / 9));

        return round($x, 1).','.round($y, 1);
    })->implode(' ');
    $names = [
        'G' => 'Hard Working', 'L' => 'Leadership', 'I' => 'Decision Making', 'T' => 'Pace',
        'V' => 'Vigorous Type', 'S' => 'Social Extension', 'R' => 'Theoretical Type',
        'D' => 'Interest in Detail', 'C' => 'Organized Type', 'E' => 'Emotional Control',
        'N' => 'Need to Finish Task', 'A' => 'Need to Achieve', 'P' => 'Need to Control Others',
        'X' => 'Need to be Noticed', 'B' => 'Need to Belong', 'O' => 'Need for Closeness',
        'Z' => 'Need for Change', 'K' => 'Need to be Forceful', 'F' => 'Support Authority',
        'W' => 'Rules & Supervision',
    ];
    $groups = [
        ['name' => 'WORK DIRECTION', 'codes' => ['N', 'G', 'A'], 'color' => '#991b1b', 'fill' => '#fee2e2'],
        ['name' => 'LEADERSHIP', 'codes' => ['L', 'P', 'I'], 'color' => '#9f1239', 'fill' => '#ffe4e6'],
        ['name' => 'ACTIVITY', 'codes' => ['T', 'V', 'X'], 'color' => '#c2410c', 'fill' => '#ffedd5'],
        ['name' => 'SOCIAL NATURE', 'codes' => ['O', 'B', 'S'], 'color' => '#047857', 'fill' => '#d1fae5'],
        ['name' => 'WORK STYLE', 'codes' => ['C', 'D', 'R'], 'color' => '#1d4ed8', 'fill' => '#dbeafe'],
        ['name' => 'TEMPERAMENT', 'codes' => ['Z', 'E', 'K'], 'color' => '#6d28d9', 'fill' => '#ede9fe'],
        ['name' => 'FOLLOWERSHIP', 'codes' => ['F', 'W'], 'color' => '#374151', 'fill' => '#e5e7eb'],
    ];
    $polarPoint = fn (float $angle, float $distance) => [
        $center + cos(deg2rad($angle)) * $distance,
        $center + sin(deg2rad($angle)) * $distance,
    ];
    $participant = $result->session?->participant;
    $assignment = $result->session?->assignment;
@endphp

<x-common.page-breadcrumb pageTitle="Profil PAPI Peserta" />

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <p class="text-sm text-gray-500">Peserta</p>
        <p class="mt-2 font-semibold text-gray-900">{{ $participant?->user?->name ?? 'Peserta tidak tersedia' }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $participant?->participant_number ?? '-' }}</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <p class="text-sm text-gray-500">Batch</p>
        <p class="mt-2 font-semibold text-gray-900">{{ $assignment?->batch?->name ?? '-' }}</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <p class="text-sm text-gray-500">Total Role</p>
        <p class="mt-2 text-3xl font-semibold text-brand-700">{{ $result->total_role }}</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <p class="text-sm text-gray-500">Total Need</p>
        <p class="mt-2 text-3xl font-semibold text-brand-700">{{ $result->total_need }}</p>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="font-semibold text-gray-900">Profil Melingkar PAPI Kostick</h2>
            <p class="mt-1 text-sm text-gray-500">Profil skor PAPI Kostick pada skala 0–9.</p>
        </div>
        <div class="mx-auto max-w-[700px] overflow-x-auto">
            <svg viewBox="0 0 640 640" class="min-w-[560px]" role="img" aria-label="Diagram profil PAPI Kostick">
                @php $groupStart = 0; @endphp
                @foreach ($groups as $group)
                    @php
                        $groupSize = count($group['codes']);
                        $startAngle = -90 + (($groupStart - 0.5) * 360 / $dimensionCount);
                        $endAngle = -90 + (($groupStart + $groupSize - 0.5) * 360 / $dimensionCount);
                        $middleAngle = ($startAngle + $endAngle) / 2;
                        [$outerStartX, $outerStartY] = $polarPoint($startAngle, 306);
                        [$outerEndX, $outerEndY] = $polarPoint($endAngle, 306);
                        [$innerEndX, $innerEndY] = $polarPoint($endAngle, 276);
                        [$innerStartX, $innerStartY] = $polarPoint($startAngle, 276);
                        [$groupLabelX, $groupLabelY] = $polarPoint($middleAngle, 291);
                        $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;
                        $groupPath = "M {$outerStartX} {$outerStartY} A 306 306 0 {$largeArc} 1 {$outerEndX} {$outerEndY} L {$innerEndX} {$innerEndY} A 276 276 0 {$largeArc} 0 {$innerStartX} {$innerStartY} Z";
                    @endphp
                    <path d="{{ $groupPath }}" fill="{{ $group['fill'] }}" stroke="#ffffff" stroke-width="2" />
                    <text x="{{ $groupLabelX }}" y="{{ $groupLabelY }}" text-anchor="middle" dominant-baseline="middle" font-size="7.5" font-weight="800" fill="{{ $group['color'] }}">{{ $group['name'] }}</text>
                    @php $groupStart += $groupSize; @endphp
                @endforeach

                @for ($ring = 1; $ring <= 9; $ring++)
                    <circle cx="320" cy="320" r="{{ $radius * $ring / 9 }}" fill="none" stroke="{{ $ring === 9 ? '#d1d5db' : '#e5e7eb' }}" stroke-width="1" />
                @endfor

                @foreach ($orderedCodes as $index => $code)
                    @php
                        [$axisX, $axisY] = $point($index, $radius);
                        [$labelX, $labelY] = $point($index, $labelRadius);
                    @endphp
                    <line x1="320" y1="320" x2="{{ $axisX }}" y2="{{ $axisY }}" stroke="#e5e7eb" />
                    <text x="{{ $labelX }}" y="{{ $labelY }}" text-anchor="middle" dominant-baseline="middle" font-size="14" font-weight="700" fill="#374151">{{ $code }}</text>

                    @for ($scale = 1; $scale <= 9; $scale++)
                        @php
                            [$scaleX, $scaleY] = $point($index, $radius * ($scale / 9));
                        @endphp
                        <text
                            x="{{ $scaleX }}"
                            y="{{ $scaleY }}"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            font-size="7.5"
                            font-weight="600"
                            fill="#9ca3af"
                            paint-order="stroke"
                            stroke="#ffffff"
                            stroke-width="2.5"
                        >{{ $scale }}</text>
                    @endfor
                @endforeach

                <circle cx="320" cy="320" r="9" fill="#ffffff" stroke="#e5e7eb" />
                <text x="320" y="320" text-anchor="middle" dominant-baseline="middle" font-size="8" font-weight="700" fill="#6b7280">0</text>

                <polygon points="{{ $polygon }}" fill="rgba(190,18,60,.16)" stroke="#be123c" stroke-width="3" stroke-linejoin="round" />

                @foreach ($orderedCodes as $index => $code)
                    @php
                        [$scoreX, $scoreY] = $point($index, $radius * (($scores[$code] ?? 0) / 9));
                    @endphp
                    <circle cx="{{ $scoreX }}" cy="{{ $scoreY }}" r="4" fill="#be123c">
                        <title>{{ $code }}: {{ $scores[$code] ?? 0 }}</title>
                    </circle>
                @endforeach
            </svg>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold text-gray-900">Skor 20 Dimensi</h2></div>
        <div class="max-h-[680px] divide-y divide-gray-100 overflow-auto">
            @foreach ($orderedCodes as $index => $code)
                <div class="flex items-center gap-3 px-5 py-3">
                    @php
                        $dimensionGroup = collect($groups)->first(fn ($group) => in_array($code, $group['codes'], true));
                    @endphp
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold" style="background-color: {{ $dimensionGroup['fill'] }}; color: {{ $dimensionGroup['color'] }}">{{ $code }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800">{{ $names[$code] }}</p>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-600" style="width: {{ (($scores[$code] ?? 0) / 9) * 100 }}%"></div></div>
                    </div>
                    <span class="text-lg font-semibold text-gray-900">{{ $scores[$code] ?? 0 }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">Dokumen hasil psikotes · Interpretasi profesional oleh psikolog berwenang.</div>
@endsection
