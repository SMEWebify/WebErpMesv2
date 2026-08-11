<?php

namespace App\Services\N2P;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;

class N2PPayloadBuilder
{
    /**
     * Prefix used to build a deterministic operation_code when task.code is empty.
     * The return channel (Task::resolveByExternalRef) recognises it to reverse-lookup.
     */
    public const OPERATION_CODE_FALLBACK_PREFIX = 'op-';

    public function build(Orders $order, array $settings): array
    {
        $jobs = [];
        $jobStatus = Arr::get($settings, 'n2p_job_status_on_send', 'released');
        $defaultPriority = (int) Arr::get($settings, 'n2p_priority_default', 3);
        $sendTasks = (bool) Arr::get($settings, 'n2p_send_tasks', true);

        foreach ($order->OrderLines as $orderLine) {
            $jobs[] = $this->buildJob($order, $orderLine, $jobStatus, $defaultPriority, $sendTasks);
        }

        return [
            'jobs' => array_values(array_filter($jobs)),
        ];
    }

    private function buildJob(Orders $order, OrderLines $orderLine, string $jobStatus, int $defaultPriority, bool $sendTasks): array
    {
        $priority = $this->clampPriority($order->priority ?? $defaultPriority);

        $dueDate = $orderLine->delivery_date ?? $order->validity_date ?? null;
        $tasks = $orderLine->Task ?? collect();
        $plannedStartAt = optional(
            $tasks
                ->filter(fn (Task $task) => (bool) $task->start_date)
                ->sortBy('start_date')
                ->first()
        )->start_date;
        $plannedEndAt = optional(
            $tasks
                ->filter(fn (Task $task) => (bool) $task->end_date)
                ->sortByDesc('end_date')
                ->first()
        )->end_date;

        $product = $orderLine->Product;
        $details = $orderLine->OrderLineDetails;
        $company = $order->companie;

        $job = [
            'of_code' => "OF" . $orderLine->id,
            'line_ref' => (string) $orderLine->getKey(),
            'status' => $jobStatus,
            'priority' => $priority,
            'due_date' => $this->nullableDate($dueDate),
            "alias_erp" => $product?->code ?? $orderLine->code,
            'customer_code' => $company?->code,
            'customer_name' => $company?->label,
            'order_ref' => $order->code ?? null,
            "label"=> $orderLine->label,
            "cad_file_path"=> $details?->cad_file_path ?? $product?->cad_file_path,
            "cam_file_path"=> $details?->cam_file_path ?? $product?->cam_file_path,
            'picture_base64' => $this->pictureBase64($details?->picture, $product?->picture),
            'required_qty' => (float) $orderLine->qty,
            'product_ref' => $product?->code ?? $orderLine->code,
            'material' => $details?->material ?? $product?->material,
            'thickness' => $this->nullableNumber($details?->thickness ?? $product?->thickness),
            'bend_count' => $details?->bend_count ?? $product?->bend_count,
            'part_type' => $this->resolvePartType($orderLine, $details, $product),
            'dimension_x' => $this->nullableNumber($details?->x_size ?? $product?->x_size),
            'dimension_y' => $this->nullableNumber($details?->y_size ?? $product?->y_size),
            'dimension_z' => $this->nullableNumber($details?->z_size ?? $product?->z_size),
            'weight' => $this->nullableNumber($details?->weight ?? $product?->weight),
            'notes' => $orderLine->comment ?? $order->comment,
            'planned_start_at' => $this->nullableDateTime($plannedStartAt),
            'planned_end_at' => $this->nullableDateTime($plannedEndAt),
        ];

        if (!$job['due_date']) {
            unset($job['due_date']);
        }

        if ($job['thickness'] === null) {
            unset($job['thickness']);
        }

        if (!$job['notes']) {
            unset($job['notes']);
        }


        if ($sendTasks) {
            $tasksPayload = $this->mapTasks($orderLine, $orderLine->Task);
        
            $job['tasks'] = $tasksPayload;
        }

        return array_filter($job, fn ($value) => !is_null($value) && $value !== '');
    }

    private function mapTasks(OrderLines $orderLine, $tasks): array
    {
        return $tasks->map(function (Task $task) use ($orderLine) {
            $operationCode = $task->code ?: self::OPERATION_CODE_FALLBACK_PREFIX . $task->getKey();

            $plannedTimeMinutes = null;
            if ($task->seting_time !== null || $task->unit_time !== null) {
                $calculated = (int) max(0, round($task->TotalTime() * 60));
                $plannedTimeMinutes = $calculated > 0 ? $calculated : null;
            }

            $workcenterCode = $task->MethodsTools?->code ?? $task->service?->code;
            $requiredQty = $this->nullableNumber($task->qty ?? $task->qty_init ?? $orderLine->qty);

            return array_filter([
                'operation_code' => $operationCode,
                'workcenter_code' => $workcenterCode,
                'planned_start_at' => $this->nullableDateTime($task->start_date),
                'planned_end_at' => $this->nullableDateTime($task->end_date),
                'status' => 'planned',
                'planned_time_min' => $plannedTimeMinutes,
                'required_qty' => $requiredQty,
            ], fn ($value) => !is_null($value) && $value !== '');
        })->values()->all();
    }

    private function clampPriority(?int $priority): int
    {
        $priority = $priority ?? 3;
        return max(1, min(5, $priority));
    }

    private function nullableDate($date): ?string
    {
        if (!$date || $date === '') {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableDateTime($date): ?string
    {
        if (!$date || $date === '') {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableNumber($number): ?float
    {
        if ($number === null || $number === '') {
            return null;
        }

        return (float) $number;
    }

    private function resolvePartType(OrderLines $orderLine, $details, $product): string
    {
        $explicit = $details?->part_type ?? $product?->part_type ?? null;
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if ($this->hasSubAssemblies($orderLine, $product)) {
            return 'Assembly';
        }

        $cadFile = $details?->cad_file_path ?? $product?->cad_file_path;
        $camFile = $details?->cam_file_path ?? $product?->cam_file_path;
        if ($this->isSymFile($camFile) || $this->isSymFile($cadFile)) {
            return 'SymbolPart';
        }

        if ($product instanceof Products && (int) $product->purchased === 1) {
            return 'StandardPart';
        }

        $thickness = $details?->thickness ?? $product?->thickness;
        if ((float) $thickness > 0 || !empty($cadFile) || !empty($camFile)) {
            return 'SymbolPart';
        }

        return 'StandardPart';
    }

    private function isSymFile(?string $path): bool
    {
        if (!is_string($path) || $path === '') {
            return false;
        }

        return str_ends_with(strtolower($path), '.sym');
    }

    private function hasSubAssemblies(OrderLines $orderLine, $product): bool
    {
        if ($orderLine->relationLoaded('SubAssembly')
            && $orderLine->getRelation('SubAssembly')?->isNotEmpty()) {
            return true;
        }

        if ($product instanceof Products
            && $product->relationLoaded('SubAssembly')
            && $product->getRelation('SubAssembly')?->isNotEmpty()) {
            return true;
        }

        return false;
    }

    private function pictureBase64(?string $detailPicture, ?string $productPicture): ?string
    {
        $candidates = array_filter([
            $detailPicture  ? public_path('images/order-lines/' . $detailPicture) : null,
            $detailPicture  ? public_path('images/quote-lines/' . $detailPicture) : null,
            $productPicture ? public_path('images/products/' . $productPicture)   : null,
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $data = @file_get_contents($path);
                if ($data !== false) {
                    return base64_encode($data);
                }
            }
        }

        return null;
    }
}
