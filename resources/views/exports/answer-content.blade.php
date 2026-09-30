@php
    $clean = \App\Support\RichTextSanitizer::sanitize($answer);
    // Render saved scene objects without requiring the browser editor.
    $clean = preg_replace_callback('/<span\b[^>]*data-answer-scene=([\'"])(.*?)\1[^>]*>\s*<\/span>/su', function ($match) {
        $objects = json_decode(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true) ?: [];
        return \App\Support\AnswerDiagramExport::html($objects);
    }, $clean);
@endphp
@if(str_contains($answer, '<'))
    {!! $clean !!}
@else
    {!! nl2br(e($answer)) !!}
@endif
