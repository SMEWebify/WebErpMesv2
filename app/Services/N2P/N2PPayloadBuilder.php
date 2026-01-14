<?php

namespace App\Services\N2P;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\Log;

class N2PPayloadBuilder
{
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

        Log::info('N2P dates debug', [
            'dueDate_raw' => $dueDate,
            'plannedStart_raw' => $plannedStartAt,
            'plannedEnd_raw' => $plannedEndAt,
            'task_first_start' => optional($tasks->first())->start_date,
          ]);

          Log::info('N2P dates debug', [
            'dueDate_raw' => $dueDate,
            'plannedStart_raw' => $plannedStartAt,
            'plannedEnd_raw' => $plannedEndAt,
            'task_first_start' => optional($tasks->first())->start_date,
          ]);

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
            'required_qty' => (float) $orderLine->qty,
            'product_ref' => $product?->code ?? $orderLine->code,
            'material' => $details?->material ?? $product?->material,
            'thickness' => $this->nullableNumber($details?->thickness ?? $product?->thickness),
            'bend_count' => $details?->bend_count ?? $product?->bend_count,
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
            $operationCode = $task->code ?: ($task->service->code ?: ($task->label ?? null));
            if (!$operationCode) {
                $operationCode = Str::slug($task->service->code ?? 'task-' . $task->getKey());
            }

            $plannedTimeMinutes = null;
            if ($task->seting_time !== null || $task->unit_time !== null) {
                $plannedTimeMinutes = (int) max(0, round($task->TotalTime() * 60));
            }

            $workcenterCode = $task->MethodsTools?->code ?? $task->service?->code;

            return array_filter([
                'operation_code' => $operationCode,
                'workcenter_code' => $workcenterCode,
                'planned_start_at' => $this->nullableDateTime($task->start_date),
                'planned_end_at' => $this->nullableDateTime($task->end_date),
                'status' => "planned",
                'planned_time_min' => $plannedTimeMinutes,
                'required_qty' => (float) $orderLine->qty,
                'notes' => $task->comment ?? null,
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
}
