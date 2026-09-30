<?php

use App\Support\AnswerDiagram;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;

test('diagram supports all tools and renders text safely without javascript', function () {
    $objects = [
        ['type' => 'line', 'points' => [[1, 2], [40, 50]]],
        ['type' => 'arrow', 'points' => [[1, 2], [80, 90]]],
        ['type' => 'pen', 'points' => [[1, 2], [3, 4], [5, 6]]],
        ['type' => 'text', 'points' => [[100, 200]], 'text' => '<script>alert(1)</script>'],
    ];
    expect(AnswerDiagram::parse(json_encode($objects)))->toBe($objects);
    $html = Blade::render('<x-common.answer-diagram :value="$objects" />', compact('objects'));
    expect($html)->toContain('<polyline', '&lt;script&gt;')->not->toContain('<script>', 'data-answer-diagram', 'data-diagram-tool');
});

test('diagram rejects excessive objects malformed points and unsupported attributes', function () {
    $line = ['type' => 'line', 'points' => [[1, 2], [3, 4]]];
    foreach ([array_fill(0, 1501, $line), [$line + ['onclick' => 'alert(1)']], [['type' => 'text', 'points' => [[1, 2], [3, 4]], 'text' => '']], [['type' => 'pen', 'points' => ['bad' => [1, 2]]]]] as $objects) {
        expect(fn () => AnswerDiagram::parse(json_encode($objects)))->toThrow(ValidationException::class);
    }
});

test('embedded document shapes survive sanitizing without unsafe attributes', function () {
    $scene = [['type' => 'text', 'points' => [[100, 100]], 'width' => 200, 'height' => 80, 'text' => '<script>unsafe</script>']];
    $html = '<p>Analisis</p><span onclick="alert(1)" data-answer-scene="'.htmlspecialchars(json_encode($scene), ENT_QUOTES).'"><script>alert(1)</script></span><p>Simpulan</p>';
    $clean = RichTextSanitizer::sanitize($html);
    expect($clean)->toContain('data-answer-scene=', 'Analisis', 'Simpulan')->not->toContain('onclick', '<script>');
    expect(RichTextSanitizer::hasAnswer($clean))->toBeTrue();
    expect(RichTextSanitizer::hasAnswer('<span data-answer-scene="[]"></span>'))->toBeFalse();
    expect(RichTextSanitizer::sanitize($clean))->toBe($clean);
});

test('empty fishbone labels and long textbox content are accepted', function () {
    $scene = [
        ['type' => 'text', 'points' => [[10, 1500]], 'width' => 200, 'height' => 100, 'text' => ''],
        ['type' => 'text', 'points' => [[230, 1500]], 'width' => 200, 'height' => 100, 'text' => str_repeat('penyebab ', 100)],
    ];
    expect(AnswerDiagram::parse(json_encode($scene)))->toBe($scene);
    $html = '<span data-answer-layer="true" data-answer-scene="'.htmlspecialchars(json_encode($scene), ENT_QUOTES).'"></span>';
    expect(RichTextSanitizer::sanitize($html))->toContain('data-answer-layer="true"');
});

test('fishbone groups survive sanitizing and reject invalid identifiers', function () {
    $scene = [['type' => 'line', 'points' => [[1, 2], [3, 4]], 'group' => 'fishbone-abc-123']];
    expect(AnswerDiagram::parse(json_encode($scene)))->toBe($scene);
    $html = '<span data-answer-layer="true" data-answer-scene="'.htmlspecialchars(json_encode($scene), ENT_QUOTES).'"></span>';
    expect(RichTextSanitizer::sanitize($html))->toContain('fishbone-abc-123');
    $scene[0]['group'] = '<script>';
    expect(fn () => AnswerDiagram::parse(json_encode($scene)))->toThrow(ValidationException::class);
});

test('document table spacing survives sanitization without arbitrary styles', function () {
    $clean = RichTextSanitizer::sanitize('<p data-answer-space="500" style="position:fixed" onclick="bad()"></p>');
    expect($clean)->toContain('data-answer-space="500"', '--answer-unit')->not->toContain('position:fixed', 'onclick');
    expect(RichTextSanitizer::sanitize('<p data-answer-space="999999"></p>'))->not->toContain('data-answer-space');
});

test('in flow diagram remains between text blocks after sanitizing', function () {
    $scene = [['type' => 'line', 'points' => [[10, 20], [100, 20]]]];
    $html = '<p>Sebelum</p><span data-answer-flow="true" data-answer-scene="'.htmlspecialchars(json_encode($scene), ENT_QUOTES).'"></span><p>Sesudah</p>';
    $clean = RichTextSanitizer::sanitize($html);
    expect($clean)->toContain('data-answer-flow="true"')->not->toContain('data-answer-layer');
    expect(strpos($clean, 'Sebelum'))->toBeLessThan(strpos($clean, 'data-answer-scene'));
    expect(strpos($clean, 'Sesudah'))->toBeGreaterThan(strpos($clean, 'data-answer-scene'));
});
