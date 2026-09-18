<?php

namespace App\Services\Files;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Nettoie un SVG avant de le servir publiquement (public/images/...).
 * Retire les éléments actifs (script, foreignObject...), les attributs
 * d'événement (on*), les liens javascript:/data: non image et les
 * ressources externes (href, url() en CSS ou en attribut de présentation).
 */
class SvgSanitizer
{
    private const FORBIDDEN_ELEMENTS = ['script', 'foreignobject', 'iframe', 'embed', 'object', 'handler', 'listener'];

    /**
     * Prologue XML autorisé avant la racine : déclaration et instructions de
     * traitement, commentaires, DOCTYPE (sous-ensemble interne compris), blancs.
     */
    private const PROLOG = '/\G(?:<\?.*?\?>|<!--.*?-->|<!DOCTYPE(?:[^\[>]|\[.*?\])*>|\s+)/is';

    /**
     * Vrai si la racine du document est <svg>, quelle que soit la longueur du prologue.
     */
    public static function looksLikeSvg(string $content): bool
    {
        $content = ltrim($content, "\xEF\xBB\xBF \t\r\n");
        $offset  = 0;

        while (preg_match(self::PROLOG, $content, $match, 0, $offset)) {
            $offset += strlen($match[0]);
        }

        return (bool) preg_match('/\G<svg[\s>\/]/i', $content, $match, 0, $offset);
    }

    public function sanitize(string $content): ?string
    {
        if (!self::looksLikeSvg($content)) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // LIBXML_NONET : pas de ressource réseau ; pas de LIBXML_NOENT -> entités non substituées (XXE)
        $loaded = $dom->loadXML($content, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return null;
        }

        if ($dom->doctype) {
            // Des entités déclarées resteraient en références (&x;) une fois le
            // DOCTYPE retiré : document illisible, on le refuse.
            if ($dom->doctype->internalSubset || $dom->doctype->entities->length > 0) {
                return null;
            }
            $dom->removeChild($dom->doctype);
        }

        $xpath = new DOMXPath($dom);

        $toRemove = [];
        foreach ($xpath->query('//*') as $node) {
            if (in_array(strtolower($node->localName), self::FORBIDDEN_ELEMENTS, true)) {
                $toRemove[] = $node;
            }
        }
        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*') as $node) {
            /** @var DOMElement $node */
            if (strtolower($node->localName) === 'style') {
                $node->textContent = $this->sanitizeCss($node->textContent);
            }

            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[] = $attr;
            }
            foreach ($attributes as $attr) {
                $name  = strtolower($attr->localName);
                $value = strtolower(preg_replace('/\s+/', '', $attr->value));

                $isEvent = str_starts_with($name, 'on');
                $isLink  = in_array($name, ['href', 'src', 'action', 'formaction'], true);
                $badLink = $isLink && !$this->isLocalReference($value);

                if ($isEvent || $badLink || str_contains($value, 'javascript:')) {
                    $node->removeAttributeNode($attr);
                    continue;
                }

                // style="..." et attributs de présentation (fill, filter, mask...) peuvent porter url()
                if ($name === 'style' || str_contains($value, 'url(')) {
                    $attr->value = $this->sanitizeCss($attr->value);
                }
            }
        }

        return $dom->saveXML($dom->documentElement);
    }

    /**
     * Retire @import et les url() qui ne pointent pas dans le document.
     * Un aperçu de pièce n'a pas besoin des échappements CSS : leur présence
     * suffit à vider la règle, plutôt que de tenter de les décoder.
     */
    private function sanitizeCss(string $css): string
    {
        if (str_contains($css, '\\')) {
            return '';
        }

        $css = preg_replace('/@import[^;]*;?/i', '', $css);

        return preg_replace_callback(
            '/url\(\s*([\'"]?)(.*?)\1\s*\)/is',
            fn (array $m) => $this->isLocalReference(strtolower(preg_replace('/\s+/', '', $m[2]))) ? $m[0] : 'none',
            $css
        );
    }

    /**
     * Ancre interne (#id) ou image raster embarquée : rien n'est chargé hors du document.
     */
    private function isLocalReference(string $value): bool
    {
        return str_starts_with($value, '#')
            || str_starts_with($value, 'data:image/png')
            || str_starts_with($value, 'data:image/jpeg');
    }
}
