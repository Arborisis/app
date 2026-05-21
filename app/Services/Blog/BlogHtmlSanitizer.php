<?php

declare(strict_types=1);

namespace App\Services\Blog;

use DOMDocument;
use DOMElement;
use DOMNode;

class BlogHtmlSanitizer
{
    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'data-arborisis-type', 'data-arborisis-id'],
    ];

    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'article',
        'blockquote',
        'br',
        'em',
        'h2',
        'h3',
        'hr',
        'li',
        'ol',
        'p',
        'strong',
        'ul',
        'a',
    ];

    public function sanitize(string $html): string
    {
        $document = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            return '';
        }

        $this->sanitizeNode($body);

        $clean = '';
        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);
            if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                $this->unwrapNode($child);
                continue;
            }

            $this->sanitizeAttributes($child, $tagName);
            $this->sanitizeNode($child);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tagName): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! in_array($name, $allowed, true) || ! $this->isSafeAttribute($name, $value)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function isSafeAttribute(string $name, string $value): bool
    {
        if ($name !== 'href') {
            return true;
        }

        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return false;
        }

        return str_starts_with($value, '/')
            || str_starts_with($value, 'https://')
            || str_starts_with($value, 'http://');
    }

    private function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
