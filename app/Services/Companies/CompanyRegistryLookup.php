<?php

namespace App\Services\Companies;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Recherche d'une entreprise française dans le répertoire Sirene, par l'API
 * Recherche d'entreprises de l'État (gratuite, sans clé, ~7 requêtes / s).
 *
 * Sert seulement à préremplir un formulaire : rien n'est enregistré ici,
 * l'utilisateur relit avant d'enregistrer. Seul le texte cherché part vers
 * l'API, jamais une donnée de l'ERP.
 *
 * Le n° de TVA intracommunautaire n'est pas fourni par Sirene : il est
 * calculé à partir du SIREN (clé officielle), donc juste pour une entreprise
 * assujettie, mais rien ne dit qu'elle l'est.
 */
class CompanyRegistryLookup
{
    public const MAX_RESULTS = 10;

    public function isEnabled(): bool
    {
        return (bool) config('services.company_registry.enabled')
            && filled(config('services.company_registry.url'));
    }

    /**
     * Normalise la saisie : un SIRET / SIREN tapé avec espaces ou points
     * devient une suite de chiffres, un nom reste tel quel.
     */
    public static function normalizeQuery(string $query): string
    {
        $query   = trim($query);
        $compact = preg_replace('/[\s.\-]/', '', $query);

        return preg_match('/^\d{9}(\d{5})?$/', $compact) ? $compact : $query;
    }

    /**
     * Clé de TVA française : (12 + 3 × (SIREN mod 97)) mod 97, sur 2 chiffres.
     */
    public static function vatNumber(string $siren): ?string
    {
        if (! preg_match('/^\d{9}$/', $siren)) {
            return null;
        }

        $key = (12 + 3 * ((int) $siren % 97)) % 97;

        return 'FR'.str_pad((string) $key, 2, '0', STR_PAD_LEFT).$siren;
    }

    /**
     * @return array<int, array<string, mixed>> une entrée par établissement proposé
     *
     * @throws RuntimeException API injoignable ou en erreur
     */
    public function search(string $query): array
    {
        $query = self::normalizeQuery($query);

        return Cache::remember(
            'company-registry:'.sha1($query),
            now()->addHours(12),
            fn () => $this->fetch($query)
        );
    }

    private function fetch(string $query): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(config('services.company_registry.timeout', 8))
                ->get(rtrim(config('services.company_registry.url'), '/').'/search', [
                    'q'        => $query,
                    'page'     => 1,
                    'per_page' => self::MAX_RESULTS,
                ]);
        } catch (\Throwable $e) {
            Log::warning('CompanyRegistryLookup: API unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException('unreachable', 0, $e);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('rate_limited');
        }

        if (! $response->successful()) {
            Log::warning('CompanyRegistryLookup: API error', [
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('error');
        }

        $siret   = preg_match('/^\d{14}$/', $query) ? $query : null;
        $results = [];

        foreach ((array) $response->json('results', []) as $unit) {
            $establishment = $this->pickEstablishment($unit, $siret);
            if ($establishment === null) {
                continue;
            }
            $results[] = $this->format($unit, $establishment);
        }

        return $results;
    }

    /**
     * Pour un SIRET : l'établissement visé ; sinon le siège.
     */
    private function pickEstablishment(array $unit, ?string $siret): ?array
    {
        if ($siret !== null) {
            foreach ((array) ($unit['matching_etablissements'] ?? []) as $etab) {
                if (($etab['siret'] ?? null) === $siret) {
                    return $etab;
                }
            }
            if (($unit['siege']['siret'] ?? null) === $siret) {
                return $unit['siege'];
            }

            return null;
        }

        return $unit['siege'] ?? null;
    }

    private function format(array $unit, array $etab): array
    {
        $siren   = (string) ($unit['siren'] ?? '');
        $zipcode = (string) ($etab['code_postal'] ?? '');
        $city    = (string) ($etab['libelle_commune'] ?? '');

        // `adresse` = « 8 RUE DE LONDRES 75009 PARIS » : on garde la voie, le
        // code postal et la commune ont leurs propres champs.
        $street = trim((string) ($etab['adresse'] ?? ''));
        $suffix = trim($zipcode.' '.$city);
        if ($suffix !== '' && str_ends_with($street, $suffix)) {
            $street = trim(mb_substr($street, 0, mb_strlen($street) - mb_strlen($suffix)));
        }

        $foreignCountry = $etab['libelle_pays_etranger'] ?? null;

        return [
            'siren'               => $siren,
            'siret'               => $etab['siret'] ?? null,
            'label'               => $unit['nom_raison_sociale'] ?? $unit['nom_complet'] ?? '',
            'naf_code'            => $unit['activite_principale'] ?? $etab['activite_principale'] ?? null,
            'intra_community_vat' => self::vatNumber($siren),
            'is_head_office'      => (bool) ($etab['est_siege'] ?? false),
            // Unité légale cessée (C) ou établissement fermé (F).
            'active'              => ($unit['etat_administratif'] ?? 'A') === 'A'
                                     && ($etab['etat_administratif'] ?? 'A') === 'A',
            'address'             => [
                'adress'  => $street,
                'zipcode' => $zipcode,
                'city'    => $city,
                'country' => $foreignCountry ? ucfirst(mb_strtolower($foreignCountry)) : 'France',
            ],
        ];
    }
}
