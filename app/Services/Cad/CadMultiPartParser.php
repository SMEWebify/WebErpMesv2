<?php

namespace App\Services\Cad;

use Illuminate\Http\UploadedFile;

/**
 * A format that holds several parts, each becoming a line of its own.
 *
 * Optional, and tested with instanceof: the single-part formats keep their
 * parse() and know nothing about it.
 */
interface CadMultiPartParser extends CadParser
{
    /**
     * Extract one set of line fields per part.
     *
     * Each entry has the shape of CadParser::parse(), plus qty (the quantity
     * of the line) and cad_file (the name of the part file, in place of the
     * name of the upload).
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException when the file is not readable as this format
     */
    public function parseParts(UploadedFile $file): array;
}
