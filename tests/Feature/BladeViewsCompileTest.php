<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use ParseError;
use Tests\TestCase;

/**
 * Compile chaque vue Blade sans l'afficher : aucune donnée n'est nécessaire.
 *
 * Attrape le piège qui a cassé la fiche pré-commande en v3.0.0 : un
 * `@php(...)` en ligne suivi plus loin d'un bloc `@php ... @endphp` fait lire
 * tout l'intervalle comme un seul bloc PHP. Le résultat est soit du PHP
 * invalide, soit des directives laissées brutes dans la page.
 */
class BladeViewsCompileTest extends TestCase
{
    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $failures = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getRelativePathname());
            $compiled = Blade::compileString($file->getContents());

            try {
                token_get_all($compiled, TOKEN_PARSE);
            } catch (ParseError $e) {
                $failures[] = "{$path} : PHP invalide après compilation ({$e->getMessage()}, ligne {$e->getLine()})";

                continue;
            }

            if (preg_match('/(?<![@\w])@(endphp|endif|endforeach|endforelse|endisset|endempty)\b/', $compiled, $match)) {
                $failures[] = "{$path} : directive @{$match[1]} laissée brute (mélange de @php(...) et de @php ... @endphp ?)";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
