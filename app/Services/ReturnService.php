<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Workflow\Returns;
use App\Models\Workflow\ReturnLines;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\ReturnNotification;

class ReturnService
{
    public function __construct(
        protected DocumentCodeGenerator $documentCodeGenerator,
        protected NotificationService $notificationService
    ) {
    }

    public function registerReturn(array $data, array $lines): Returns
    {
        return DB::transaction(function () use ($data, $lines) {
            $lastReturn = Returns::latest('id')->first();
            $code = $data['code'] ?? $this->documentCodeGenerator->generateDocumentCode('return', $lastReturn?->id);
            $label = $data['label'] ?? $code;

            $return = Returns::create([
                'code' => $code,
                'label' => $label,
                'statu' => $data['statu'] ?? 1,
                'deliverys_id' => $data['deliverys_id'] ?? null,
                'quality_non_conformity_id' => $data['quality_non_conformity_id'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
                'customer_report' => $data['customer_report'] ?? null,
                'received_at' => $data['received_at'] ?? Carbon::now(),
            ]);

            foreach ($lines as $line) {
                if (! $this->shouldPersistLine($line)) {
                    continue;
                }

                ReturnLines::create([
                    'return_id' => $return->id,
                    'delivery_line_id' => $line['delivery_line_id'] ?? null,
                    'original_task_id' => $line['original_task_id'] ?? null,
                    'qty' => $line['qty'] ?? null,
                    'issue_description' => $line['issue_description'] ?? null,
                    'rework_instructions' => $line['rework_instructions'] ?? null,
                ]);
            }

            $this->notifyUsers($return);

            return $return->fresh(['lines', 'delivery', 'qualityNonConformity']);
        });
    }

    public function diagnose(Returns $return, array $data): Returns
    {
        return DB::transaction(function () use ($return, $data) {
            $return->diagnosis = $data['diagnosis'] ?? $return->diagnosis;
            $return->customer_report = $data['customer_report'] ?? $return->customer_report;
            $return->diagnosed_by = $data['diagnosed_by'] ?? Auth::id();
            $return->diagnosed_at = $data['diagnosed_at'] ?? Carbon::now();
            $return->statu = 2;
            $return->save();

            return $return->fresh(['lines', 'delivery', 'qualityNonConformity']);
        });
    }

    public function createReworkTasks(Returns $return): array
    {
        return DB::transaction(function () use ($return) {
            $return->loadMissing('lines');
            $createdTasks = [];
            $status = Status::whereIn('title', ['In progress', 'To schedule', 'Planned'])->orderBy('order')->first()
                ?? Status::orderBy('order')->first();

            foreach ($return->lines as $line) {
                if (! $line->original_task_id) {
                    continue;
                }

                $originalTask = Task::find($line->original_task_id);

                if (! $originalTask) {
                    continue;
                }

                $newTask = $originalTask->replicate();
                $newTask->status_id = $status?->id ?? $originalTask->status_id;
                $newTask->origin = 'return:' . $return->code;

                if (! empty($line->qty)) {
                    $newTask->qty = $line->qty;
                    $newTask->qty_init = $line->qty;
                    $newTask->qty_aviable = $line->qty;
                }

                $newTask->created_at = Carbon::now();
                $newTask->updated_at = Carbon::now();
                $newTask->save();

                $line->rework_task_id = $newTask->id;
                $line->save();

                $createdTasks[] = $newTask;
            }

            if (! empty($createdTasks)) {
                $return->update(['statu' => 3]);
            }

            return $createdTasks;
        });
    }

    public function close(Returns $return, array $data = []): Returns
    {
        return DB::transaction(function () use ($return, $data) {
            $return->resolution_notes = $data['resolution_notes'] ?? $return->resolution_notes;
            $return->closed_at = $data['closed_at'] ?? Carbon::now();
            $return->statu = 4;
            $return->save();

            $this->notifyUsers($return->fresh(), 'return_notification');

            return $return->fresh(['lines', 'delivery', 'qualityNonConformity']);
        });
    }

    protected function shouldPersistLine(array $line): bool
    {
        return (bool) (($line['delivery_line_id'] ?? null)
            || ($line['original_task_id'] ?? null)
            || ($line['issue_description'] ?? null)
            || ($line['rework_instructions'] ?? null));
    }

    protected function notifyUsers(Returns $return, string $column = 'return_notification'): void
    {
        $payload = [
            'id' => $return->id,
            'code' => $return->code,
            'user_id' => $return->created_by ?? Auth::id(),
            'statu' => $return->statu,
        ];

        $this->notificationService->sendNotification(ReturnNotification::class, $payload, $column);
    }
}
