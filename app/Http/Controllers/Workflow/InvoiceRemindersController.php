<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Admin\EmailTemplate;
use App\Models\Workflow\InvoiceReminder;
use App\Models\Workflow\InvoiceReminderLevel;
use App\Services\Receivables\InvoiceReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Écran « Factures à relancer » et paramétrage des niveaux de relance.
 * L'envoi et le paramétrage exigent `invoice-reminders-send` (routes).
 */
class InvoiceRemindersController extends Controller
{
    public const SEND_PERMISSION = 'invoice-reminders-send';

    public function __construct(private readonly InvoiceReminderService $reminders) {}

    public function index(Request $request)
    {
        $reactEndpoints = [
            'list'    => route('invoices.reminders.json.list'),
            'preview' => route('invoices.reminders.preview'),
            'send'    => route('invoices.reminders.send'),
            'log'     => route('invoices.reminders.log'),
            'levels'  => route('invoices.reminders.levels'),
            'company' => route('companies.show', ['id' => '__ID__']),
            'invoice' => route('invoices.show', ['id' => '__ID__']),
        ];

        $reactTrans = __('receivables.reminders') + [
            'currency' => app('Factory')->curency ?? 'EUR',
            'locale'   => str_replace('_', '-', config('app.locale')),
        ];

        $canSend = (bool) $request->user()?->can(self::SEND_PERMISSION);

        return view('workflow/invoice-reminders', compact('reactEndpoints', 'reactTrans', 'canSend'));
    }

    public function listJson(Request $request): JsonResponse
    {
        $rows = $this->reminders->overdue();

        return response()->json([
            'data'   => $rows,
            'levels' => $this->reminders->levels()->map(fn ($l) => [
                'level' => $l->level, 'label' => $l->label, 'delay_days' => $l->delay_days, 'color' => $l->color,
            ])->values(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $this->validateSelection($request);

        return response()->json($this->reminders->preview($data['companies_id'], $data['invoice_ids']));
    }

    public function send(Request $request): JsonResponse
    {
        $data = $this->validateSelection($request, [
            'to'      => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $result = $this->reminders->send($data['companies_id'], $data['invoice_ids'], $data['to'], $data['subject'], $data['message']);

        return $result['sent']
            ? response()->json(['ok' => true, 'message' => __('receivables.reminders.sent_ok', ['to' => $data['to']])])
            : response()->json(['ok' => false, 'message' => __('receivables.reminders.send_failed', ['error' => $result['error']])], 502);
    }

    public function logManual(Request $request): JsonResponse
    {
        $data = $this->validateSelection($request, [
            'channel' => ['required', Rule::in([InvoiceReminder::CHANNEL_PHONE, InvoiceReminder::CHANNEL_LETTER])],
            'note'    => 'nullable|string|max:2000',
        ]);

        $count = $this->reminders->logManual($data['companies_id'], $data['invoice_ids'], $data['channel'], $data['note'] ?? null);

        return response()->json(['ok' => true, 'message' => __('receivables.reminders.logged_ok', ['count' => $count])]);
    }

    // -------------------------------------------------------------------------
    // Niveaux de relance
    // -------------------------------------------------------------------------

    public function levels()
    {
        return view('workflow/invoice-reminder-levels', [
            'levels'    => InvoiceReminderLevel::orderBy('level')->get(),
            'templates' => EmailTemplate::where('document_type', 'invoice-reminder')->orderBy('subject')->get(['id', 'subject']),
            'colors'    => InvoiceReminderLevel::COLORS,
        ]);
    }

    public function storeLevel(Request $request)
    {
        InvoiceReminderLevel::create($this->validateLevel($request));

        return redirect()->route('invoices.reminders.levels')->with('success', __('receivables.reminders.level_saved'));
    }

    public function updateLevel(Request $request, InvoiceReminderLevel $level)
    {
        $level->update($this->validateLevel($request, $level));

        return redirect()->route('invoices.reminders.levels')->with('success', __('receivables.reminders.level_saved'));
    }

    public function destroyLevel(InvoiceReminderLevel $level)
    {
        // Les relances et factures qui y renvoient passent à null (nullOnDelete).
        $level->delete();

        return redirect()->route('invoices.reminders.levels')->with('success', __('receivables.reminders.level_deleted'));
    }

    private function validateLevel(Request $request, ?InvoiceReminderLevel $level = null): array
    {
        return $request->validate([
            'level'             => ['required', 'integer', 'min:1', 'max:99', Rule::unique('invoice_reminder_levels', 'level')->ignore($level?->id)],
            'label'             => 'required|string|max:255',
            'delay_days'        => 'required|integer|min:0|max:3650',
            'email_template_id' => ['nullable', Rule::exists('email_templates', 'id')->where('document_type', 'invoice-reminder')],
            'color'             => ['required', Rule::in(InvoiceReminderLevel::COLORS)],
        ]);
    }

    /**
     * @return array{companies_id:int, invoice_ids:int[]}
     */
    private function validateSelection(Request $request, array $extra = []): array
    {
        $data = $request->validate(array_merge([
            'companies_id'  => 'required|integer',
            'invoice_ids'   => 'required|array|min:1|max:200',
            'invoice_ids.*' => 'integer',
        ], $extra));

        $data['companies_id'] = (int) $data['companies_id'];
        $data['invoice_ids']  = array_map('intval', $data['invoice_ids']);

        return $data;
    }
}
