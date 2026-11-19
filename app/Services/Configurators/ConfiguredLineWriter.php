<?php

namespace App\Services\Configurators;

use App\Models\Accounting\AccountingVat;
use App\Models\Integrations\ConfiguratorSession;
use App\Models\Integrations\ExternalConfigurator;
use App\Models\Workflow\LineConfigurationItem;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Écrit un résultat de configuration relu (ConfigurationResult) en lignes ordinaires
 * du devis ou de la commande, en fin de document : ligne, détail, éléments de
 * configuration structurés, marque du configurateur, puis fichiers dans la GED.
 * Prix figé (jamais de prix calculé).
 */
class ConfiguredLineWriter
{
    public function __construct(private readonly FileStorageService $files) {}

    /**
     * @return array<int, QuoteLines|OrderLines>
     */
    public function writeLines(ConfiguratorSession $session, Quotes|Orders $document, array $result): array
    {
        $vat = AccountingVat::getDefault();
        if (! $vat) {
            throw new ConfiguratorException(__('imports.no_line_defaults'));
        }

        $isQuote = $document instanceof Quotes;
        $lineClass = $isQuote ? QuoteLines::class : OrderLines::class;
        $foreignKey = $isQuote ? 'quotes_id' : 'orders_id';
        $ordre = (int) $lineClass::where($foreignKey, $document->id)->max('ordre');
        $now = now();

        $created = [];
        foreach ($result['lines'] as $data) {
            $attributes = [
                $foreignKey          => $document->id,
                'ordre'              => ++$ordre,
                'code'               => $data['code'],
                'label'              => $data['label'],
                'product_id'         => $data['product_id'],
                'qty'                => $data['qty'],
                'methods_units_id'   => $data['methods_units_id'],
                'selling_price'      => $data['unit_price'],
                'discount'           => $data['discount'],
                'accounting_vats_id' => $vat->id,
                'delivery_date'      => $data['lead_time_days'] !== null ? $now->copy()->addDays($data['lead_time_days'])->toDateString() : null,
            ];
            $attributes += $isQuote
                ? ['statu' => 1]
                : ['delivered_remaining_qty' => $data['qty'], 'invoiced_remaining_qty' => $data['qty']];

            /** @var QuoteLines|OrderLines $line */
            $line = new $lineClass($attributes);
            $line->forceFill([
                'external_configurator_id' => $session->external_configurator_id,
                'configuration_ref'        => $result['configuration_ref'],
                'configured_at'            => $now,
            ])->save();

            $detail = $data['detail'] + [
                'internal_comment' => $data['comment_internal'],
                'external_comment' => $data['comment_external'],
            ];
            $isQuote
                ? QuoteLineDetails::create(['quote_lines_id' => $line->id] + $detail)
                : OrderLineDetails::create(['order_lines_id' => $line->id] + $detail);

            foreach ($data['items'] as $sort => $item) {
                $line->configurationItems()->create($item + ['sort' => $sort]);
            }

            $created[] = $line;
        }

        return $created;
    }

    /**
     * Télécharge les fichiers annoncés et les range : ceux d'une ligne dans la GED de
     * la ligne (premier plan principal), ceux du résultat dans la GED du document.
     * Un fichier en échec est signalé, jamais bloquant.
     *
     * @param  array<int, Model>  $lines
     * @return array<int, string> avertissements
     */
    public function attachFiles(ExternalConfigurator $configurator, string $resultId, Quotes|Orders $document, array $lines, array $result): array
    {
        $client = new ConfiguratorClient($configurator);
        $warnings = [];

        foreach ($result['lines'] as $index => $data) {
            $primaryDone = false;
            foreach ($data['files'] as $file) {
                $primary = ! $primaryDone && $file['role'] === FileRole::PLAN;
                $primaryDone = $primaryDone || $primary;
                $this->store($client, $resultId, $file, $lines[$index], $primary, $warnings);
            }
        }

        foreach ($result['files'] as $file) {
            $this->store($client, $resultId, $file, $document, false, $warnings);
        }

        return $warnings;
    }

    private function store(ConfiguratorClient $client, string $resultId, array $file, Model $entity, bool $primary, array &$warnings): void
    {
        try {
            $download = $client->file($resultId, $file['id']);
            if (strlen($download['contents']) > (int) config('configurators.max_file_bytes')) {
                $warnings[] = __('configurators.warnings.file_too_large', ['name' => $file['name']]);

                return;
            }

            $stored = $this->files->storeContents(
                $download['contents'],
                $file['name'],
                $download['mime'] ?? 'application/octet-stream',
                ['hashtags' => ['configurateur']]
            );
            $this->files->attach($stored, $entity, $file['role'], $primary);
        } catch (\Throwable $e) {
            Log::warning('Configurateur : fichier non récupéré', ['file' => $file['name'], 'error' => $e->getMessage()]);
            $warnings[] = __('configurators.warnings.file_failed', ['name' => $file['name']]);
        }
    }
}
