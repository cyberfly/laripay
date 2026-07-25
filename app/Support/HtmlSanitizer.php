<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'b' => [],
        'strong' => [],
        'i' => [],
        'em' => [],
        'u' => [],
        'del' => [],
        'blockquote' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'a' => ['href', 'target', 'rel'],
    ];

    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><body>'.$html.'</body>',
            LIBXML_NOBLANKS,
        );
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        self::cleanNode($document, $body);

        $output = '';

        foreach (iterator_to_array($body->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanNode(DOMDocument $document, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                    self::unwrap($document, $child);

                    continue;
                }

                self::stripDisallowedAttributes($child, self::ALLOWED_TAGS[$tag]);

                if ($tag === 'a') {
                    self::sanitizeAnchor($child);
                }

                self::cleanNode($document, $child);
            }
        }
    }

    /**
     * @param  array<int, string>  $allowedAttributes
     */
    private static function stripDisallowedAttributes(DOMElement $element, array $allowedAttributes): void
    {
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            if (! in_array(strtolower($attribute->nodeName), $allowedAttributes, true)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }

    private static function sanitizeAnchor(DOMElement $anchor): void
    {
        $href = $anchor->getAttribute('href');

        if ($href !== '' && ! preg_match('/^(https?:|mailto:|\/)/i', $href)) {
            $anchor->removeAttribute('href');
        }

        $anchor->setAttribute('rel', 'noopener noreferrer');
    }

    private static function unwrap(DOMDocument $document, DOMElement $element): void
    {
        while ($element->firstChild) {
            $element->parentNode?->insertBefore($element->firstChild, $element);
        }

        $element->parentNode?->removeChild($element);
    }
}
