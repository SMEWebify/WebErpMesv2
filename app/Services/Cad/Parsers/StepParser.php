<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Files\FileRole;
use Illuminate\Http\UploadedFile;

/**
 * STEP (ISO 10303-21) — only the header and the first PRODUCT entity are read.
 *
 * No dimensions: a STEP holds a boundary representation, and measuring it
 * means tessellating the solid through a CAD kernel. The file itself is
 * attached to the line, where the viewer renders it with OpenCascade.
 */
class StepParser extends BaseCadParser
{
    /** Enough to cover the header and reach the first PRODUCT entity. */
    private const READ_BYTES = 131072;

    public static function extensions(): array
    {
        return ['step', 'stp'];
    }

    public function gedRole(): ?string
    {
        return FileRole::MODEL_3D;
    }

    public function parse(UploadedFile $file): array
    {
        $head = $this->head($file, self::READ_BYTES);

        if (! str_contains(substr($head, 0, 200), 'ISO-10303-21')) {
            throw new \RuntimeException('Fichier STEP non reconnu');
        }

        $code = $this->baseName($file);
        $product = $this->product($head);
        $schema = $this->schema($head);

        return $this->result([
            'code' => $code,
            'label' => $this->label([$code, $product !== $code ? $product : null]),
            'extra' => $this->requirements([
                $this->requirement('Nom de la pièce', $product),
                $this->requirement('Schéma', $schema),
                $this->requirement('Source', 'STEP'),
            ]),
        ]);
    }

    /**
     * Name of the first PRODUCT entity, falling back to the name recorded in
     * the header when the DATA section starts further than we read.
     */
    private function product(string $head): ?string
    {
        // "=" and the immediate "(" keep PRODUCT_DEFINITION and friends out.
        if (preg_match("/=\s*PRODUCT\s*\(\s*'((?:[^']|'')*)'/i", $head, $matches) === 1) {
            $name = $this->unescape($matches[1]);

            if ($name !== '') {
                return $name;
            }
        }

        if (preg_match("/FILE_NAME\s*\(\s*'((?:[^']|'')*)'/i", $head, $matches) === 1) {
            $name = $this->unescape($matches[1]);

            // Most exporters put the full path of the original document there.
            return $name !== '' ? pathinfo(str_replace('\\', '/', $name), PATHINFO_FILENAME) : null;
        }

        return null;
    }

    /**
     * Application protocol of the file: AP203, AP214, AP242…
     */
    private function schema(string $head): ?string
    {
        if (preg_match("/FILE_SCHEMA\s*\(\s*\(\s*'((?:[^']|'')*)'/i", $head, $matches) !== 1) {
            return null;
        }

        $schema = $this->unescape($matches[1]);

        // "AUTOMOTIVE_DESIGN { 1 0 10303 214 ... }" — the braces carry the
        // version and only add noise on the line.
        $schema = trim(preg_replace('/\{.*$/s', '', $schema));

        return $schema !== '' ? $schema : null;
    }

    /**
     * STEP escapes a quote by doubling it.
     */
    private function unescape(string $value): string
    {
        return trim(str_replace("''", "'", $value));
    }
}
