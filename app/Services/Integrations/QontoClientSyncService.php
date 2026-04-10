<?php

namespace App\Services\Integrations;

use App\Models\Companies\Companies;
use App\Models\Customer\Customer;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoSyncReview;
use Illuminate\Support\Str;

class QontoClientSyncService
{
    public function reconcile(int $tenantId, array $wemClients, array $qontoClients, bool $importBidirectional = false): array
    {
        $mappedWem = [];
        $mappedQonto = [];
        $existingMappingsByWem = QontoClientMapping::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('qonto_client_id')
            ->pluck('qonto_client_id', 'wem_client_id')
            ->map(fn ($qontoClientId) => (string) $qontoClientId)
            ->all();
        $reservedQontoIds = array_values(array_unique(array_values($existingMappingsByWem)));

        foreach ($wemClients as $wemClient) {
            $wemClientId = (int) ($wemClient['id'] ?? 0);
            $currentQontoId = $existingMappingsByWem[$wemClientId] ?? null;
            $qontoCandidates = array_values(array_filter($qontoClients, function (array $qontoClient) use ($reservedQontoIds, $currentQontoId) {
                $qontoClientId = (string) ($qontoClient['id'] ?? '');

                if ($qontoClientId === '') {
                    return false;
                }

                if ($currentQontoId !== null && $qontoClientId === $currentQontoId) {
                    return true;
                }

                return ! in_array($qontoClientId, $reservedQontoIds, true);
            }));
            $match = $this->findBestMatch($wemClient, $qontoCandidates);

            // no_match : aucun candidat Qonto — sera créé dans Qonto par sync()
            if ($match['status'] === 'no_match') {
                continue;
            }

            // review_required : match ambigu, candidat(s) trouvé(s) mais incertitude
            if ($match['status'] === 'review_required') {
                QontoSyncReview::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'wem_client_id' => $wemClient['id'],
                        'qonto_client_id' => $match['best_candidate']['id'] ?? null,
                    ],
                    [
                        'matching_score' => $match['score'],
                        'candidate_payload' => $match,
                        'status' => 'pending',
                    ]
                );

                QontoClientMapping::updateOrCreate(
                    ['tenant_id' => $tenantId, 'wem_client_id' => $wemClient['id']],
                    ['sync_status' => 'review_required', 'matching_score' => $match['score'], 'last_sync_at' => now()]
                );

                continue;
            }

            if ($match['best_candidate']) {
                $qontoClientId = (string) ($match['best_candidate']['id'] ?? '');
                QontoClientMapping::updateOrCreate(
                    ['tenant_id' => $tenantId, 'wem_client_id' => $wemClient['id']],
                    [
                        'qonto_client_id' => $qontoClientId,
                        'sync_status' => 'linked',
                        'matching_score' => $match['score'],
                        'last_sync_at' => now(),
                    ]
                );

                $mappedWem[] = $wemClient['id'];
                $mappedQonto[] = $qontoClientId;
                $existingMappingsByWem[$wemClientId] = $qontoClientId;
                if (! in_array($qontoClientId, $reservedQontoIds, true)) {
                    $reservedQontoIds[] = $qontoClientId;
                }
            }
        }

        $createdInWem = 0;
        if ($importBidirectional) {
            foreach ($qontoClients as $qontoClient) {
                $id = (string) ($qontoClient['id'] ?? '');
                if (in_array($id, $mappedQonto, true)) {
                    continue;
                }

                $company = Companies::create([
                    'label' => $qontoClient['name'] ?? 'Qonto Client',
                    'client_type' => '1',
                    'user_id' => $tenantId,
                    'active' => 1,
                    'statu_customer' => 1,
                    'siren' => $qontoClient['siren'] ?? null,
                    'intra_community_vat' => $qontoClient['vat_number'] ?? null,
                ]);

                $customer = Customer::create([
                    'companies_id' => $company->id,
                    'name' => $qontoClient['name'] ?? 'Qonto',
                    'mail' => $qontoClient['email'] ?? null,
                ]);

                QontoClientMapping::updateOrCreate(
                    ['tenant_id' => $tenantId, 'qonto_client_id' => $id],
                    [
                        'wem_client_id' => $company->id,  // company->id pour cohérence avec fetchWemClients
                        'sync_status' => 'imported_from_qonto',
                        'matching_score' => 100,
                        'last_sync_at' => now(),
                    ]
                );

                $createdInWem++;
            }
        }

        return [
            'mapped_wem_count' => count($mappedWem),
            'mapped_qonto_count' => count($mappedQonto),
            'created_in_wem_count' => $createdInWem,
        ];
    }

    private function findBestMatch(array $wemClient, array $qontoClients): array
    {
        $candidates = [];

        foreach ($qontoClients as $qontoClient) {
            $score = $this->computeScore($wemClient, $qontoClient);
            if ($score <= 0) {
                continue;
            }

            $candidates[] = [
                'id' => $qontoClient['id'] ?? null,
                'score' => $score,
                'client' => $qontoClient,
            ];
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $candidates[0] ?? null;
        $second = $candidates[1] ?? null;

        // Aucun candidat Qonto : client inconnu, pas ambigu — à créer dans Qonto
        if (! $best) {
            return [
                'status' => 'no_match',
                'score' => 0,
                'best_candidate' => null,
                'candidates' => [],
            ];
        }

        // Score trop faible ou ex æquo → révision manuelle
        if ($best['score'] < 60 || ($second && $best['score'] - $second['score'] < 15)) {
            return [
                'status' => 'review_required',
                'score' => $best['score'],
                'best_candidate' => $best['client'],
                'candidates' => array_slice($candidates, 0, 3),
            ];
        }

        return [
            'status' => 'linked',
            'score' => $best['score'],
            'best_candidate' => $best['client'],
            'candidates' => array_slice($candidates, 0, 3),
        ];
    }

    private function computeScore(array $wemClient, array $qontoClient): int
    {
        $score = 0;

        // Qonto v2 retourne registration_number ; on accepte aussi siren/siret pour compatibilité
        $idMatches = [
            [$wemClient['siren'] ?? null,       $qontoClient['registration_number'] ?? $qontoClient['siren'] ?? null],
            [$wemClient['siret'] ?? null,        $qontoClient['registration_number'] ?? $qontoClient['siret'] ?? null],
            [$wemClient['vat_number'] ?? null,   $qontoClient['vat_number'] ?? null],
        ];
        foreach ($idMatches as [$wemVal, $qontoVal]) {
            if (! empty($wemVal) && ! empty($qontoVal) && $wemVal === $qontoVal) {
                $score = max($score, 100);
            }
        }

        if (! empty($wemClient['email']) && ! empty($qontoClient['email']) && strtolower($wemClient['email']) === strtolower($qontoClient['email'])) {
            $score = max($score, 85);
        }

        $wemName = $this->normalize($wemClient['name'] ?? null);
        $qontoName = $this->normalize($qontoClient['name'] ?? null);

        if ($wemName && $wemName === $qontoName) {
            if (! empty($wemClient['postal_code']) && ! empty($qontoClient['postal_code']) && $wemClient['postal_code'] === $qontoClient['postal_code']) {
                $score = max($score, 75);
            }

            if (! empty($wemClient['city']) && ! empty($qontoClient['city']) && $this->normalize($wemClient['city']) === $this->normalize($qontoClient['city'])) {
                $score = max($score, 65);
            }
        }

        return $score;
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::lower(Str::squish(preg_replace('/[^\pL\pN\s]/u', ' ', $value) ?? $value));
    }
}
