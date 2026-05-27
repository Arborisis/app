<?php

declare(strict_types=1);

namespace App\Services\Mail;

/**
 * Service de sanitization HTML pour les emails et contenus riches.
 *
 * Utilise une liste blanche de balises et attributs autorisés
 * pour prévenir les attaques XSS via injection de HTML malveillant.
 */
class HtmlSanitizerService
{
    /**
     * Balises HTML autorisées pour les newsletters.
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'div', 'span', 'img', 'hr',
    ];

    /**
     * Attributs autorisés par balise.
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'div' => ['class', 'style'],
        'span' => ['class', 'style'],
        'p' => ['class', 'style'],
        'table' => ['class', 'style', 'border', 'cellpadding', 'cellspacing', 'width'],
        'td' => ['class', 'style', 'colspan', 'rowspan', 'width', 'valign', 'align'],
        'th' => ['class', 'style', 'colspan', 'rowspan', 'width', 'valign', 'align'],
    ];

    /**
     * Protocoles d'URL autorisés.
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto', 'tel'];

    /**
     * Sanitize du HTML pour les emails newsletter.
     *
     * @param string $html Le HTML brut à nettoyer
     * @return string Le HTML sanitizé
     */
    public function sanitizeForEmail(string $html): string
    {
        // Étape 1: Nettoyer avec strip_tags en whitelist
        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $cleaned = strip_tags($html, $allowed);

        // Étape 2: Parser et filtrer les attributs avec DOMDocument
        return $this->sanitizeAttributes($cleaned);
    }

    /**
     * Sanitize du HTML simple (pour les réponses de tickets, etc.)
     *
     * @param string $html Le HTML brut
     * @return string Le HTML sanitizé
     */
    public function sanitizeSimple(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><a><ul><ol><li><blockquote><span>';
        $cleaned = strip_tags($html, $allowed);

        return $this->sanitizeAttributes($cleaned);
    }

    /**
     * Sanitize les attributs des balises HTML.
     */
    private function sanitizeAttributes(string $html): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // Supprimer les erreurs de parsing HTML5
        $internalErrors = libxml_use_internal_errors(true);

        // Charger avec encodage UTF-8 explicite
        $wrapped = '<?xml encoding="UTF-8"?><div id="sanitizer-root">' . $html . '</div>';
        $dom->loadHTML($wrapped, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);

        libxml_use_internal_errors($internalErrors);

        $xpath = new \DOMXPath($dom);
        $allNodes = $xpath->query('//*');

        foreach ($allNodes as $node) {
            /** @var \DOMElement $node */
            $tagName = strtolower($node->nodeName);

            // Ignorer le wrapper
            if ($tagName === 'div' && $node->getAttribute('id') === 'sanitizer-root') {
                continue;
            }

            // Vérifier si la balise est autorisée
            if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                // Remplacer par son contenu textuel
                $text = $dom->createTextNode($node->textContent);
                $node->parentNode->replaceChild($text, $node);
                continue;
            }

            // Filtrer les attributs
            $allowedAttrs = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];
            $attrsToRemove = [];

            foreach ($node->attributes as $attr) {
                $attrName = strtolower($attr->nodeName);

                if (! in_array($attrName, $allowedAttrs, true)) {
                    $attrsToRemove[] = $attrName;
                    continue;
                }

                // Sanitization spécifique pour href/src
                if ($attrName === 'href' || $attrName === 'src') {
                    $sanitizedUrl = $this->sanitizeUrl($attr->nodeValue);
                    if ($sanitizedUrl === null) {
                        $attrsToRemove[] = $attrName;
                    } else {
                        $node->setAttribute($attrName, $sanitizedUrl);
                    }
                }

                // Sanitization pour style (CSS inline)
                if ($attrName === 'style') {
                    $sanitizedStyle = $this->sanitizeStyle($attr->nodeValue);
                    $node->setAttribute('style', $sanitizedStyle);
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }

            // Bloquer les event handlers (onclick, onload, etc.)
            foreach ($node->attributes as $attr) {
                if (str_starts_with(strtolower($attr->nodeName), 'on')) {
                    $node->removeAttribute($attr->nodeName);
                }
            }
        }

        // Extraire le contenu du wrapper
        $root = $dom->getElementById('sanitizer-root');
        if ($root === null) {
            return $html;
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    /**
     * Sanitize une URL.
     */
    private function sanitizeUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        // URLs relatives autorisées
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        // Vérifier le protocole
        $parsed = parse_url($url);
        if ($parsed === false) {
            return null;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');

        if ($scheme === '') {
            // Pas de scheme = URL relative ou invalide
            return str_starts_with($url, './') || str_starts_with($url, '../')
                ? $url
                : null;
        }

        if (! in_array($scheme, self::ALLOWED_PROTOCOLS, true)) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize du CSS inline.
     */
    private function sanitizeStyle(string $style): string
    {
        // Propriétés CSS dangereuses à bloquer
        $dangerous = [
            'expression',
            'javascript:',
            'behavior',
            '-moz-binding',
            '@import',
            'position:\s*fixed',
            'position:\s*absolute',
        ];

        $cleaned = $style;
        foreach ($dangerous as $pattern) {
            $cleaned = preg_replace('/' . $pattern . '/i', 'invalid', $cleaned);
        }

        return $cleaned;
    }
}
