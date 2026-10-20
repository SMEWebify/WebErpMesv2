<?php

namespace App\Support;

/**
 * Sizes the company logo on printed documents.
 *
 * The PDF views used to force a height only, so a wide logo grew as wide as
 * it liked and pushed the document title off the page (DomPDF never shrinks
 * a table cell below its image). The logo is now fitted into a box with its
 * proportions kept: explicit pixel sizes, because DomPDF's max-width inside
 * a table cell is not reliable.
 */
class PdfLogo
{
    /**
     * Inline CSS sizing a base64 logo to fit within $maxWidth x $maxHeight.
     */
    public static function style(?string $base64, int $maxWidth, int $maxHeight): string
    {
        $size = $base64 ? @getimagesizefromstring((string) base64_decode($base64, true)) : false;

        if (! $size || empty($size[0]) || empty($size[1])) {
            // Unreadable (or vector) image: let the renderer do its best within the box.
            return "max-width: {$maxWidth}px; max-height: {$maxHeight}px;";
        }

        $ratio  = min($maxWidth / $size[0], $maxHeight / $size[1]);
        $width  = max(1, (int) floor($size[0] * $ratio));
        $height = max(1, (int) floor($size[1] * $ratio));

        return "width: {$width}px; height: {$height}px;";
    }
}
