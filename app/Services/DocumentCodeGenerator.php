<?php

namespace App\Services;

use App\Models\DocumentCodeTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentCodeGenerator
{
    //Default template if none found for given document type
    protected $defaultTemplate = '{type}-{id}';

    /**
     * Generate a document code based on a template and the document type.
     *
     * This method generates a unique document code using a predefined template for the given document type.
     * It retrieves the template from the DocumentCodeTemplate model, replaces placeholders in the template
     * with dynamic values such as the current year, month, day, and an incremented ID, and returns the generated code.
     *
     * @param string $documentType The type of the document for which to generate the code.
     * @param int|null $lastId The last used ID for the document type, used to increment the new ID. Defaults to null.
     * @return string The generated document code.
     */
    public function generateDocumentCode(string $documentType, int $lastId = null)
    {
        // Retrieve the code template for the given document type
        $templateModel = DocumentCodeTemplate::getTemplateForDocument($documentType);
        $template = $templateModel?->template ?? $this->defaultTemplate;
        $resetPeriod = $templateModel?->reset_period ?? 'none';

        $id = $this->resolveNextSequence($documentType, $resetPeriod, $lastId);

        // Replace placeholders with dynamic values
        $code = str_replace('{year}', Carbon::now()->format('Y'), $template);
        $code = str_replace('{month}', Carbon::now()->format('m'), $code);
        $code = str_replace('{day}', Carbon::now()->format('d'), $code);
        $code = str_replace('{id}', $id, $code);
        $code = str_replace('{type}', strtoupper($documentType), $code);

        return $code;
    }

    protected function resolveNextSequence(string $documentType, string $resetPeriod, ?int $lastId): int
    {
        if (!in_array($resetPeriod, ['daily', 'weekly', 'monthly', 'none'], true)) {
            $resetPeriod = 'none';
        }

        $periodKey = $this->buildPeriodKey($resetPeriod);

        return DB::transaction(function () use ($documentType, $periodKey, $lastId, $resetPeriod) {
            $counter = DB::table('document_code_counters')
                ->where('document_type', $documentType)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $initialValue = 1;

                if ($resetPeriod === 'none' && $lastId !== null) {
                    $initialValue = $lastId + 1;
                }

                DB::table('document_code_counters')->insert([
                    'document_type' => $documentType,
                    'period_key' => $periodKey,
                    'current_value' => $initialValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $initialValue;
            }

            $nextValue = (int) $counter->current_value + 1;

            DB::table('document_code_counters')
                ->where('id', $counter->id)
                ->update([
                    'current_value' => $nextValue,
                    'updated_at' => now(),
                ]);

            return $nextValue;
        });
    }

    protected function buildPeriodKey(string $resetPeriod): string
    {
        $now = Carbon::now();

        return match ($resetPeriod) {
            'daily' => $now->format('Y-m-d'),
            'weekly' => sprintf('%s-W%02d', $now->isoWeekYear, $now->isoWeek),
            'monthly' => $now->format('Y-m'),
            default => 'global',
        };
    }
}
