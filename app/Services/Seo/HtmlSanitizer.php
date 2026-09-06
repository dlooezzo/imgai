<?php

namespace App\Services\Seo;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class HtmlSanitizer
{
    /**
     * Whitelisted safe HTML tags for articles.
     */
    protected static array $allowedTags = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'span', 'div',
        'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'mark',
        'ul', 'ol', 'li',
        'a',
        'blockquote', 'q', 'cite',
        'figure', 'figcaption', 'img', 'picture', 'source',
        'pre', 'code', 'kbd', 'samp',
        'hr', 'br',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'details', 'summary',
        'section', 'article',
    ];

    /**
     * Whitelisted safe attributes on tags.
     */
    protected static array $allowedAttributes = [
        '*' => ['class', 'id', 'title', 'lang', 'dir'],
        'a' => ['href', 'target', 'rel', 'download'],
        'img' => ['src', 'alt', 'width', 'height', 'loading', 'decoding'],
        'source' => ['src', 'srcset', 'type', 'media'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'table' => ['cellpadding', 'cellspacing', 'border'],
    ];

    /**
     * Sanitize and normalize HTML string for safe publishing.
     */
    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if (empty($html)) {
            return '';
        }

        // Quick pre-filtering of dangerous script / iframe / embed tags
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $html = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $html);
        $html = preg_replace('#<object(.*?)>(.*?)</object>#is', '', $html);
        $html = preg_replace('#<embed(.*?)>(.*?)</embed>#is', '', $html);
        $html = preg_replace('#<applet(.*?)>(.*?)</applet>#is', '', $html);

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        
        // Wrap with UTF-8 meta and body to preserve unicode
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $html . '</body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        // Only inspect descendants of body
        $nodes = $xpath->query('//body//*');

        $nodesToRemove = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($node->tagName);

            // Strip disallowed tags
            if (!in_array($tagName, self::$allowedTags, true)) {
                $nodesToRemove[] = $node;
                continue;
            }

            // Clean attributes
            if ($node->hasAttributes()) {
                $attributesToRemove = [];
                foreach ($node->attributes as $attr) {
                    $attrName = strtolower($attr->nodeName);
                    $attrValue = $attr->nodeValue;

                    // Remove event handlers (onclick, onload, onerror, etc.)
                    if (str_starts_with($attrName, 'on')) {
                        $attributesToRemove[] = $attrName;
                        continue;
                    }

                    // Check if attribute is allowed
                    $globalAllowed = self::$allowedAttributes['*'] ?? [];
                    $tagAllowed = self::$allowedAttributes[$tagName] ?? [];
                    $allowed = array_merge($globalAllowed, $tagAllowed);

                    if (!in_array($attrName, $allowed, true)) {
                        $attributesToRemove[] = $attrName;
                        continue;
                    }

                    // Sanitize URLs (href, src) against javascript: pseudo-protocols
                    if (in_array($attrName, ['href', 'src'], true)) {
                        $loweredValue = strtolower(trim($attrValue));
                        if (str_starts_with($loweredValue, 'javascript:') || 
                            str_starts_with($loweredValue, 'vbscript:') || 
                            str_starts_with($loweredValue, 'data:text/html')) {
                            $attributesToRemove[] = $attrName;
                            continue;
                        }
                    }

                    // Enforce rel="noopener noreferrer" for external target="_blank" links
                    if ($tagName === 'a' && $attrName === 'target' && $attrValue === '_blank') {
                        $node->setAttribute('rel', 'noopener noreferrer');
                    }
                }

                foreach ($attributesToRemove as $attrName) {
                    $node->removeAttribute($attrName);
                }
            }
        }

        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Extract body content
        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $cleanHtml = '';
        foreach ($body->childNodes as $child) {
            $cleanHtml .= $doc->saveHTML($child);
        }

        return trim($cleanHtml);
    }
}
