@extends('layouts.app')
@section('content')
<div x-data="papiExam()" x-init="init()" class="space-y-5">
    <div class="sticky top-20 z-20 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-sm backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-sm font-semibold text-gray-900">PAPI Kostick</p><p class="text-xs text-gray-500"><span x-text="answeredCount"></span>/90 terjawab · jawaban tersimpan otomatis</p></div>
        <div class="rounded-xl bg-error-50 px-4 py-2 text-center"><p class="text-xs font-medium text-error-600">Sisa waktu</p><p class="font-mono text-xl font-bold text-error-700" x-text="timer"></p></div>
    </div>
    <div class="grid gap-5 xl:grid-cols-[1fr_300px]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-8">
            <div class="mb-6 flex items-center justify-between"><span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700">Soal <span x-text="current + 1"></span> dari 90</span><span class="text-sm" :class="saving ? 'text-warning-600' : 'text-success-600'" x-text="saveStatus"></span></div>
            @foreach($questions as $index => $question)
                <div x-show="current === {{ $index }}" x-cloak>
                    <div class="space-y-4">
                        @foreach($question->options->sortBy('display_order') as $option)
                            <label class="flex cursor-pointer gap-4 rounded-2xl border p-5 transition" :class="answers[{{ $question->id }}] === '{{ $option->code }}' ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/10' : 'border-gray-200 hover:border-brand-300'">
                                <input type="radio" class="mt-1 size-5" name="question_{{ $question->id }}" value="{{ $option->code }}" :checked="answers[{{ $question->id }}] === '{{ $option->code }}'" @change="saveAnswer({{ $question->id }}, '{{ $option->code }}')">
                                <span><span class="mb-1 block text-xs font-bold text-gray-500">PILIHAN {{ $option->code }}</span><span class="text-base leading-7 text-gray-800">{{ $option->statement }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="mt-8 flex justify-between"><button type="button" class="crud-btn-secondary" @click="current = Math.max(0, current - 1)" :disabled="current === 0">Sebelumnya</button><button type="button" class="crud-btn-primary" @click="current = Math.min(89, current + 1)" x-show="current < 89">Berikutnya</button></div>
        </div>
        <aside class="rounded-2xl border border-gray-200 bg-white p-5 xl:sticky xl:top-44 xl:self-start"><h2 class="font-semibold text-gray-900">Navigasi Soal</h2><div class="mt-4 grid grid-cols-10 gap-1.5 xl:grid-cols-5">@foreach($questions as $index => $question)<button type="button" @click="current={{ $index }}" class="aspect-square rounded-lg text-xs font-semibold transition" :class="current === {{ $index }} ? 'bg-brand-600 text-white' : (answers[{{ $question->id }}] ? 'bg-success-100 text-success-700' : 'bg-gray-100 text-gray-500')">{{ $question->number }}</button>@endforeach</div>
            <form x-ref="submitForm" method="POST" action="{{ route('peserta-rekrutmen.exams.submit', $session) }}" class="mt-6">@csrf<button type="button" @click="$dispatch('confirm-dialog',{title:'Kumpulkan jawaban?',message:'Jawaban tidak dapat diubah setelah dikumpulkan.',confirmLabel:'Ya, Kumpulkan',onConfirm:()=>$refs.submitForm.requestSubmit()})" class="crud-btn-primary w-full justify-center" :disabled="answeredCount < 90 || saving">Simpan dan Kumpulkan</button><p x-show="answeredCount < 90" class="mt-2 text-center text-xs text-error-600">Selesaikan seluruh 90 soal untuk mengumpulkan.</p></form>
        </aside>
    </div>
</div>
<script>
function papiExam() { return { current: {{ max(0, min(89, ($session->last_question_number ?? 1) - 1)) }}, answers: @js($answers), answeredCount: {{ $answers->filter()->count() }}, saving: false, saveStatus: 'Tersimpan', timer: '--:--', expiresAt: new Date(@js($session->expires_at->toIso8601String())).getTime(), init() { this.tick(); setInterval(() => this.tick(), 1000); }, tick() { const left=Math.max(0,Math.floor((this.expiresAt-Date.now())/1000)); this.timer=String(Math.floor(left/60)).padStart(2,'0')+':'+String(left%60).padStart(2,'0'); if(left===0) window.location.reload(); }, async saveAnswer(questionId, choice) { this.saving=true; this.saveStatus='Menyimpan...'; try { const response=await fetch(@js(route('peserta-rekrutmen.exams.answers.store',$session)), {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({question_id:questionId,choice})}); const data=await response.json(); if(!response.ok) throw new Error(data.message || 'Jawaban gagal disimpan.'); this.answers[questionId]=choice; this.answeredCount=data.answered_count; this.saveStatus='Tersimpan'; } catch(error) { this.saveStatus='Gagal tersimpan'; window.dispatchEvent(new CustomEvent('notify',{detail:{type:'error',message:error.message}})); } finally { this.saving=false; } } } }
</script>
@endsection
