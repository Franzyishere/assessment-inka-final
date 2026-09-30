<?php

namespace App\Support;

class AnswerDiagramExport
{
    /** Render in bounded strips so very tall answer sheets cannot exhaust memory. */
    public static function html(array $objects): string
    {
        $objects = AnswerDiagram::parse(json_encode($objects, JSON_UNESCAPED_UNICODE));
        if (! $objects) return '';
        if (! extension_loaded('gd')) throw new \RuntimeException('Ekspor diagram memerlukan ekstensi PHP GD.');
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $width = 1000;
        $height = 120;
        $labels = [];
        foreach ($objects as &$object) {
            if ($object['type'] === 'text') {
                $object['width'] ??= 220;
                $limit = max(5, (int) floor(($object['width'] - 20) / 11));
                $lines = [];
                foreach (explode("\n", $object['text'] ?? '') as $paragraph) {
                    array_push($lines, ...(mb_str_split($paragraph, $limit) ?: ['']));
                }
                $object['lines'] = $lines;
                $object['height'] = max($object['height'] ?? 90, count($lines) * 23 + 20);
                if (trim($object['text'] ?? '') !== '') $labels[] = $object['text'];
            }
            foreach ($object['points'] as [$x, $y]) {
                $width = max($width, $x + ($object['type'] === 'text' ? $object['width'] : 0) + 10);
                $height = max($height, $y + ($object['type'] === 'text' ? $object['height'] : 0) + 30);
            }
        }
        unset($object);
        $html = '';
        for ($top = 0; $top < $height; $top += 1100) {
            $stripHeight = (int) min(1100, $height - $top);
            $image = imagecreatetruecolor((int) $width, $stripHeight);
            $white = imagecolorallocate($image, 255, 255, 255);
            $ink = imagecolorallocate($image, 17, 24, 39);
            imagefill($image, 0, 0, $white);
            imagesetthickness($image, 2);
            imageantialias($image, true);
            foreach ($objects as $object) {
                if ($object['type'] === 'text') {
                    [$x, $y] = $object['points'][0];
                    $y -= $top;
                    if ($y > $stripHeight || $y + $object['height'] < 0) continue;
                    imagefilledrectangle($image, (int) $x, (int) $y, (int) ($x + $object['width']), (int) ($y + $object['height']), $white);
                    imagerectangle($image, (int) $x, (int) $y, (int) ($x + $object['width']), (int) ($y + $object['height']), $ink);
                    foreach ($object['lines'] as $index => $line) {
                        $baseline = (int) ($y + 28 + $index * 23);
                        if ($baseline >= -23 && $baseline <= $stripHeight + 23) {
                            imagettftext($image, 13.5, 0, (int) ($x + 10), $baseline, $ink, $font, $line);
                        }
                    }
                    continue;
                }
                $points = $object['points'];
                if (count($points) === 1) {
                    imagefilledellipse($image, (int) $points[0][0], (int) ($points[0][1] - $top), 3, 3, $ink);
                }
                for ($i = 1; $i < count($points); $i++) {
                    imageline($image, (int) $points[$i - 1][0], (int) ($points[$i - 1][1] - $top), (int) $points[$i][0], (int) ($points[$i][1] - $top), $ink);
                }
                if ($object['type'] === 'arrow') {
                    [$a, $b] = $points;
                    $angle = atan2($b[1] - $a[1], $b[0] - $a[0]);
                    foreach ([-.5, .5] as $offset) {
                        imageline($image, (int) $b[0], (int) ($b[1] - $top), (int) ($b[0] - 16 * cos($angle + $offset)), (int) ($b[1] - $top - 16 * sin($angle + $offset)), $ink);
                    }
                }
            }
            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            unset($image);
            $displayHeight = round($stripHeight * 640 / $width);
            $html .= '<div style="page-break-inside:avoid"><img alt="'.e(implode(' | ', $labels)).'" width="640" height="'.$displayHeight.'" style="width:100%;height:auto" src="data:image/png;base64,'.base64_encode($png).'"></div>';
        }
        return $html;
    }

    /** Word reads MHTML image parts offline; raw HTML data URLs are unreliable. */
    public static function wordDocument(string $html): string
    {
        $images = [];
        $html = preg_replace_callback('/src="data:image\/png;base64,([A-Za-z0-9+\/=]+)"/', function ($match) use (&$images) {
            $name = 'diagram-'.count($images).'.png';
            $images[$name] = $match[1];
            return 'src="'.$name.'"';
        }, $html);
        if (! $images) return $html;
        $boundary = 'assessment-'.bin2hex(random_bytes(16));
        $document = "MIME-Version: 1.0\r\nContent-Type: multipart/related; boundary=\"{$boundary}\"; type=\"text/html\"\r\n\r\n";
        $document .= "--{$boundary}\r\nContent-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\nContent-Location: answer.html\r\n\r\n".quoted_printable_encode($html)."\r\n";
        foreach ($images as $name => $image) {
            $document .= "--{$boundary}\r\nContent-Type: image/png\r\nContent-Transfer-Encoding: base64\r\nContent-Location: {$name}\r\n\r\n".chunk_split($image, 76, "\r\n");
        }
        return $document."--{$boundary}--\r\n";
    }
}
