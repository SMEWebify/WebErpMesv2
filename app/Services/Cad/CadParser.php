<?php

namespace App\Services\Cad;

use Illuminate\Http\UploadedFile;

/**
 * Reads a CAD file and turns it into the fields of a quote or order line.
 *
 * Adding a format is a new class here plus one entry in CadParserFactory.
 */
interface CadParser
{
    /**
     * Lowercase extensions handled by this parser, without the dot.
     *
     * @return array<int, string>
     */
    public static function extensions(): array;

    /**
     * Extract the line fields from an upload.
     *
     * Called before the file is moved to its final location, so the parser can
     * still read it from the temporary path.
     *
     * derived_svg holds a contour redrawn as SVG when the format itself is not
     * readable by the nesting page nor by any viewer. It is stored in the GED
     * next to the source file.
     *
     * @return array{code: string, label: string, material: ?string, thickness: ?float,
     *               x_size: ?float, y_size: ?float, weight: ?float, picture: ?string,
     *               derived_svg: ?string, extra: ?array<int, array{label: string, value: string}>}
     *
     * @throws \RuntimeException when the file is not readable as this format
     */
    public function parse(UploadedFile $file): array;

    /**
     * Role the source file is stored under in the GED, or null to keep it out.
     *
     * A .sym is fully digested at import (metadata plus thumbnail), so keeping
     * the file would only duplicate what is already on the line. The other
     * formats stay useful afterwards — they are the drawing the shop works
     * from — and are therefore attached to the line.
     */
    public function gedRole(): ?string;
}
