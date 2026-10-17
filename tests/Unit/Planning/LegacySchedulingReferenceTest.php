<?php

namespace Tests\Unit\Planning;

use Tests\TestCase;

/**
 * Non-régression de l'ordonnancement historique (chaînage par `ordre`).
 *
 * tests/Fixtures/planning/legacy-dates.json a été calculé AVANT l'introduction
 * des dépendances entre opérations. Une gamme sans lien doit produire exactement
 * les mêmes dates : c'est la garantie donnée au client en production.
 *
 * Si la référence manque, elle est écrite et le test est marqué incomplet :
 * ne jamais régénérer une référence existante pour faire passer ce test.
 */
class LegacySchedulingReferenceTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../../Fixtures/planning/legacy-dates.json';

    public function test_dates_without_dependencies_match_the_reference(): void
    {
        $actual = [];
        foreach (PlanningScenarios::all() as $name => $scenario) {
            $actual[$name] = PlanningScenarios::run($scenario);
        }

        if (! is_file(self::FIXTURE)) {
            if (! is_dir(dirname(self::FIXTURE))) {
                mkdir(dirname(self::FIXTURE), 0777, true);
            }
            file_put_contents(
                self::FIXTURE,
                json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            $this->markTestIncomplete('Référence écrite : ' . self::FIXTURE);
        }

        $expected = json_decode(file_get_contents(self::FIXTURE), true);

        foreach ($expected as $name => $reference) {
            $this->assertArrayHasKey($name, $actual, "Scénario disparu : {$name}");
            $this->assertSame($reference, $actual[$name], "Dates modifiées dans le scénario {$name}");
        }
        $this->assertSameSize($expected, $actual, 'Un scénario a été ajouté : écrire sa référence avant la modification.');
    }
}
