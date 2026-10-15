<?php

namespace App\Services\Receivables;

use App\Mail\InvoiceReminderMail;
use App\Models\EmailLog;
use App\Models\Workflow\InvoiceReminder;
use App\Models\Workflow\InvoiceReminderLevel;
use App\Models\Workflow\Invoices;
use App\Services\Documents\DocumentPdfService;
use App\Support\SafeHtml;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Relances des factures échues non soldées.
 *
 * Niveau proposé = le plus bas de :
 *  - le niveau atteint d'après le retard (dernier niveau dont delay_days ≤ retard) ;
 *  - le niveau suivant la dernière relance faite.
 * On ne saute donc jamais de niveau, et une facture déjà relancée au niveau
 * que justifie son retard n'est plus proposée.
 *
 * Aucun envoi automatique : chaque envoi part d'une action de l'utilisateur.
 * Seules les factures (invoice_type 1) sont relancées ; une proforma n'est
 * pas une créance, un avoir n'est pas dû, et aucun code ne crée encore de
 * facture d'acompte.
 */
class InvoiceReminderService
{
    public function __construct(
        private readonly ReceivablesService $receivables,
        private readonly DocumentPdfService $pdfService,
    ) {}

    /** @return Collection<int, InvoiceReminderLevel> triés par niveau */
    public function levels(): Collection
    {
        return InvoiceReminderLevel::with('emailTemplate')->orderBy('level')->get();
    }

    /**
     * @param int|null $currentLevel numéro du dernier niveau envoyé
     */
    public function proposedLevel(int $daysLate, ?int $currentLevel, Collection $levels): ?InvoiceReminderLevel
    {
        $reached = $levels->filter(fn ($l) => $daysLate >= $l->delay_days)->sortBy('level')->last();
        if ($reached === null) {
            return null;
        }

        $next = $levels->filter(fn ($l) => $currentLevel === null || $l->level > $currentLevel)->sortBy('level')->first();

        return $next !== null && $next->level <= $reached->level ? $next : null;
    }

    /**
     * Factures échues et non soldées, avec leur niveau proposé.
     *
     * @return Collection<int, array>
     */
    public function overdue(?int $companyId = null, ?Carbon $today = null): Collection
    {
        $today  = ($today ?? now())->copy()->startOfDay();
        $levels = $this->levels();

        $rows = $this->receivables->openInvoices($companyId)
            ->leftJoin('companies as c', 'c.id', '=', 'b.companies_id')
            ->leftJoin('companies_contacts as cc', 'cc.id', '=', 'b.companies_contacts_id')
            ->leftJoin('invoice_reminder_levels as rl', 'rl.id', '=', 'b.reminder_level_id')
            ->whereNotNull('b.due_date')
            ->where('b.due_date', '<', $today->toDateString())
            ->select(['b.*', 'c.label as company_label', 'cc.mail as contact_mail', 'rl.level as current_level', 'rl.label as current_level_label'])
            ->orderBy('c.label')
            ->orderBy('b.due_date')
            ->get();

        return $rows->map(function ($row) use ($today, $levels) {
            $daysLate     = (int) Carbon::parse($row->due_date)->startOfDay()->diffInDays($today);
            $currentLevel = $row->current_level !== null ? (int) $row->current_level : null;
            $proposed     = $this->proposedLevel($daysLate, $currentLevel, $levels);

            return [
                'id'                  => (int) $row->id,
                'code'                => $row->code,
                'label'               => $row->label,
                'companies_id'        => (int) $row->companies_id,
                'company_label'       => $row->company_label,
                'contact_mail'        => $row->contact_mail,
                'invoice_date'        => $row->created_at ? Carbon::parse($row->created_at)->toDateString() : null,
                'due_date'            => Carbon::parse($row->due_date)->toDateString(),
                'days_late'           => $daysLate,
                'amount_ttc'          => round((float) $row->amount_ttc, 2),
                'paid'                => round((float) $row->paid, 2),
                'credited'            => round((float) $row->credited, 2),
                'balance'             => round((float) $row->balance, 2),
                'current_level'       => $currentLevel,
                'current_level_label' => $row->current_level_label,
                'last_reminder_at'    => $row->last_reminder_at ? Carbon::parse($row->last_reminder_at)->toDateString() : null,
                'proposed_level'      => $proposed?->level,
                'proposed_level_id'   => $proposed?->id,
                'proposed_label'      => $proposed?->label,
                'proposed_color'      => $proposed?->color,
            ];
        })->values();
    }

    /**
     * Destinataire, objet et texte de la relance d'un client, avant envoi.
     *
     * @param int[] $invoiceIds
     */
    public function preview(int $companyId, array $invoiceIds): array
    {
        $rows  = $this->selectRows($companyId, $invoiceIds);
        $level = $this->groupLevel($rows);
        $template = $level?->emailTemplate;

        $subject = $template?->subject ?: __('receivables.reminders.default_subject');
        $content = $template?->content ?: __('receivables.reminders.default_content');

        return [
            'to'       => $rows->pluck('contact_mail')->filter()->first() ?? '',
            'subject'  => $this->render($subject, $rows, html: false),
            'message'  => $this->render($content, $rows, html: true),
            'level'    => $level?->level,
            'label'    => $level?->label,
            'invoices' => $rows->pluck('code')->all(),
        ];
    }

    /**
     * Envoie la relance par e-mail, factures jointes, et la trace sur chaque
     * facture (email_logs + invoice_reminders).
     *
     * @param int[] $invoiceIds
     * @return array{sent:bool, error:?string}
     */
    public function send(int $companyId, array $invoiceIds, string $to, string $subject, string $message): array
    {
        $rows    = $this->selectRows($companyId, $invoiceIds);
        $message = SafeHtml::clean($message);
        $invoices = Invoices::whereIn('id', $rows->pluck('id'))->get()->keyBy('id');

        // PDF d'abord : un document qui ne se génère pas ne part pas à moitié.
        $pdfs = [];
        foreach ($rows as $row) {
            try {
                $invoice = $invoices[$row['id']];
                $pdfs[]  = ['name' => $this->pdfService->fileName($invoice), 'bytes' => $this->pdfService->render($invoice)];
            } catch (Throwable $e) {
                throw ValidationException::withMessages([
                    'invoices' => __('receivables.reminders.pdf_failed', ['code' => $row['code'], 'error' => $e->getMessage()]),
                ]);
            }
        }

        $logs = [];
        foreach ($rows as $row) {
            $logs[$row['id']] = $invoices[$row['id']]->emailLogs()->create([
                'to'              => $to,
                'subject'         => $subject,
                'message'         => $message,
                'status'          => EmailLog::STATUS_PENDING,
                'sent_by_user_id' => Auth::id(),
            ]);
        }

        $user  = Auth::user();
        $error = null;
        try {
            Mail::to($to)->send(new InvoiceReminderMail(
                subjectText:    $subject,
                messageContent: $message,
                fromAddress:    (string) config('mail.from.address'),
                fromName:       (string) config('mail.from.name'),
                replyToAddress: $user?->email,
                replyToName:    $user?->name,
                pdfs:           $pdfs,
            ));
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $levels = $this->levels();
        DB::transaction(function () use ($rows, $logs, $error, $levels) {
            foreach ($rows as $row) {
                $logs[$row['id']]->update($error === null
                    ? ['status' => EmailLog::STATUS_SENT, 'sent_at' => now()]
                    : ['status' => EmailLog::STATUS_FAILED, 'error' => $error]);

                $this->record($row, $this->sendLevel($row, $levels), InvoiceReminder::CHANNEL_EMAIL, $error === null, $logs[$row['id']]->id, null);
            }
        });

        return ['sent' => $error === null, 'error' => $error];
    }

    /**
     * Relance faite hors de l'ERP (appel, courrier) : tracée et comptée
     * comme une relance du niveau proposé.
     *
     * @param int[] $invoiceIds
     */
    public function logManual(int $companyId, array $invoiceIds, string $channel, ?string $note): int
    {
        $rows   = $this->selectRows($companyId, $invoiceIds);
        $levels = $this->levels();

        DB::transaction(function () use ($rows, $levels, $channel, $note) {
            foreach ($rows as $row) {
                $this->record($row, $this->sendLevel($row, $levels), $channel, false, null, $note);
            }
        });

        return $rows->count();
    }

    /**
     * Lignes de la sélection, toutes du même client et toujours dues : une
     * facture soldée entre l'affichage et l'envoi n'est pas relancée.
     *
     * @param int[] $invoiceIds
     */
    private function selectRows(int $companyId, array $invoiceIds): Collection
    {
        $ids  = array_map('intval', $invoiceIds);
        $rows = $this->overdue($companyId)->whereIn('id', $ids)->values();

        if ($rows->isEmpty() || $rows->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'invoices' => __('receivables.reminders.not_overdue'),
            ]);
        }

        return $rows;
    }

    /** Niveau d'une relance groupée : le plus élevé des factures. */
    private function groupLevel(Collection $rows): ?InvoiceReminderLevel
    {
        $levels = $this->levels();

        return $rows->map(fn ($row) => $this->sendLevel($row, $levels))
            ->filter()
            ->sortBy('level')
            ->last();
    }

    /**
     * Niveau d'une facture à l'envoi : le niveau proposé, sinon (relance
     * répétée) le niveau déjà atteint, sinon le premier niveau.
     */
    private function sendLevel(array $row, Collection $levels): ?InvoiceReminderLevel
    {
        if ($row['proposed_level_id']) {
            return $levels->firstWhere('id', $row['proposed_level_id']);
        }
        if ($row['current_level'] !== null) {
            return $levels->firstWhere('level', $row['current_level']);
        }

        return $levels->first();
    }

    private function record(array $row, ?InvoiceReminderLevel $level, string $channel, bool $emailSent, ?int $emailLogId, ?string $note): void
    {
        InvoiceReminder::create([
            'invoice_id'        => $row['id'],
            'reminder_level_id' => $level?->id,
            'reminded_at'       => now(),
            'channel'           => $channel,
            'user_id'           => Auth::id(),
            'email_sent'        => $emailSent,
            'email_log_id'      => $emailLogId,
            'amount_due'        => $row['balance'],
            'days_late'         => $row['days_late'],
            'note'              => $note,
        ]);

        // Un e-mail en échec n'est pas une relance faite.
        if ($channel === InvoiceReminder::CHANNEL_EMAIL && ! $emailSent) {
            return;
        }

        $update = ['last_reminder_at' => now()];
        // Le niveau atteint ne redescend jamais.
        if ($level !== null && ($row['current_level'] === null || $level->level > $row['current_level'])) {
            $update['reminder_level_id'] = $level->id;
        }
        DB::table('invoices')->where('id', $row['id'])->update($update);
    }

    /**
     * Variables du modèle : {client}, {factures}, {total_du}, {retard},
     * {entreprise}. Valeurs échappées ; {factures} est un tableau HTML.
     */
    private function render(string $text, Collection $rows, bool $html): string
    {
        $esc = fn (?string $value) => $html ? e((string) $value) : (string) $value;

        $values = [
            '{client}'     => $esc($rows->first()['company_label'] ?? ''),
            '{total_du}'   => $esc($this->money($rows->sum('balance'))),
            '{retard}'     => (string) $rows->max('days_late'),
            '{entreprise}' => $esc(app('Factory')->name ?? config('app.name')),
            '{factures}'   => $html ? $this->invoicesTable($rows) : $rows->pluck('code')->implode(', '),
        ];

        return strtr($text, $values);
    }

    private function invoicesTable(Collection $rows): string
    {
        $style = 'border:1px solid #ccc;padding:4px 8px';
        $cell  = 'style="' . $style . '"';
        $head = collect(['invoice', 'date', 'due_date', 'days_late', 'balance'])
            ->map(fn ($k) => "<th $cell>" . e(__('receivables.reminders.table.' . $k)) . '</th>')
            ->implode('');

        $body = $rows->map(function ($row) use ($cell, $style) {
            return '<tr>'
                . "<td $cell>" . e($row['code']) . '</td>'
                . "<td $cell>" . e($row['invoice_date'] ? Carbon::parse($row['invoice_date'])->format('d/m/Y') : '') . '</td>'
                . "<td $cell>" . e(Carbon::parse($row['due_date'])->format('d/m/Y')) . '</td>'
                . "<td $cell>" . $row['days_late'] . '</td>'
                . '<td style="' . $style . ';text-align:right">' . e($this->money($row['balance'])) . '</td>'
                . '</tr>';
        })->implode('');

        return '<table style="border-collapse:collapse"><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table>';
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, app('Factory')->curency ?? 'EUR', config('app.locale'));
    }
}
