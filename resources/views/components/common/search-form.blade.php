@props(['action', 'placeholder' => 'Cari data...', 'label' => 'Pencarian'])

<form data-live-search method="GET" action="{{ $action }}" class="flex w-full items-center gap-2 sm:max-w-md">
    @foreach(request()->except(['search', 'page']) as $key => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <label class="relative min-w-0 flex-1">
        <span class="sr-only">{{ $label }}</span>
        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}" class="h-9 w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10">
    </label>
    <button class="crud-btn-secondary" type="submit">Cari</button>
    @if(request()->filled('search'))<a href="{{ $action }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600">Reset</a>@endif
</form>
