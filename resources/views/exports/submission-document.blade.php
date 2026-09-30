<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Jawaban Peserta</title>
    <style>
        @page { margin: 24mm 18mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #111; line-height: 1.5; }
        h1 { font-size: 18pt; } h2 { font-size: 13pt; margin-top: 24px; }
        .identity { margin-bottom: 24px; } .answer { overflow-wrap: break-word; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #777; padding: 6px; vertical-align: top; }
        p { margin: 0 0 10px; } pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Lembar Jawaban Peserta</h1>
    <div class="identity">
        <p><strong>Nama:</strong> {{ $session->participant->user->name }}</p>
        <p><strong>Program:</strong> {{ $session->programSimulation->program->name }}</p>
        <p><strong>Simulasi:</strong> {{ $simulationName }}</p>
        <p><strong>Dikumpulkan:</strong> {{ $session->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
    </div>

    @if($submission->storage_path)
        <h2>Berkas Presentasi</h2>
        <p>{{ $submission->original_filename }}</p>
        <p>Jawaban berupa berkas unggahan. Dokumen ini merupakan ringkasan identitas berkas, bukan konversi isinya. Unduh berkas asli melalui halaman Lembar Jawaban Peserta.</p>
    @else
        @php
            // Read saved answers, not only current material pages: materials may have changed.
            $decoded = json_decode($submission->response_text ?? '', true);
            $answers = is_array($decoded) ? $decoded : [1 => $submission->response_text ?? ''];
        @endphp
        @forelse($answers as $number => $answer)
            @if(is_string($answer))
                <h2>Jawaban {{ $number }}</h2>
                <div class="answer">
                    @include('exports.answer-content', ['answer' => $answer])
                    @if(!empty($submission->diagrams[$number]))
                        {!! \App\Support\AnswerDiagramExport::html($submission->diagrams[$number]) !!}
                    @endif
                </div>
            @endif
        @empty
            <p>Tidak ada jawaban tertulis yang tersimpan.</p>
        @endforelse
    @endif
</body>
</html>
