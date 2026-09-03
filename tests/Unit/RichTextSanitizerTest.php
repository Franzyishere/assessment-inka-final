<?php

use App\Support\RichTextSanitizer;

test('assessment rich text keeps supported formatting and removes executable content', function () {
    $result = RichTextSanitizer::sanitize('<p style="text-align: center; font-size: 18px; position: fixed" onclick="alert(1)"><strong>Jawaban</strong><script>alert(1)</script></p>');

    expect($result)
        ->toContain('<p style="text-align: center; font-size: 18px"><strong>Jawaban</strong></p>')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('position');
});

test('assessment rich text permits tables but removes unsafe attributes', function () {
    $result = RichTextSanitizer::sanitize('<table><tbody><tr><td colspan="2" onmouseover="alert(1)">Data</td></tr></tbody></table>');

    expect($result)->toContain('<table>')->toContain('<td colspan="2">Data</td>')->not->toContain('onmouseover');
});
