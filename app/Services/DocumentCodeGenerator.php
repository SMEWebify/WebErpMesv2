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
     * Table dont la colonne `code` (unique) porte les codes du type. Un écran propose le code
     * lu par peekNextCode() et l'enregistre tel quel ; tant que ce code n'était pas consommé,
     * le compteur restait en retard et le code suivant était déjà pris (422 à la saisie, 500
     * pour une facture créée depuis un BL). Les codes déjà pris sont donc sautés.
     */
    protected const CODE_TABLES = [
        'invoice'        => 'invoices',
        'proforma'       => 'invoices',
        'order'          => 'orders',
        'internal-order' => 'orders',
        'delivery'       => 'deliverys',
    ];

    /** Garde-fou : un modèle sans {id} donne toujours le même code. */
    protected const MAX_SKIPPED = 1000;

    public function peekNextCode(string $documentType): string
    {
        $templateModel = DocumentCodeTemplate::getTemplateForDocument($documentType);
        $template      = $templateModel?->template ?? $this->defaultTemplate;
        $resetPeriod   = $templateModel?->reset_period ?? 'none';
        $resetMonth    = (int) ($templateModel?->yearly_reset_month ?? 1);
        $resetDay      = (int) ($templateModel?->yearly_reset_day ?? 1);
        $idPadding     = (int) ($templateModel?->id_padding ?? 0);

        $periodKey = $this->buildPeriodKey($resetPeriod, $resetMonth, $resetDay);

        $counter = DB::table('document_code_counters')
            ->where('document_type', $documentType)
            ->where('period_key', $periodKey)
            ->first();

        $nextId = $counter ? (int) $counter->current_value + 1 : 1;
        $code   = $this->applyTemplate($template, $nextId, $idPadding, $documentType);

        for ($i = 0; $i < self::MAX_SKIPPED && $this->isTaken($documentType, $code); $i++) {
            $code = $this->applyTemplate($template, ++$nextId, $idPadding, $documentType);
        }

        return $code;
    }

    /**
     * Le code enregistré est celui que l'écran proposait : il est consommé, pour que le suivant
     * ne soit pas le même. Un code saisi à la main ne touche pas au compteur.
     */
    public function consumeIfProposed(string $documentType, ?string $code): void
    {
        if ($code !== null && $code === $this->peekNextCode($documentType)) {
            $this->generateDocumentCode($documentType);
        }
    }

    public function generateDocumentCode(string $documentType, ?int $lastId = null)
    {
        $templateModel = DocumentCodeTemplate::getTemplateForDocument($documentType);
        $template      = $templateModel?->template ?? $this->defaultTemplate;
        $resetPeriod   = $templateModel?->reset_period ?? 'none';
        $resetMonth    = (int) ($templateModel?->yearly_reset_month ?? 1);
        $resetDay      = (int) ($templateModel?->yearly_reset_day ?? 1);
        $idPadding     = (int) ($templateModel?->id_padding ?? 0);

        $id   = $this->resolveNextSequence($documentType, $resetPeriod, $lastId, $resetMonth, $resetDay);
        $code = $this->applyTemplate($template, $id, $idPadding, $documentType);

        for ($i = 0; $i < self::MAX_SKIPPED && $this->isTaken($documentType, $code); $i++) {
            $id   = $this->resolveNextSequence($documentType, $resetPeriod, $lastId, $resetMonth, $resetDay);
            $code = $this->applyTemplate($template, $id, $idPadding, $documentType);
        }

        return $code;
    }

    protected function isTaken(string $documentType, string $code): bool
    {
        $table = self::CODE_TABLES[$documentType] ?? null;

        return $table !== null && DB::table($table)->where('code', $code)->exists();
    }

    protected function applyTemplate(string $template, int $id, int $idPadding, string $documentType): string
    {
        $now = Carbon::now();

        // New tokens — longer tokens first to avoid partial collision ({yyyy} before {yy})
        $code = str_replace('{yyyy}', $now->format('Y'),                                    $template);
        $code = str_replace('{yy}',   $now->format('y'),                                    $code);
        $code = str_replace('{mm}',   $now->format('m'),                                    $code);
        $code = str_replace('{m}',    $now->format('n'),                                    $code);
        $code = str_replace('{dd}',   $now->format('d'),                                    $code);
        $code = str_replace('{d}',    $now->format('j'),                                    $code);
        $code = str_replace('{ww}',   str_pad($now->format('W'), 2, '0', STR_PAD_LEFT),    $code);
        $code = str_replace('{w}',    (string) (int) $now->format('W'),                     $code);

        // Legacy aliases (kept for backward compatibility)
        $code = str_replace('{year}',  $now->format('Y'), $code);
        $code = str_replace('{month}', $now->format('m'), $code);
        $code = str_replace('{day}',   $now->format('d'), $code);
        $code = str_replace('{week}',  $now->format('W'), $code);
        $code = str_replace('{type}',  strtoupper($documentType), $code);

        // Inline {id(N)} — must be replaced before plain {id}
        $code = preg_replace_callback('/\{id\((\d+)\)\}/', function ($matches) use ($id) {
            return str_pad((string) $id, (int) $matches[1], '0', STR_PAD_LEFT);
        }, $code);

        // Plain {id} uses legacy id_padding field
        $formattedId = $idPadding > 0
            ? str_pad((string) $id, $idPadding, '0', STR_PAD_LEFT)
            : (string) $id;

        return str_replace('{id}', $formattedId, $code);
    }

    protected function resolveNextSequence(string $documentType, string $resetPeriod, ?int $lastId = null, int $resetMonth = 1, int $resetDay = 1): int
    {
        if (!\in_array($resetPeriod, ['daily', 'weekly', 'monthly', 'yearly', 'none'], true)) {
            $resetPeriod = 'none';
        }

        $periodKey = $this->buildPeriodKey($resetPeriod, $resetMonth, $resetDay);

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

    protected function buildPeriodKey(string $resetPeriod, int $resetMonth = 1, int $resetDay = 1): string
    {
        $now = Carbon::now();

        return match ($resetPeriod) {
            'daily'   => $now->format('Y-m-d'),
            'weekly'  => \sprintf('%s-W%02d', $now->isoWeekYear, $now->isoWeek),
            'monthly' => $now->format('Y-m'),
            'yearly'  => $this->buildFiscalYearKey($now, $resetMonth, $resetDay),
            default   => 'global',
        };
    }

    protected function buildFiscalYearKey(Carbon $now, int $month, int $day): string
    {
        $fiscalStart = Carbon::create($now->year, $month, $day, 0, 0, 0);

        if ($now->lt($fiscalStart)) {
            $fiscalStart->subYear();
        }

        return $fiscalStart->format('Y-m-d');
    }
}
