<?php

namespace App\Services\Configurators;

use App\Models\Integrations\ConfiguratorSession;
use App\Models\Integrations\ExternalConfigurator;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Parcours d'une configuration externe : ouverture de la session, relecture du
 * résultat (aperçu, rien d'écrit), puis écriture des lignes après confirmation.
 * Contrat : docs/conception/configurateurs-externes.md.
 */
class ConfiguratorService
{
    public function __construct(
        private readonly ConfigurationResult $results,
        private readonly ConfiguredLineWriter $writer,
    ) {}

    /** Devis au statut 1, commande au statut 1 : seuls documents où l'on ajoute des lignes configurées. */
    public static function document(string $type, int $id): Quotes|Orders
    {
        $document = match ($type) {
            'quote' => Quotes::withTemplates()->findOrFail($id),
            'order' => Orders::findOrFail($id),
            default => abort(404),
        };

        if ((int) $document->statu !== 1 || ($type === 'order' && (int) $document->type === 2)) {
            throw new ConfiguratorException(__('configurators.errors.document_locked'));
        }

        return $document;
    }

    /**
     * @return array{session: string, url: string, display: string, origin: ?string}
     */
    public function open(ExternalConfigurator $configurator, string $type, Quotes|Orders $document, User $user,
        ?Products $product, float $quantity, string $returnOrigin): array
    {
        $lines = $type === 'quote' ? QuoteLines::class : OrderLines::class;
        $foreignKey = $type === 'quote' ? 'quotes_id' : 'orders_id';
        $nextOrder = (int) $lines::where($foreignKey, $document->id)->max('ordre') + 1;

        $session = ConfiguratorSession::create([
            'uuid'                     => (string) Str::uuid(),
            'external_configurator_id' => $configurator->id,
            'user_id'                  => $user->id,
            'document_type'            => $type,
            'document_id'              => $document->id,
            'reference_externe'        => $document->code . '/L' . $nextOrder,
            'status'                   => ConfiguratorSession::STATUS_PENDING,
            'expires_at'               => now()->addMinutes((int) config('configurators.session_minutes', 120)),
        ]);

        $customer = $document->companie;
        $payload = [
            'reference_externe' => $session->reference_externe,
            'document'          => ['type' => $type, 'code' => $document->code, 'line_order' => $nextOrder],
            // Ni adresse ni contact : le configurateur n'en a pas besoin.
            'customer'          => $customer ? ['code' => $customer->code, 'label' => $customer->label] : null,
            'product'           => $product ? [
                'code'   => $product->code,
                'label'  => $product->label,
                'family' => $product->family?->code,
            ] : null,
            'quantity'          => $quantity,
            'locale'            => app()->getLocale(),
            'currency'          => app('Factory')->curency ?? 'EUR',
            'user'              => ['name' => $user->name, 'email' => $user->email],
            'configuration_ref' => null,
            'return'            => [
                'origin' => $returnOrigin,
                'url'    => route('configurators.return', ['session' => $session->uuid]),
            ],
        ];

        try {
            $remote = (new ConfiguratorClient($configurator))->openSession($payload);
        } catch (ConfiguratorException $e) {
            $session->forceFill(['status' => ConfiguratorSession::STATUS_CANCELLED, 'closed_at' => now()])->save();
            throw $e;
        }

        $session->forceFill(['remote_session_id' => $remote['session_id']])->save();

        return [
            'session' => $session->uuid,
            'url'     => $remote['url'],
            'display' => $configurator->display,
            'origin'  => $configurator->origin(),
        ];
    }

    /**
     * Relit le résultat et le garde sur la session pour l'aperçu. N'écrit aucune ligne.
     */
    public function preview(ConfiguratorSession $session, string $resultId): array
    {
        if (! $session->isOpen()) {
            throw new ConfiguratorException(__('configurators.errors.session_closed'));
        }
        if ($session->result_id !== null && $session->result_id !== $resultId) {
            throw new ConfiguratorException(__('configurators.errors.session_closed'));
        }
        if (ConfiguratorSession::where('result_id', $resultId)->whereKeyNot($session->id)->exists()) {
            throw new ConfiguratorException(__('configurators.errors.result_used'));
        }

        $configurator = $session->configurator;
        $raw = (new ConfiguratorClient($configurator))->result($resultId);

        // Le résultat doit fermer CETTE session : sinon, un resultId d'une autre session
        // (ou d'un autre client du configurateur) pourrait être injecté.
        if (($raw['session_id'] ?? null) !== $session->remote_session_id) {
            throw new ConfiguratorException(__('configurators.errors.foreign_result'));
        }

        $document = self::document($session->document_type, $session->document_id);
        $normalized = $this->results->normalize($raw, $configurator, $document);

        $session->forceFill([
            'result_id' => $resultId,
            'result'    => $normalized,
            'status'    => ConfiguratorSession::STATUS_PREVIEWED,
        ])->save();

        return $normalized;
    }

    /**
     * Écrit les lignes de l'aperçu confirmé, puis range les fichiers dans la GED.
     *
     * @return array{lines: array<int, Model>, warnings: array<int, string>}
     */
    public function apply(ConfiguratorSession $session): array
    {
        // Session verrouillée : un double clic ne crée pas deux fois les lignes.
        [$document, $lines, $result] = DB::transaction(function () use ($session) {
            $locked = ConfiguratorSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ConfiguratorSession::STATUS_PREVIEWED || ! $locked->isOpen()) {
                throw new ConfiguratorException(__('configurators.errors.session_closed'));
            }

            $document = self::document($locked->document_type, $locked->document_id);
            $lines = $this->writer->writeLines($locked, $document, $locked->result);
            $locked->forceFill(['status' => ConfiguratorSession::STATUS_DONE, 'closed_at' => now()])->save();

            return [$document, $lines, $locked->result];
        });

        // Fichiers après l'écriture : un téléchargement en échec n'annule pas les lignes.
        $warnings = $this->writer->attachFiles($session->configurator, $session->result_id, $document, $lines, $result);

        return ['lines' => $lines, 'warnings' => $warnings];
    }

    public function cancel(ConfiguratorSession $session): void
    {
        if ($session->isOpen()) {
            $session->forceFill(['status' => ConfiguratorSession::STATUS_CANCELLED, 'closed_at' => now()])->save();
        }
    }
}
