<?php

namespace App\Services\Integrations\Nest2Bend\Data;

/**
 * Réponse 200 de GET /api/v1/results/{resultId}.
 *
 * Lus : `resultat.temps` (section affichée à « Terminer »), `resultat.section`,
 * `resultat.simulation` (presse, montages, ordre de pliage) et `fiche`, l'URL absolue de la
 * fiche de réglage PDF de toute la pièce (null sans poinçon choisi ou pièce non simulée).
 */
final class BendingResult
{
    public function __construct(
        public readonly string $resultId,
        public readonly string $sessionId,
        public readonly ?string $referenceExterne,
        public readonly ?string $politique,
        public readonly ?float $preparation,
        public readonly ?float $unitaire,
        public readonly array $compteurs = [],
        public readonly ?array $section = null,
        public readonly ?string $fiche = null,
        public readonly ?array $simulation = null,
    ) {}

    public static function fromArray(array $body): self
    {
        $resultat = is_array($body['resultat'] ?? null) ? $body['resultat'] : [];
        $temps = is_array($resultat['temps'] ?? null) ? $resultat['temps'] : [];
        $retenu = is_array($temps['retenu'] ?? null) ? $temps['retenu'] : [];

        $seconds = static fn ($value): ?float => is_numeric($value) && (float) $value >= 0 ? (float) $value : null;

        return new self(
            resultId: (string) ($body['result_id'] ?? ''),
            sessionId: (string) ($body['session_id'] ?? ''),
            referenceExterne: isset($body['reference_externe']) ? (string) $body['reference_externe'] : null,
            politique: is_string($temps['politique'] ?? null) ? $temps['politique'] : null,
            preparation: $seconds($retenu['preparation'] ?? null),
            unitaire: $seconds($retenu['unitaire'] ?? null),
            compteurs: is_array($temps['compteurs'] ?? null) ? $temps['compteurs'] : [],
            section: is_array($resultat['section'] ?? null) ? $resultat['section'] : null,
            fiche: is_string($body['fiche'] ?? null) && $body['fiche'] !== '' ? $body['fiche'] : null,
            simulation: self::simulation($resultat['simulation'] ?? null),
        );
    }

    /**
     * `resultat.simulation` réduit à ce que l'ERP affiche : presse, outils de chaque montage
     * (référence, constructeur, ouverture V) et ordre de pliage. Le reste est ignoré.
     *
     * @return array{presse: ?string, montages: list<array{poincon: ?string, matrice: ?string, retourne: bool}>,
     *               sequence: list<int>, plis: list<?int>}|null
     */
    private static function simulation(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $text = static fn ($value): ?string => is_scalar($value) && trim((string) $value) !== '' ? mb_substr(trim((string) $value), 0, 120) : null;

        $tool = static function (mixed $tool, bool $die) use ($text): ?string {
            if (! is_array($tool)) {
                return null;
            }
            $label = $text($tool['reference'] ?? null);
            if ($label === null) {
                return null;
            }
            if ($die && is_numeric($tool['ouverture_v'] ?? null)) {
                $label .= ' V' . (0 + $tool['ouverture_v']);
            }
            $maker = $text($tool['constructeur'] ?? null);

            return $maker !== null ? $label . ' (' . $maker . ')' : $label;
        };

        $montages = [];
        foreach (is_array($raw['montages'] ?? null) ? array_values($raw['montages']) : [] as $montage) {
            $montages[] = [
                'poincon'  => $tool($montage['poincon'] ?? null, false),
                'matrice'  => $tool($montage['matrice'] ?? null, true),
                'retourne' => (bool) ($montage['poincon_retourne'] ?? false),
            ];
        }

        $integers = static fn ($list): array => is_array($list)
            ? array_values(array_map('intval', array_filter($list, static fn ($v) => is_int($v) || (is_string($v) && ctype_digit($v)))))
            : [];

        return [
            'presse'   => is_array($raw['presse'] ?? null) ? $text($raw['presse']['nom'] ?? null) : null,
            'montages' => $montages,
            'sequence' => $integers($raw['sequence'] ?? null),
            'plis'     => array_values(array_map(
                static fn ($pli) => is_array($pli) && is_numeric($pli['montage'] ?? null) ? (int) $pli['montage'] : null,
                is_array($raw['plis'] ?? null) ? $raw['plis'] : [],
            )),
        ];
    }
}
