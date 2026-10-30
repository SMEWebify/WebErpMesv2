<?php

namespace App\Services\Documents;

use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Packaging;
use Illuminate\Support\Collection;

/**
 * Liste de colisage d'un BL (modèle d'impression « packing-list ») : chaque colis
 * avec ses dimensions, poids et contenu, puis la quantité de chaque ligne qui
 * n'est rangée dans aucun colis (« hors colis »), et les totaux.
 *
 * Ne lit que ce qui est saisi : poids et dimensions du colis, quantités de
 * packaging_lines. Rien n'est déduit du poids des articles.
 */
class PackingList
{
    /** En dessous, une quantité restante est un arrondi, pas un reste à colisager. */
    private const EPSILON = 0.0005;

    /**
     * @param  Collection<int, DeliveryLines>  $lines     lignes du BL
     * @param  Collection<int, Packaging>      $packages  colis du BL
     * @return array{packages: list<array>, unpacked: list<array>, totals: array{count: int, net_weight: float, gross_weight: float}}
     */
    public function build(Collection $lines, Collection $packages): array
    {
        $lines    = $lines->keyBy('id');
        $packed   = [];
        $packages = $packages->sortBy('id')->values();
        $packages->loadMissing('lines');

        $rows = [];
        foreach ($packages as $index => $package) {
            $content = [];
            foreach ($package->lines as $packagingLine) {
                $line = $lines->get($packagingLine->delivery_line_id);
                if ($line === null || $packagingLine->qty <= 0) {
                    continue;
                }
                $packed[$line->id] = ($packed[$line->id] ?? 0) + $packagingLine->qty;
                $content[]         = $this->row($line, $packagingLine->qty);
            }

            usort($content, fn ($a, $b) => $a['ordre'] <=> $b['ordre']);

            $rows[] = [
                'number'       => $index + 1,
                'code'         => $package->code,
                'type'         => $package->type,
                'length'       => (float) $package->length,
                'width'        => (float) $package->width,
                'height'       => (float) $package->height,
                'net_weight'   => (float) $package->net_weight,
                'gross_weight' => (float) $package->gross_weight,
                'comment'      => $package->comment,
                'lines'        => $content,
            ];
        }

        $unpacked = [];
        foreach ($lines as $line) {
            $remaining = (float) $line->qty - ($packed[$line->id] ?? 0);
            if ($remaining > self::EPSILON) {
                $unpacked[] = $this->row($line, $remaining);
            }
        }

        return [
            'packages' => $rows,
            'unpacked' => $unpacked,
            'totals'   => [
                'count'        => count($rows),
                'net_weight'   => array_sum(array_column($rows, 'net_weight')),
                'gross_weight' => array_sum(array_column($rows, 'gross_weight')),
            ],
        ];
    }

    private function row(DeliveryLines $line, float $qty): array
    {
        $orderLine = $line->OrderLine;

        return [
            'ordre'          => (int) $line->ordre,
            'order_code'     => $orderLine?->order?->code,
            'code'           => $orderLine?->code,
            'ind'            => $line->ind ?: null,
            'label'          => $orderLine?->label,
            'qty'            => $qty,
            'unit'           => $orderLine?->Unit?->label,
            'equipment_tags' => $orderLine && !empty($orderLine->equipment_tag) ? $orderLine->equipmentTagsText() : null,
        ];
    }
}
