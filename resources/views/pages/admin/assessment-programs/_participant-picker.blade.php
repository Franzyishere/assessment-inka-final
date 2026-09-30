@php
    $pickerRows = $participants->map(fn ($participant) => [
        'id' => (int) $participant->id,
        'search' => Str::lower($participant->name.' '.$participant->email),
        'category' => old("participant_categories.{$participant->id}", $selectedParticipantCategories[$participant->id] ?? ''),
        'material' => old("participant_simulation_three_choices.{$participant->id}", $selectedParticipantChoices[$participant->id] ?? ''),
    ])->values();
@endphp

<div x-data="{
    rows: @js($pickerRows),
    categories: @js($assessmentCategories),
    materials: @js($simulationThreeOptions),
    filter: 'all',
    page: 1,
    pageSize: 10,
    openedId: null,
    init() {
        this.openedId = this.rows.find(item => this.selectedParticipants.includes(item.id) && this.incomplete(item.id))?.id ?? null;
        this.$watch('participantSearch', () => this.page = 1);
        this.$watch('filter', () => this.page = 1);
    },
    row(id) { return this.rows.find(item => item.id === id); },
    incomplete(id) { const item = this.row(id); return !item.category || !item.material; },
    get incompleteCount() { return this.rows.filter(item => this.selectedParticipants.includes(item.id) && this.incomplete(item.id)).length; },
    get filtered() {
        return this.rows.filter(item => this.participantMatches(item.search)
            && (this.filter === 'all' || this.selectedParticipants.includes(item.id))
            && (this.filter !== 'incomplete' || this.incomplete(item.id)));
    },
    get pageCount() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); },
    get currentPage() { return Math.min(this.page, this.pageCount); },
    get visibleIds() { return this.filtered.slice((this.currentPage - 1) * this.pageSize, this.currentPage * this.pageSize).map(item => item.id); },
    expanded(id) { return this.selectedParticipants.includes(id) && this.openedId === id; },
    participantChecked(id, checked) {
        if (checked) this.openedId = id;
        else if (this.openedId === id) this.openedId = null;
    },
    summary(id) {
        const item = this.row(id);
        return [this.categories[item.category], this.materials[item.material]].filter(Boolean).join(' · ') || 'Pengaturan belum lengkap';
    }
}" class="participant-picker">
    <div class="mb-4 flex flex-wrap gap-2" aria-label="Filter peserta">
        <button type="button" class="participant-filter" :aria-pressed="filter === 'all'" @click="filter = 'all'">Semua <span>({{ $participants->count() }})</span></button>
        <button type="button" class="participant-filter" :aria-pressed="filter === 'selected'" @click="filter = 'selected'">Dipilih (<span x-text="selectedParticipants.length"></span>)</button>
        <button type="button" class="participant-filter" :aria-pressed="filter === 'incomplete'" @click="filter = 'incomplete'">Belum lengkap (<span x-text="incompleteCount"></span>)</button>
    </div>

    <div class="space-y-2">
        @foreach ($participants as $participant)
            @php $participantId = (int) $participant->id; @endphp
            <div x-show="visibleIds.includes({{ $participantId }})" x-cloak
                class="participant-picker-row rounded-xl border border-gray-200 p-3 sm:p-4"
                @click="if (selectedParticipants.includes({{ $participantId }}) && !$event.target.closest('button, input, select, a, label')) openedId = {{ $participantId }}"
                :class="selectedParticipants.includes({{ $participantId }}) ? 'cursor-pointer' : ''"
                :data-selected="selectedParticipants.includes({{ $participantId }}) ? 'true' : 'false'">
                <div class="flex items-start gap-3">
                    <input id="participant-{{ $participantId }}" type="checkbox" name="participant_ids[]" value="{{ $participantId }}"
                        aria-labelledby="participant-name-{{ $participantId }}"
                        x-model.number="selectedParticipants" @change="participantChecked({{ $participantId }}, $event.target.checked)"
                        class="mt-1 size-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <div class="min-w-0 flex-1">
                        <button type="button" class="block w-full text-left disabled:cursor-default"
                            :disabled="!selectedParticipants.includes({{ $participantId }})"
                            @click="openedId = {{ $participantId }}"
                            :aria-expanded="expanded({{ $participantId }})" aria-controls="participant-options-{{ $participantId }}">
                            <span id="participant-name-{{ $participantId }}" class="block text-sm font-semibold text-gray-800">{{ $participant->name }}</span>
                            <span class="block break-all text-xs text-gray-500">{{ $participant->email }}</span>
                        </button>
                        <p x-show="selectedParticipants.includes({{ $participantId }})" class="mt-2 text-xs leading-5 text-gray-600" x-text="summary({{ $participantId }})"></p>
                    </div>
                    <button type="button" x-show="selectedParticipants.includes({{ $participantId }})"
                        @click="openedId = expanded({{ $participantId }}) ? null : {{ $participantId }}"
                        :aria-expanded="expanded({{ $participantId }})" aria-controls="participant-options-{{ $participantId }}"
                        class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium text-gray-700 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500">
                        <span x-text="expanded({{ $participantId }}) ? 'Tutup' : 'Atur'"></span>
                        <span aria-hidden="true" x-text="expanded({{ $participantId }}) ? '−' : '+'"></span>
                    </button>
                </div>
                <div id="participant-options-{{ $participantId }}" class="participant-options" data-expanded="false"
                    :data-expanded="expanded({{ $participantId }}) ? 'true' : 'false'" :inert="!expanded({{ $participantId }})">
                    <div class="participant-options-inner">
                        <div class="mt-4 grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2">
                            <div>
                                <label for="participant-category-{{ $participantId }}" class="mb-1 block text-xs font-medium text-gray-700">Tujuan Assessment</label>
                                <select id="participant-category-{{ $participantId }}" name="participant_categories[{{ $participantId }}]"
                                    x-model="row({{ $participantId }}).category" :disabled="!selectedParticipants.includes({{ $participantId }})"
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700">
                                    <option value="">Pilih tujuan assessment</option>
                                    @foreach($assessmentCategories as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error("participant_categories.{$participantId}")<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="simulation-choice-{{ $participantId }}" class="mb-1 block text-xs font-medium text-gray-700">Materi Simulasi 3</label>
                                <select id="simulation-choice-{{ $participantId }}" name="participant_simulation_three_choices[{{ $participantId }}]"
                                    x-model="row({{ $participantId }}).material" :disabled="!selectedParticipants.includes({{ $participantId }})"
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700">
                                    <option value="">Pilih materi</option>
                                    @foreach($simulationThreeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error("participant_simulation_three_choices.{$participantId}")<p class="mt-1 text-xs text-error-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <p x-show="filtered.length === 0" x-cloak class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">Tidak ada peserta yang sesuai dengan pencarian atau filter.</p>
    </div>
    <div x-show="filtered.length > 0" x-cloak class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
        <p><span x-text="filtered.length"></span> peserta · Halaman <span x-text="currentPage"></span> dari <span x-text="pageCount"></span></p>
        <div x-show="pageCount > 1" class="flex gap-2">
            <button type="button" class="participant-filter" :disabled="currentPage === 1" @click="page = currentPage - 1">Sebelumnya</button>
            <button type="button" class="participant-filter" :disabled="currentPage === pageCount" @click="page = currentPage + 1">Berikutnya</button>
        </div>
    </div>
</div>
