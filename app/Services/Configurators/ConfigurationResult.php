<?php

namespace App\Services\Configurators;

use App\Models\Integrations\ExternalConfigurator;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Workflow\LineConfigurationItem;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileRole;

/**
 * Résultat d'un configurateur ramené à ce que l'ERP sait écrire (contrat § 4.5) :
 * champs bornés, unités et articles résolus, remise du client appliquée selon le mode
 * de prix du configurateur. Un résultat sans ligne exploitable est refusé ; un champ
 * inconnu est ignoré, un champ absent toléré.
 */
class ConfigurationResult
{
    /** Champs numériques du détail de ligne repris tels quels. */
    private const NUMERIC_DETAIL = ['thickness', 'x_size', 'y_size', 'z_size', 'diameter', 'weight'];
    private const TEXT_DETAIL = ['material', 'finishing'];
    private const LABEL_MAX = 255;

    public function normalize(array $raw, ExternalConfigurator $configurator, Quotes|Orders $document): array
    {
        $rawLines = is_array($raw['lines'] ?? null) ? array_values($raw['lines']) : [];
        $max = (int) config('configurators.max_lines', 50);
        if ($rawLines === []) {
            throw new ConfiguratorException(__('configurators.errors.no_lines'));
        }
        if (count($rawLines) > $max) {
            throw new ConfiguratorException(__('configurators.errors.too_many_lines', ['max' => $max]));
        }

        $discount = $configurator->price_mode === ExternalConfigurator::PRICE_NET
            ? 0.0
            : (float) ($document->companie->discount ?? 0);

        $units = MethodsUnits::query()->get(['id', 'code', 'label']);
        $defaultUnit = MethodsUnits::getDefault();
        $warnings = [];
        $budget = (int) config('configurators.max_result_bytes');

        $lines = [];
        foreach ($rawLines as $index => $row) {
            $lines[] = $this->line(is_array($row) ? $row : [], $index + 1, $discount, $units, $defaultUnit, $warnings, $budget);
        }

        return [
            'configurator'      => $configurator->label,
            'configuration_ref' => self::text($raw['configuration_ref'] ?? null, 255),
            'configuration_url' => self::text($raw['configuration_url'] ?? null, 500),
            'lines'             => $lines,
            'files'             => $this->files($raw['files'] ?? [], $warnings, $budget),
            'warnings'          => $warnings,
            'total'             => round(array_sum(array_column($lines, 'net_total')), 2),
        ];
    }

    private function line(array $row, int $position, float $discount, $units, ?MethodsUnits $defaultUnit, array &$warnings, int &$budget): array
    {
        $label = self::text($row['label'] ?? null, 10000);
        $qty = is_numeric($row['quantity'] ?? null) ? (float) $row['quantity'] : null;
        $price = is_numeric($row['unit_price'] ?? null) ? (float) $row['unit_price'] : null;

        if ($label === null || $qty === null || $qty <= 0 || $price === null || $price < 0) {
            throw new ConfiguratorException(__('configurators.errors.invalid_line', ['line' => $position]));
        }

        // Désignation trop longue : coupée, texte complet repris dans le commentaire externe.
        $commentExternal = self::text($row['comment_external'] ?? null, 5000);
        if (mb_strlen($label) > self::LABEL_MAX) {
            $commentExternal = trim($label . "\n" . ($commentExternal ?? ''));
            $label = mb_substr($label, 0, self::LABEL_MAX);
        }

        $unitCode = mb_strtolower((string) ($row['unit'] ?? ''));
        $unit = $unitCode !== ''
            ? $units->first(fn ($u) => mb_strtolower((string) $u->code) === $unitCode || mb_strtolower((string) $u->label) === $unitCode)
            : null;
        if ($unit === null && $unitCode !== '') {
            $warnings[] = __('configurators.warnings.unknown_unit', ['line' => $position, 'unit' => $row['unit']]);
        }
        $unit ??= $defaultUnit;

        $productCode = self::text($row['product_code'] ?? null, 255);
        $productId = $productCode !== null ? Products::where('code', $productCode)->value('id') : null;
        if ($productCode !== null && $productId === null) {
            $warnings[] = __('configurators.warnings.unknown_product', ['line' => $position, 'code' => $productCode]);
        }

        $lead = is_numeric($row['lead_time_days'] ?? null) && $row['lead_time_days'] >= 0 ? (int) $row['lead_time_days'] : null;

        $detail = [];
        $rawDetail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
        foreach (self::NUMERIC_DETAIL as $field) {
            if (is_numeric($rawDetail[$field] ?? null)) {
                $detail[$field] = (float) $rawDetail[$field];
            }
        }
        foreach (self::TEXT_DETAIL as $field) {
            if (($value = self::text($rawDetail[$field] ?? null, 255)) !== null) {
                $detail[$field] = $value;
            }
        }

        $items = [];
        foreach ([
            'options'      => LineConfigurationItem::KIND_OPTION,
            'dimensions'   => LineConfigurationItem::KIND_DIMENSION,
            'requirements' => LineConfigurationItem::KIND_REQUIREMENT,
        ] as $key => $kind) {
            foreach (is_array($row[$key] ?? null) ? $row[$key] : [] as $entry) {
                if (is_array($entry) && ($item = self::item($entry, $kind)) !== null) {
                    $items[] = $item;
                }
            }
        }
        if (is_array($row['supplier'] ?? null)) {
            $supplier = $row['supplier'];
            $reference = self::text($supplier['reference'] ?? null, 255);
            if ($reference !== null) {
                $items[] = [
                    'kind'       => LineConfigurationItem::KIND_SUPPLIER,
                    'code'       => self::text($supplier['code'] ?? null, 100),
                    'label'      => self::text($supplier['label'] ?? null, 255) ?? $reference,
                    'value'      => $reference,
                    'unit'       => null,
                    'unit_price' => null,
                ];
            }
        }

        return [
            'code'             => self::text($row['code'] ?? null, 255) ?? '',
            'label'            => $label,
            'product_id'       => $productId,
            'product_code'     => $productId ? $productCode : null,
            'qty'              => $qty,
            'methods_units_id' => $unit?->id,
            'unit_label'       => $unit?->label,
            'unit_price'       => round($price, 3),
            'discount'         => $discount,
            'net_total'        => round($qty * $price * (1 - $discount / 100), 2),
            'lead_time_days'   => $lead,
            'detail'           => $detail,
            'items'            => array_slice($items, 0, 200),
            'comment_internal' => self::text($row['comment_internal'] ?? null, 5000),
            'comment_external' => $commentExternal,
            'files'            => $this->files($row['files'] ?? [], $warnings, $budget),
        ];
    }

    private static function item(array $entry, string $kind): ?array
    {
        $label = self::text($entry['label'] ?? null, 255) ?? self::text($entry['code'] ?? null, 255);
        if ($label === null) {
            return null;
        }

        $value = $entry['value'] ?? null;

        return [
            'kind'       => $kind,
            'code'       => self::text($entry['code'] ?? null, 100),
            'label'      => $label,
            'value'      => is_scalar($value) ? self::text((string) $value, 255) : null,
            'unit'       => self::text($entry['unit'] ?? null, 20),
            'unit_price' => is_numeric($entry['unit_price'] ?? null) ? round((float) $entry['unit_price'], 3) : null,
        ];
    }

    /** Fichiers annoncés : rôle connu sinon « autre », taille bornée par fichier et par résultat. */
    private function files(mixed $files, array &$warnings, int &$budget): array
    {
        $kept = [];
        $max = (int) config('configurators.max_file_bytes');

        foreach (is_array($files) ? $files : [] as $file) {
            if (! is_array($file) || ! is_scalar($file['id'] ?? null) || (string) $file['id'] === '') {
                continue;
            }

            $name = self::text(basename(str_replace('\\', '/', (string) ($file['name'] ?? ''))), 255) ?? ('fichier-' . $file['id']);
            $size = is_numeric($file['size'] ?? null) ? (int) $file['size'] : 0;
            if ($size > $max || $size > $budget) {
                $warnings[] = __('configurators.warnings.file_too_large', ['name' => $name]);
                continue;
            }
            $budget -= $size;

            $role = (string) ($file['role'] ?? '');
            $kept[] = [
                'id'   => mb_substr((string) $file['id'], 0, 100),
                'name' => $name,
                'role' => in_array($role, FileRole::all(), true) ? $role : FileRole::OTHER,
                'size' => $size,
            ];
        }

        return $kept;
    }

    private static function text(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
