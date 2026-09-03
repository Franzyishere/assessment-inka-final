<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'span', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    public static function sanitize(?string $html): string
    {
        if (blank($html)) return '';

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        foreach (iterator_to_array($xpath->query('//script|//style|//iframe|//object|//embed|//link|//meta')) as $dangerous) {
            $dangerous->parentNode?->removeChild($dangerous);
        }

        foreach (array_reverse(iterator_to_array($xpath->query('//*'))) as $element) {
            if (! $element instanceof DOMElement || $element->tagName === 'body') continue;

            if (! in_array(strtolower($element->tagName), self::ALLOWED_TAGS, true)) {
                $parent = $element->parentNode;
                while ($element->firstChild) $parent?->insertBefore($element->firstChild, $element);
                $parent?->removeChild($element);
                continue;
            }

            $originalStyle = $element->getAttribute('style');
            $colspan = $element->getAttribute('colspan');
            $rowspan = $element->getAttribute('rowspan');
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $element->removeAttribute($attribute->name);
            }

            if (in_array($element->tagName, ['p', 'span', 'h1', 'h2', 'h3', 'th', 'td'], true)) {
                $style = self::sanitizeStyle($originalStyle);
                if ($style) $element->setAttribute('style', $style);
            }
            if (in_array($element->tagName, ['th', 'td'], true)) {
                if (ctype_digit($colspan) && (int) $colspan <= 20) $element->setAttribute('colspan', $colspan);
                if (ctype_digit($rowspan) && (int) $rowspan <= 20) $element->setAttribute('rowspan', $rowspan);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body) return '';

        $clean = '';
        foreach ($body->childNodes as $child) $clean .= $document->saveHTML($child);

        return trim($clean);
    }

    private static function sanitizeStyle(string $style): string
    {
        $safe = [];
        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $property = strtolower($property);
            if ($property === 'text-align' && in_array($value, ['left', 'center', 'right', 'justify'], true)) $safe[] = "$property: $value";
            if ($property === 'font-size' && preg_match('/^(12|14|16|18|20|24|28)px$/', $value)) $safe[] = "$property: $value";
            if ($property === 'font-family' && preg_match('/^(Arial|Calibri|Georgia|Times New Roman)(,\s*(sans-serif|serif))?$/i', $value)) $safe[] = "$property: $value";
            if ($property === 'color' && preg_match('/^(#[0-9a-f]{3,8}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/i', $value)) $safe[] = "$property: $value";
        }

        return implode('; ', $safe);
    }
}
