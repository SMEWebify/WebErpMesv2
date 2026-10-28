<?php

namespace App\Services\Documents;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\File;
use App\Models\Products\Products;
use App\Services\Cad\DxfToSvg;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Données de la fiche suiveuse (ex-« instruction de fabrication ») d'une commande :
 * une page de garde, puis une fiche par ligne article.
 *
 * Tout est réduit en tableaux simples : la vue print/production-sheet ne lit
 * aucune relation, ce qui la rend testable sans base et évite les requêtes
 * paresseuses pendant le rendu DomPDF.
 */
class ProductionSheetBuilder
{
    private const RASTER = ['png', 'jpg', 'jpeg', 'gif'];

    public function __construct(private FileStorageService $files, private DxfToSvg $dxfToSvg)
    {
    }

    public function build(Orders $order): array
    {
        $lines = $order->OrderLines()
            ->articles()
            ->with([
                'Product:id,code,label,picture,svg_file,drawing_file',
                'Product.files',
                'files',
                'Unit:id,label',
                'OrderLineDetails',
                'TechnicalCut.service:id,code,label',
                'TechnicalCut.machineResource:methods_ressources.id,code,label',
                'BOM.Component:id,code,label',
            ])
            ->orderBy('ordre')
            ->get();

        $orderUrl = route('orders.show', ['id' => $order->id]);

        $sheets = $lines->map(fn (OrderLines $line) => $this->sheet($line))->values();

        return [
            'orderCode' => $order->code,
            'customerName' => (int) $order->type === 1
                ? (string) optional($order->companie)->label
                : __('general_content.internal_order_trans_key'),
            'customerReference' => $order->customer_reference,
            'generatedAt' => Carbon::now(),
            'orderQrSrc' => $this->qrCode($orderUrl),
            'sheets' => $sheets,
            'totalRequired' => (float) $sheets->sum('qty'),
            'totalDelivered' => (float) $sheets->sum('delivered_qty'),
        ];
    }

    private function sheet(OrderLines $line): array
    {
        $details = $line->OrderLineDetails;
        $operations = $line->TechnicalCut;

        return [
            'ordre' => $line->ordre,
            'code' => $line->code,
            'label' => $line->label,
            'product_code' => $line->Product?->code,
            'revision' => $line->ind,
            'equipment_tags' => $line->equipment_tag,
            'qty' => (float) $line->qty,
            'delivered_qty' => (float) $line->delivered_qty,
            'unit' => $line->Unit?->label,
            'due_date' => $line->delivery_date,
            'internal_delay' => $line->internal_delay,
            'material' => $details?->material,
            'thickness' => $details?->thickness,
            'x_size' => $details?->x_size,
            'y_size' => $details?->y_size,
            'z_size' => $details?->z_size,
            'weight' => $details?->weight,
            'bend_count' => $details?->bend_count,
            'finishing' => $details?->finishing,
            'cad_file' => $details?->cad_file,
            'notes' => $details?->internal_comment,
            'image_src' => $this->gedImage($line->files) ?? $this->gedImage($line->Product?->files)
                ?? $this->image($details?->picture, $line->Product),
            'planned_start' => $operations->pluck('start_date')->filter()->sort()->first(),
            'planned_end' => $operations->pluck('end_date')->filter()->sort()->last(),
            'operations' => $operations->map(fn (Task $task) => [
                'ordre' => $task->ordre,
                'label' => $task->label,
                'service' => $task->service?->code ?: $task->service?->label,
                'workcenter' => $task->machineResource->first()?->label ?: $task->service?->label,
                // Stockage en heures (TimeDisplay) : la fiche affiche en h/min.
                'planned_time_min' => (float) $task->TotalTime() * 60,
                'barcode_src' => $this->taskBarcode($task),
            ])->values()->all(),
            'components' => $line->BOM->map(fn (Task $task) => [
                'ordre' => $task->ordre,
                'label' => $task->label,
                'component' => trim(($task->Component?->code ?? '') . ' ' . ($task->Component?->label ?? '')),
                'qty' => $task->qty,
            ])->values()->all(),
        ];
    }

    /**
     * Code-barres de l'opération, au format réglé dans la fiche société : c'est
     * l'identifiant de la tâche que la douchette saisit dans l'écran de pointage.
     * Un échec de génération ne doit pas bloquer l'impression.
     */
    private function taskBarcode(Task $task): ?string
    {
        try {
            $png = \DNS1D::getBarcodePNG((string) $task->id, app('Factory')->task_barre_code ?: 'C128', 1, 30);
        } catch (\Throwable) {
            return null;
        }

        return $png ? 'data:image/png;base64,' . $png : null;
    }

    private function qrCode(string $payload): ?string
    {
        try {
            $png = \DNS2D::getBarcodePNG($payload, 'QRCODE', 3, 3);
        } catch (\Throwable) {
            return null;
        }

        return $png ? 'data:image/png;base64,' . $png : null;
    }

    /**
     * Aperçu de la pièce rangé dans la GED : une photo d'abord, sinon le dessin
     * vectoriel (SVG), sinon le plan DXF converti en SVG. À rang égal, le
     * fichier marqué principal l'emporte.
     */
    private function gedImage(?Collection $files): ?string
    {
        $rank = static function (File $file): ?int {
            $extension = strtolower((string) $file->extension);

            return match (true) {
                in_array($extension, self::RASTER, true) => $file->pivot?->role === FileRole::PHOTO ? 0 : 1,
                $extension === 'svg' => 2,
                $extension === 'dxf' => 3,
                default => null,
            };
        };

        $candidates = ($files ?? collect())
            ->filter(fn (File $file) => $rank($file) !== null)
            ->sortBy(fn (File $file) => [$rank($file), $file->pivot?->is_primary ? 0 : 1]);

        foreach ($candidates as $file) {
            $located = $this->files->locate($file);

            if ($located && ($uri = $this->dataUri((string) Storage::disk($located[0])->get($located[1]), $file->extension))) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * Anciennes colonnes : image de la ligne, sinon de l'article, puis son SVG
     * et son plan. Les fichiers publics ont pu être déplacés dans le stockage
     * privé (wem:files:import).
     */
    private function image(?string $linePicture, ?Products $product): ?string
    {
        $candidates = array_filter([
            $linePicture ? public_path('images/order-lines/' . basename($linePicture)) : null,
            $product?->picture ? public_path('images/products/' . basename($product->picture)) : null,
        ]);

        foreach ($candidates as $path) {
            if (is_file($path) && ($uri = $this->dataUri((string) @file_get_contents($path), pathinfo($path, PATHINFO_EXTENSION)))) {
                return $uri;
            }
        }

        foreach ([$product?->picture, $product?->svg_file, $product?->drawing_file] as $name) {
            $located = $this->files->locateLegacy($name);

            if ($located && ($uri = $this->dataUri((string) Storage::disk($located[0])->get($located[1]), pathinfo($located[1], PATHINFO_EXTENSION)))) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * php-svg-lib (moteur SVG de DomPDF) dessine toujours en unités du viewBox,
     * sans appliquer le changement d'échelle vers width / height : un export
     * CAO en width="100%" ou en mm débordait du cadre. On aligne donc width /
     * height sur le viewBox (origine ramenée à 0 0) ; la réduction au cadre
     * « Pièce » est laissée au max-width / max-height CSS de l'image.
     */
    private function fitSvg(string $svg): string
    {
        if (! preg_match('/<svg\b[^>]*>/i', $svg, $match)) {
            return $svg;
        }

        $tag = $match[0];
        $width = $height = null;
        $offset = null;

        if (preg_match('/viewBox\s*=\s*["\']\s*([-\d.e]+)[\s,]+([-\d.e]+)[\s,]+([\d.e]+)[\s,]+([\d.e]+)\s*["\']/i', $tag, $box)) {
            [$width, $height] = [(float) $box[3], (float) $box[4]];

            // php-svg-lib ignore l'origine du viewBox : un export CAO en
            // « -4.9 -79.9 … » sortait du cadre. On ramène l'origine à 0 0 en
            // décalant le dessin d'autant.
            if ((float) $box[1] != 0.0 || (float) $box[2] != 0.0) {
                $offset = [-(float) $box[1], -(float) $box[2]];
                $tag = str_replace($box[0], 'viewBox="0 0 ' . $box[3] . ' ' . $box[4] . '"', $tag);
            }
        } elseif (preg_match('/\swidth\s*=\s*["\']\s*([\d.]+)(?![\d.]*\s*%)/i', $tag, $w)
            && preg_match('/\sheight\s*=\s*["\']\s*([\d.]+)(?![\d.]*\s*%)/i', $tag, $h)) {
            [$width, $height] = [(float) $w[1], (float) $h[1]];
            // Sans viewBox, la taille fixe tronquerait le dessin au lieu de le réduire.
            $tag = preg_replace('/^<svg\b/i', '<svg viewBox="0 0 ' . $width . ' ' . $height . '"', $tag, 1);
        }

        if (! $width || ! $height) {
            return $svg;
        }

        $size = sprintf(' width="%s" height="%s"', $width, $height);

        $fitted = preg_replace('/\s(width|height)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $tag);
        $fitted = preg_replace('/^<svg\b/i', '<svg' . $size, $fitted, 1);

        if ($offset !== null) {
            $closing = strripos($svg, '</svg>');

            if ($closing === false) {
                return $svg;
            }

            $svg = substr($svg, 0, $closing) . '</g>' . substr($svg, $closing);
            $fitted .= sprintf('<g transform="translate(%s %s)">', $offset[0], $offset[1]);
        }

        return str_replace($match[0], $fitted, $svg);
    }

    private function dataUri(string $contents, ?string $extension): ?string
    {
        if ($contents === '') {
            return null;
        }

        $extension = strtolower((string) $extension);

        if ($extension === 'dxf') {
            try {
                $contents = (string) $this->dxfToSvg->render($contents);
            } catch (\Throwable) {
                return null;
            }

            $extension = 'svg';
        }

        if ($extension === 'svg') {
            $contents = $this->fitSvg($contents);
        }

        $mime = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => null, // webp, bmp… : DomPDF ne les affiche pas
        };

        return $mime && $contents !== '' ? 'data:' . $mime . ';base64,' . base64_encode($contents) : null;
    }
}
