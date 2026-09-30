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
            $space = $element->tagName === 'p' ? $element->getAttribute('data-answer-space') : '';
            $scene = $element->tagName === 'span' && $element->hasAttribute('data-answer-scene')
                ? AnswerDiagram::parse($element->getAttribute('data-answer-scene')) : null;
            $isLayer = $element->getAttribute('data-answer-layer') === 'true';
            $isFlow = $element->getAttribute('data-answer-flow') === 'true';
            $colspan = $element->getAttribute('colspan');
            $rowspan = $element->getAttribute('rowspan');
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $element->removeAttribute($attribute->name);
            }
            if ($scene !== null) {
                $element->setAttribute('data-answer-scene', json_encode($scene, JSON_UNESCAPED_UNICODE));
                if ($isLayer) $element->setAttribute('data-answer-layer', 'true');
                if ($isFlow) $element->setAttribute('data-answer-flow', 'true');
                while ($element->firstChild) $element->removeChild($element->firstChild);
            }

            if (in_array($element->tagName, ['p', 'span', 'h1', 'h2', 'h3', 'th', 'td'], true)) {
                $style = self::sanitizeStyle($originalStyle);
                if ($style) $element->setAttribute('style', $style);
            }
            if (in_array($element->tagName, ['th', 'td'], true)) {
                if (ctype_digit($colspan) && (int) $colspan <= 20) $element->setAttribute('colspan', $colspan);
                if (ctype_digit($rowspan) && (int) $rowspan <= 20) $element->setAttribute('rowspan', $rowspan);
            }
            if (ctype_digit($space) && (int) $space <= 20000) {
                $element->setAttribute('data-answer-space', $space);
                $element->setAttribute('style', 'height: calc(var(--answer-unit, 1px) * '.$space.'); margin: 0;');
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body) return '';

        $clean = '';
        foreach ($body->childNodes as $child) $clean .= $document->saveHTML($child);

        return trim($clean);
    }

    public static function hasAnswer(?string $html): bool
    {
        if (trim(html_entity_decode(strip_tags($html ?? ''))) !== '') return true;
        return preg_match('/data-answer-scene=["\'](?!\[\])/', $html ?? '') === 1;
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
