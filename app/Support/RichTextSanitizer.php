<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * 리치 에디터(Quill)로 작성한 HTML을 허용 목록 기준으로 정제한다.
 * 상품 상세 설명은 판매점 계정도 작성하고 쇼핑몰에서 원문 그대로 출력되므로,
 * 스크립트·이벤트 핸들러·javascript: URL이 저장되지 않도록 서버에서 걸러낸다.
 */
class RichTextSanitizer
{
    private const ALLOWED = [
        'p' => [], 'br' => [], 'span' => [], 'div' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => [], 'li' => ['data-list'],
        'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'],
    ];

    private const COMMON_ATTRS = ['class', 'style'];

    private const ALLOWED_STYLES = ['color', 'background-color', 'text-align', 'width', 'height', 'font-size', 'font-weight'];

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }
        if (! preg_match('/<\w+[\s\S]*?>/', $html)) {
            return $html;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $ok) {
            return strip_tags($html);
        }

        $root = $doc->getElementById('__root');
        if (! $root) {
            return strip_tags($html);
        }

        self::cleanNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out) === '' ? null : trim($out);
    }

    private static function cleanNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta'], true)) {
                        $node->removeChild($child);
                    } else {
                        self::cleanNode($child);
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                    }
                    continue;
                }

                self::cleanAttributes($child, $tag);

                if ($tag === 'img' && ! $child->hasAttribute('src')) {
                    $node->removeChild($child);
                    continue;
                }

                self::cleanNode($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED[$tag], self::COMMON_ATTRS);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = trim($attr->nodeValue);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if ($name === 'href' || $name === 'src') {
                if (! self::safeUrl($value)) {
                    $el->removeAttribute($attr->nodeName);
                }
                continue;
            }
            if ($name === 'style') {
                $style = self::cleanStyle($value);
                $style === '' ? $el->removeAttribute('style') : $el->setAttribute('style', $style);
            }
        }

        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        return (bool) preg_match('#^(https?://|mailto:|data:image/(png|jpe?g|gif|webp);base64,)#i', $url);
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $decl) {
            if (! str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            if (! in_array($prop, self::ALLOWED_STYLES, true)) {
                continue;
            }
            if (preg_match('/url\s*\(|expression|javascript:/i', $val)) {
                continue;
            }
            $kept[] = $prop.': '.$val;
        }

        return implode('; ', $kept);
    }
}
