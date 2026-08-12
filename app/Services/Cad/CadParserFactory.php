<?php

namespace App\Services\Cad;

use App\Services\Cad\Parsers\DxfParser;
use App\Services\Cad\Parsers\GeoParser;
use App\Services\Cad\Parsers\StepParser;
use App\Services\Cad\Parsers\SvgParser;
use App\Services\Cad\Parsers\SymParser;

/**
 * Resolves the parser handling an extension.
 *
 * The order below is the order the formats are listed to the user.
 */
class CadParserFactory
{
    /**
     * @var array<int, class-string<CadParser>>
     */
    private const PARSERS = [
        SymParser::class,
        GeoParser::class,
        DxfParser::class,
        StepParser::class,
        SvgParser::class,
    ];

    public static function for(string $extension): ?CadParser
    {
        $extension = mb_strtolower(ltrim(trim($extension), '.'));

        foreach (self::PARSERS as $parser) {
            if (in_array($extension, $parser::extensions(), true)) {
                return app($parser);
            }
        }

        return null;
    }

    /**
     * Every extension the import accepts, lowercase and without the dot.
     *
     * @return array<int, string>
     */
    public static function extensions(): array
    {
        $extensions = [];

        foreach (self::PARSERS as $parser) {
            $extensions = array_merge($extensions, $parser::extensions());
        }

        return array_values(array_unique($extensions));
    }

    /**
     * The list as an HTML file input accepts it: ".sym,.geo,.dxf…".
     */
    public static function accept(): string
    {
        return '.' . implode(',.', self::extensions());
    }
}
