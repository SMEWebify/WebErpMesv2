<?php

namespace App\Services\Documents;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\File;
use App\Services\Files\FileKindResolver;
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
    public function __construct(private FileStorageService $files)
    {
    }

    public function build(Orders $order): array
    {
        $lines = $order->OrderLines()
            ->articles()
            ->with([
                'Product:id,code,label,picture',
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
                ?? $this->image($details?->picture, $line->Product?->picture),
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
     * Photo rangée dans la GED : rôle « photo » d'abord, puis la principale,
     * parmi les fichiers image que DomPDF sait afficher.
     */
    private function gedImage(?Collection $files): ?string
    {
        $file = ($files ?? collect())
            ->filter(fn (File $file) => $file->kind === FileKindResolver::KIND_IMAGE
                && in_array(strtolower((string) $file->extension), ['png', 'jpg', 'jpeg', 'gif'], true))
            ->sortByDesc(fn (File $file) => [$file->pivot?->role === FileRole::PHOTO, (bool) $file->pivot?->is_primary])
            ->first();

        $located = $file ? $this->files->locate($file) : null;

        if (! $located) {
            return null;
        }

        [$disk, $path] = $located;

        return $this->dataUri((string) Storage::disk($disk)->get($path), $path . '.' . $file->extension);
    }

    /**
     * Image de la ligne, sinon celle de l'article. Les anciennes images publiques
     * ont pu être déplacées dans le stockage privé (wem:files:import).
     */
    private function image(?string $linePicture, ?string $productPicture): ?string
    {
        $candidates = array_filter([
            $linePicture ? public_path('images/order-lines/' . basename($linePicture)) : null,
            $productPicture ? public_path('images/products/' . basename($productPicture)) : null,
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $this->dataUri((string) @file_get_contents($path), $path);
            }
        }

        $located = $this->files->locateLegacy($productPicture, 'images/products');

        if ($located) {
            [$disk, $path] = $located;

            return $this->dataUri((string) Storage::disk($disk)->get($path), $path);
        }

        return null;
    }

    private function dataUri(string $contents, string $path): ?string
    {
        if ($contents === '') {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            default => null, // svg/webp mal rendus par DomPDF : on s'abstient
        };

        return $mime ? 'data:' . $mime . ';base64,' . base64_encode($contents) : null;
    }
}
