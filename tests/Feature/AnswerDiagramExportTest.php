<?php

use App\Support\AnswerDiagramExport;

test('diagram export paints lines arrows pen and textboxes rather than only their text', function () {
    $html = AnswerDiagramExport::html([
        ['type' => 'line', 'points' => [[50, 50], [250, 50]], 'group' => 'fishbone-test'],
        ['type' => 'arrow', 'points' => [[50, 100], [250, 100]], 'group' => 'fishbone-test'],
        ['type' => 'pen', 'points' => [[50, 150], [100, 180], [150, 150]]],
        ['type' => 'text', 'points' => [[300, 50]], 'width' => 190, 'height' => 70, 'text' => 'Penyebab'],
    ]);
    preg_match('/base64,([^"]+)/', $html, $match);
    $image = imagecreatefromstring(base64_decode($match[1]));
    foreach ([[100, 50], [100, 100], [100, 180], [300, 70], [236, 92]] as [$x, $y]) {
        expect(imagecolorat($image, $x, $y) & 0xffffff)->not->toBe(0xffffff);
    }
    expect(imagecolorat($image, 10, 10) & 0xffffff)->toBe(0xffffff);
    expect($html)->toContain('Penyebab')->not->toContain('Isi diagram / textbox');
    $word = AnswerDiagramExport::wordDocument($html);
    expect($word)->toContain('multipart/related', 'Content-Type: image/png', 'Content-Location: diagram-0.png')
        ->not->toContain('data:image/png');
    preg_match('/Content-Location: diagram-0.png\r\n\r\n(.*?)\r\n--/s', $word, $part);
    expect(base64_decode($part[1]))->toBe(base64_decode($match[1]));
});

test('tall diagrams are split without dropping content below the first page', function () {
    $html = AnswerDiagramExport::html([
        ['type' => 'line', 'points' => [[50, 2100], [250, 2100]]],
        ['type' => 'text', 'points' => [[50, 2200]], 'text' => str_repeat('Isi panjang ', 60)],
    ]);
    expect(substr_count($html, 'data:image/png'))->toBeGreaterThan(2);
    expect($html)->toContain('Isi panjang');
});
