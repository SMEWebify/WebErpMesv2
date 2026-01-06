<?php

namespace App\Services\N2P;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

        $product = $orderLine->Product;
        $details = $orderLine->OrderLineDetails;
        $company = $order->companie;

        $job = [
            'of_code' => $order->code ?? $order->uuid,
            'line_ref' => (string) $orderLine->getKey(),
            'required_qty' => (float) $orderLine->qty,
            'status' => $jobStatus,
            'priority' => $priority,
            'due_date' => $this->nullableDate($dueDate),
            'order_ref' => $order->code ?? null,
            'customer_code' => $company?->code,
            'customer_name' => $company?->label,
            'product_ref' => $product?->code ?? $orderLine->code,
            'material' => $details?->material ?? $product?->material,
            'thickness' => $this->nullableNumber($details?->thickness ?? $product?->thickness),
            'notes' => $orderLine->comment ?? $order->comment,
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
            $job['tasks'] = $this->mapTasks($orderLine->Task);
        }

        return array_filter($job, fn ($value) => !is_null($value) && $value !== '');
    }

    private function mapTasks($tasks): array
    {
        return $tasks->map(function (Task $task) {
            $operationCode = $task->code ?: ($task->operation_code ?? null);
            if (!$operationCode) {
                $operationCode = Str::slug($task->label ?? 'task-' . $task->getKey());
            }

            $plannedTimeMinutes = null;
            if ($task->seting_time !== null || $task->unit_time !== null) {
                $plannedTimeMinutes = (int) max(0, round($task->TotalTime() * 60));
            }

            $workcenterCode = $task->MethodsTools->code ?? null;

            return array_filter([
                'operation_code' => $operationCode,
                'workcenter_code' => $workcenterCode,
                'planned_start_at' => $this->nullableDateTime($task->start_date),
                'planned_end_at' => $this->nullableDateTime($task->end_date),
                'planned_time_min' => $plannedTimeMinutes,
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
        if (!$date) {
            return null;
        }

        return optional($date)->toDateString();
    }

    private function nullableDateTime($date): ?string
    {
        if (!$date) {
            return null;
        }

        return optional($date)->toDateTimeString();
    }

    private function nullableNumber($number): ?float
    {
        if ($number === null || $number === '') {
            return null;
        }

        return (float) $number;
    }
}
