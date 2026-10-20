<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use Illuminate\Http\UploadedFile;

/**
 * RADAN .sym — XML carrying the geometry attributes and a BMP thumbnail.
 *
 * Everything useful is in the RadanAttributes block, indexed by attribute
 * number. The numbers below come from the RADAN attribute table.
 */
class SymParser extends BaseCadParser
{
    private const ATTR_FILENAME = 110;
    private const ATTR_MATERIAL = 119;
    private const ATTR_THICKNESS = 120;
    private const ATTR_THICKNESS_UNIT = 121;
    private const ATTR_SURFACE = 162;
    private const ATTR_SURFACE_EXT = 163;
    private const ATTR_WEIGHT = 164;
    private const ATTR_X_SIZE = 165;
    private const ATTR_Y_SIZE = 166;
    private const ATTR_PERIMETER_EXT = 167;
    private const ATTR_PERIMETER_TOTAL = 168;
    private const ATTR_GEO_UNIT = 169;
    private const ATTR_BEND_COUNT = 500;
    private const ATTR_LASER_CUT_LENGTH = 510;
    private const ATTR_LASER_PIERCINGS = 512;

    public static function extensions(): array
    {
        return ['sym'];
    }

    public function parse(UploadedFile $file): array
    {
        $xml = $this->loadRadanXml($file);

        return $this->fromAttributes(
            $xml->RadanAttributes,
            $this->baseName($file),
            isset($xml->Thumbnail) ? (string) $xml->Thumbnail : null,
            'RADAN .sym',
        );
    }

    /**
     * Read a RADAN compound document, .sym or .drg alike.
     */
    protected function loadRadanXml(UploadedFile $file): \SimpleXMLElement
    {
        $content = $file->get();

        // Strip the namespace so SimpleXML finds the elements without a prefix.
        $content = preg_replace('/\sxmlns="[^"]+"/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            throw new \RuntimeException('Fichier XML invalide');
        }

        return $xml;
    }

    /**
     * Index the attributes of a RadanAttributes block by their number.
     *
     * @return array<int, ?string>
     */
    protected function attributes(?\SimpleXMLElement $radanAttributes): array
    {
        $attrs = [];

        foreach ($radanAttributes?->Group ?? [] as $group) {
            foreach ($group->Attr ?? [] as $attr) {
                $num = (int) $attr['num'];
                $attrs[$num] = isset($attr['value']) ? (string) $attr['value'] : null;
            }
        }

        return $attrs;
    }

    /**
     * Turn the attributes of one symbol into the fields of a line.
     *
     * A .drg embeds a full copy of the attributes of every symbol it nests,
     * which is why the nest parser goes through here too: a part read from a
     * nest gives the very line its .sym would have given.
     */
    protected function fromAttributes(?\SimpleXMLElement $radanAttributes, string $fallbackCode, ?string $thumbnail, string $source): array
    {
        $attrs = $this->attributes($radanAttributes);

        $get = fn (int $num) => $attrs[$num] ?? null;
        $float = fn (int $num) => $get($num) !== null && $get($num) !== '' ? (float) $get($num) : null;

        $code = $get(self::ATTR_FILENAME) ?? $fallbackCode;
        $material = $get(self::ATTR_MATERIAL);
        $thickness = $float(self::ATTR_THICKNESS);
        $thicknessUnit = $get(self::ATTR_THICKNESS_UNIT) ?? 'mm';
        $xSize = $float(self::ATTR_X_SIZE);
        $ySize = $float(self::ATTR_Y_SIZE);
        $weight = $float(self::ATTR_WEIGHT);
        $geoUnit = $get(self::ATTR_GEO_UNIT) ?? 'mm';

        $dimensions = $xSize !== null && $ySize !== null
            ? $this->number($xSize) . 'x' . $this->number($ySize) . $geoUnit
            : null;

        $materialPart = $material !== null && $material !== ''
            ? $material . ($thickness !== null ? ' ' . $this->number($thickness) . $thicknessUnit : '')
            : null;

        return $this->result([
            'code' => $code,
            'label' => $this->label([$code, $materialPart, $dimensions]),
            'material' => $material,
            'thickness' => $thickness,
            'x_size' => $xSize,
            'y_size' => $ySize,
            'weight' => $weight !== null ? round($weight, 3) : null,
            'picture' => $thumbnail !== null ? $this->storeThumbnail($thumbnail) : null,
            'extra' => $this->requirements([
                $this->requirement('Périmètre extérieur', $float(self::ATTR_PERIMETER_EXT), $geoUnit),
                $this->requirement('Périmètre total', $float(self::ATTR_PERIMETER_TOTAL), $geoUnit),
                $this->requirement('Surface', $float(self::ATTR_SURFACE), $geoUnit . '²'),
                $this->requirement('Surface extérieure', $float(self::ATTR_SURFACE_EXT), $geoUnit . '²'),
                $this->requirement('Longueur de coupe', $float(self::ATTR_LASER_CUT_LENGTH), $geoUnit),
                $this->requirement('Perçages laser', $get(self::ATTR_LASER_PIERCINGS)),
                $this->requirement('Nombre de plis', $get(self::ATTR_BEND_COUNT)),
                $this->requirement('Source', $source),
            ]),
        ]);
    }

    /**
     * Decode an embedded thumbnail (BMP in a .sym, PNG in a .drg) and keep it
     * next to the other line images.
     */
    private function storeThumbnail(string $base64): ?string
    {
        $base64 = trim($base64);

        if ($base64 === '') {
            return null;
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $base64));

        if ($binary === false || strlen($binary) < 10) {
            return null;
        }

        $directory = public_path('images/quote-lines');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // GD reads BMP since PHP 7.2, so the thumbnail is normalised to PNG.
        if (function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring($binary);

            if ($image !== false) {
                $name = time() . '_' . uniqid() . '.png';
                imagepng($image, $directory . '/' . $name);
                imagedestroy($image);

                return $name;
            }
        }

        $name = time() . '_' . uniqid() . '.bmp';
        file_put_contents($directory . '/' . $name, $binary);

        return $name;
    }
}
