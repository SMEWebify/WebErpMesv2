<?php

namespace App\Services\Affairs;

use App\Enums\AffairStage;
use App\Models\EmailLog;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Purchases\PurchaseLines;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunityVisits;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Synthèse d'une affaire (opportunité), en lecture seule : étape du parcours,
 * blocages, prochaine action, compteurs et montants.
 *
 * Les blocages sont des règles explicites et testées, pas une appréciation de
 * l'IA : le dirigeant doit pouvoir comprendre pourquoi une alerte s'affiche.
 *
 * Nombre de requêtes constant (9), quelle que soit la taille de l'affaire :
 * chaque niveau est chargé en une fois par whereIn sur le niveau précédent,
 * et les montants sont agrégés en SQL ou à partir des lignes déjà chargées.
 * Les lectures passent par toBase() : les accesseurs Eloquent (prix calculé,
 * totaux formatés) déclencheraient une requête par ligne.
 *
 * Les sections masquées (permissions de l'utilisateur) disparaissent de la
 * réponse avec leurs blocages, montants et actions ; l'étape reste calculée
 * sur toute l'affaire.
 */
class AffairSummaryService
{
    /** Sections soumises à une permission de menu. Les visites suivent l'opportunité. */
    public const SECTIONS = ['quotes', 'orders', 'purchases', 'invoices'];

    private const SEVERITY_RANK = ['danger' => 0, 'warning' => 1, 'info' => 2];

    private const BLOCKER_ACTIONS = [
        'quote_expired'     => 'renew_quote',
        'quote_unanswered'  => 'follow_up_quote',
        'purchase_late'     => 'chase_supplier',
        'purchase_partial'  => 'chase_supplier',
        'task_suspended'    => 'resume_task',
        'order_late'        => 'update_delivery_date',
        'invoice_overdue'   => 'chase_payment',
        'visit_unvalidated' => 'finish_visit',
    ];

    // quotes.statu
    private const QUOTE_OPEN = 1;
    private const QUOTE_SENT = 2;
    private const QUOTE_WON = 3;
    private const QUOTE_DEAD = [4, 6]; // perdu, obsolète

    // orders.statu
    private const ORDER_CANCELED = 6;
    private const ORDER_INACTIVE = [5, 6]; // arrêtée, annulée

    // purchases.statu
    private const PURCHASE_DRAFT = 1;
    private const PURCHASE_AWAITING = [2, 3]; // commandé, partiellement reçu
    private const PURCHASE_CANCELED = 5;

    // invoices.statu / invoice_type
    private const INVOICE_PAID = 5;
    private const INVOICE_DRAFT = 1;
    private const INVOICE_TYPES_BILLED = [1, 4]; // facture, acompte
    private const INVOICE_TYPE_CREDIT = 2;

    // opportunities.statu
    private const OPPORTUNITY_LOST = 5;

    /**
     * @param string[]|null $visibleSections sections autorisées (null = toutes)
     */
    public function summarize(Opportunities $opportunity, ?array $visibleSections = null, ?CarbonInterface $now = null): array
    {
        $now     = $now ? Carbon::instance($now) : Carbon::now();
        $today   = $now->copy()->startOfDay();
        $visible = array_fill_keys($visibleSections ?? self::SECTIONS, true);
        $visible['visits'] = true;

        $quotes        = $this->loadQuotes($opportunity);
        $orders        = $this->loadOrders($quotes->pluck('id')->all());
        $lines         = $this->loadOrderLines($orders->pluck('id')->all());
        $tasks         = $this->loadTasks($lines);
        $purchaseLines = $this->loadPurchaseLines($tasks->pluck('id')->all());
        $invoices      = $this->loadInvoices($orders->pluck('id')->all(), $lines->pluck('id')->all());
        $visits        = OpportunityVisits::query()
            ->where('opportunities_id', $opportunity->id)
            ->orderBy('visited_at')
            ->toBase()->get(['id', 'statu', 'visited_at']);
        $onsiteEvent   = OpportunitiesEventsLogs::query()
            ->where('opportunities_id', $opportunity->id)
            ->where('type', 3) // visite sur site saisie à la main
            ->exists();

        $linesByOrder = $lines->groupBy('orders_id');
        $tasksByOrder = $tasks->groupBy('orders_id');

        $orders = $orders->map(function ($order) use ($linesByOrder, $tasksByOrder) {
            $orderLines = $linesByOrder->get($order->id, collect());
            $orderTasks = $tasksByOrder->get($order->id, collect());
            $count      = $orderLines->count();

            $order->amount        = round((float) $orderLines->sum('amount'), 2);
            $order->line_count    = $count;
            $order->delivered_pct = $count ? (int) round(100 * $orderLines->where('delivery_status', '>=', 3)->count() / $count) : 0;
            $order->invoiced_pct  = $count ? (int) round(100 * $orderLines->where('invoice_status', 3)->count() / $count) : 0;
            $order->hours_planned = round((float) $orderTasks->sum('planned_hours'), 2);
            $order->hours_actual  = round((float) $orderTasks->sum('actual_hours'), 2);
            $order->next_delivery_date = $orderLines
                ->filter(fn ($l) => (int) $l->delivery_status < 3 && $l->due_date)
                ->min('due_date');

            return $order;
        });

        $blockers = $this->detectBlockers($quotes, $orders, $lines, $tasks, $purchaseLines, $invoices, $visits, $now, $today);
        $stage    = $this->resolveStage($opportunity, $quotes, $orders, $lines, $tasks, $invoices, $visits, $onsiteEvent);

        $blockers = array_values(array_filter($blockers, fn ($b) => isset($visible[$b['section']])));
        usort($blockers, fn ($a, $b) => [self::SEVERITY_RANK[$a['severity']], $a['date'] ?? ''] <=> [self::SEVERITY_RANK[$b['severity']], $b['date'] ?? '']);

        $nextAction = $blockers !== []
            ? [
                'code'    => self::BLOCKER_ACTIONS[$blockers[0]['code']],
                'ref'     => $blockers[0]['ref'],
                'url'     => $blockers[0]['url'],
                'blocker' => $blockers[0]['code'],
            ]
            : $this->stageAction($stage, $opportunity, $quotes, $orders, $lines, $tasks, $purchaseLines, $invoices, $visits, $visible);

        $billed     = $invoices->whereIn('invoice_type', self::INVOICE_TYPES_BILLED);
        $credits    = $invoices->where('invoice_type', self::INVOICE_TYPE_CREDIT);
        $activeLines = $purchaseLines->where('purchase_statu', '!=', self::PURCHASE_CANCELED);

        $totals = [
            'quoted'        => round((float) $quotes->whereNotIn('statu', self::QUOTE_DEAD)->sum('amount'), 2),
            'ordered'       => round((float) $orders->where('statu', '!=', self::ORDER_CANCELED)->sum('amount'), 2),
            'purchased'     => round((float) $activeLines->sum('total_selling_price'), 2),
            'hours_planned' => round((float) $tasks->sum('planned_hours'), 2),
            'hours_actual'  => round((float) $tasks->sum('actual_hours'), 2),
            'invoiced'      => round((float) $billed->sum('amount_ht') - (float) $credits->sum('amount_ht'), 2),
            'invoiced_ttc'  => round((float) $billed->sum('amount_ttc') - (float) $credits->sum('amount_ttc'), 2),
            'collected'     => round((float) $billed->sum('paid'), 2),
            'outstanding'   => round((float) $billed->sum('remaining'), 2),
        ];

        $totalSections = [
            'quoted' => 'quotes', 'ordered' => 'orders', 'hours_planned' => 'orders', 'hours_actual' => 'orders',
            'purchased' => 'purchases', 'invoiced' => 'invoices', 'invoiced_ttc' => 'invoices',
            'collected' => 'invoices', 'outstanding' => 'invoices',
        ];
        foreach ($totalSections as $key => $section) {
            if (!isset($visible[$section])) {
                $totals[$key] = null;
            }
        }

        $sections = [
            'quotes'    => $quotes->map(fn ($q) => [
                'id'            => (int) $q->id,
                'code'          => $q->code,
                'label'         => $q->label,
                'statu'         => (int) $q->statu,
                'amount'        => round((float) $q->amount, 2),
                'validity_date' => $this->date($q->validity_date),
                'sent_at'       => $this->date($q->last_sent_at),
                'url'           => route('quotes.show', $q->id),
            ])->values()->all(),
            'orders'    => $orders->map(fn ($o) => [
                'id'                 => (int) $o->id,
                'code'               => $o->code,
                'label'              => $o->label,
                'statu'              => (int) $o->statu,
                'amount'             => $o->amount,
                'next_delivery_date' => $this->date($o->next_delivery_date),
                'delivered_pct'      => $o->delivered_pct,
                'invoiced_pct'       => $o->invoiced_pct,
                'hours_planned'      => $o->hours_planned,
                'hours_actual'       => $o->hours_actual,
                'url'                => route('orders.show', $o->id),
            ])->values()->all(),
            'purchases' => $purchaseLines->groupBy('purchases_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'id'                 => (int) $first->purchases_id,
                    'code'               => $first->purchase_code,
                    'statu'              => (int) $first->purchase_statu,
                    'amount'             => round((float) $group->sum('total_selling_price'), 2),
                    'lines'              => $group->count(),
                    'received_lines'     => $group->filter(fn ($l) => (float) $l->receipt_qty >= (float) $l->qty)->count(),
                    'next_delivery_date' => $this->date($group->filter(fn ($l) => (float) $l->receipt_qty < (float) $l->qty)->min('delivery_date')),
                    'url'                => route('purchases.show', $first->purchases_id),
                ];
            })->values()->all(),
            'invoices'  => $invoices->map(fn ($i) => [
                'id'           => (int) $i->id,
                'code'         => $i->code,
                'label'        => $i->label,
                'statu'        => (int) $i->statu,
                'invoice_type' => (int) $i->invoice_type,
                'due_date'     => $this->date($i->due_date),
                'amount_ht'    => round((float) $i->amount_ht, 2),
                'amount_ttc'   => round((float) $i->amount_ttc, 2),
                'paid'         => round((float) $i->paid, 2),
                'remaining'    => round((float) $i->remaining, 2),
                'url'          => route('invoices.show', $i->id),
            ])->values()->all(),
            'visits'    => $visits->map(fn ($v) => [
                'id'         => (int) $v->id,
                'statu'      => (int) $v->statu,
                'visited_at' => $this->date($v->visited_at, 'Y-m-d H:i'),
                'url'        => route('opportunities.visit', ['id' => $opportunity->id, 'visit' => $v->id]),
            ])->values()->all(),
        ];
        $sections = array_intersect_key($sections, $visible);

        return [
            'stage'       => $stage->value,
            'path'        => AffairStage::path(),
            'blockers'    => $blockers,
            'next_action' => $nextAction,
            'totals'      => $totals,
            'counts'      => array_map('count', $sections) + ['tasks' => isset($visible['orders']) ? $tasks->count() : null],
            'sections'    => $sections,
            'generated_at' => $now->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------
    // Chargement (une requête par niveau)
    // ------------------------------------------------------------------

    private function loadQuotes(Opportunities $opportunity): Collection
    {
        return Quotes::query()
            ->where('opportunities_id', $opportunity->id)
            ->select(['quotes.id', 'quotes.code', 'quotes.label', 'quotes.statu', 'quotes.validity_date', 'quotes.created_at', 'quotes.updated_at'])
            ->selectSub(
                QuoteLines::query()
                    ->selectRaw('COALESCE(SUM(ROUND(qty * selling_price * (1 - COALESCE(discount, 0) / 100.0), 2)), 0)')
                    ->whereColumn('quote_lines.quotes_id', 'quotes.id')
                    ->articles(),
                'amount'
            )
            ->selectSub(
                // Les journaux antérieurs à la colonne status sont restés « pending » :
                // seul un échec explicite écarte l'envoi.
                EmailLog::query()
                    ->selectRaw('MAX(COALESCE(sent_at, created_at))')
                    ->whereColumn('email_logs.emailable_id', 'quotes.id')
                    ->where('email_logs.emailable_type', Quotes::class)
                    ->where('email_logs.status', '!=', 'failed'),
                'last_sent_at'
            )
            ->orderBy('quotes.id')
            ->toBase()
            ->get();
    }

    /** @param int[] $quoteIds */
    private function loadOrders(array $quoteIds): Collection
    {
        if ($quoteIds === []) {
            return collect();
        }

        return Orders::query()
            ->whereIn('quotes_id', $quoteIds)
            ->where('type', 1)
            ->orderBy('id')
            ->toBase()
            ->get(['id', 'code', 'label', 'statu', 'quotes_id', 'validity_date', 'created_at']);
    }

    /**
     * Lignes article : une ligne de présentation ne se livre ni ne se facture.
     * due_date = date de livraison de la ligne, à défaut celle de la commande.
     *
     * @param int[] $orderIds
     */
    private function loadOrderLines(array $orderIds): Collection
    {
        if ($orderIds === []) {
            return collect();
        }

        return OrderLines::query()
            ->join('orders', 'orders.id', '=', 'order_lines.orders_id')
            ->whereIn('order_lines.orders_id', $orderIds)
            ->where('order_lines.line_type', 'article')
            ->selectRaw('order_lines.id, order_lines.orders_id, order_lines.qty, order_lines.delivered_qty,
                order_lines.delivery_status, order_lines.invoice_status, orders.statu as order_statu,
                orders.code as order_code,
                COALESCE(order_lines.delivery_date, orders.validity_date) as due_date,
                ROUND(order_lines.qty * order_lines.selling_price * (1 - COALESCE(order_lines.discount, 0) / 100.0), 2) as amount')
            ->toBase()
            ->get();
    }

    /**
     * Tâches des lignes, avec heures prévues (réglage + temps unitaire × quantité
     * commandée, comme Task::TotalTime()) et heures réelles (pointages appariés
     * comme Task::getTotalLogTime()), en deux requêtes.
     */
    private function loadTasks(Collection $lines): Collection
    {
        if ($lines->isEmpty()) {
            return collect();
        }

        $lineById = $lines->keyBy('id');

        $tasks = Task::query()
            ->leftJoin('statuses', 'statuses.id', '=', 'tasks.status_id')
            ->whereIn('tasks.order_lines_id', $lineById->keys()->all())
            ->toBase()
            ->get(['tasks.id', 'tasks.label', 'tasks.order_lines_id', 'tasks.seting_time', 'tasks.unit_time', 'tasks.qty', 'statuses.title as status_title']);

        $activities = $tasks->isEmpty() ? collect() : TaskActivities::query()
            ->whereIn('task_id', $tasks->pluck('id')->all())
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->orderBy('timestamp')
            ->orderBy('id')
            ->toBase()
            ->get(['task_id', 'type', 'timestamp', 'user_id', 'methods_ressources_id'])
            ->groupBy('task_id');

        return $tasks->map(function ($task) use ($lineById, $activities) {
            $line = $lineById->get($task->order_lines_id);
            $qty  = (float) ($line->qty ?? $task->qty ?? 0);
            $taskActivities = $activities->get($task->id, collect());

            $task->orders_id     = $line->orders_id ?? null;
            $task->order_code    = $line->order_code ?? null;
            $task->planned_hours = round((float) $task->seting_time + (float) $task->unit_time * $qty, 2);
            $task->actual_hours  = round(Task::workedSecondsFrom($taskActivities) / 3600, 2);
            $task->started       = $taskActivities->isNotEmpty();

            return $task;
        });
    }

    /** @param int[] $taskIds */
    private function loadPurchaseLines(array $taskIds): Collection
    {
        if ($taskIds === []) {
            return collect();
        }

        return PurchaseLines::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchases_id')
            ->whereNull('purchases.deleted_at')
            ->whereIn('purchase_lines.tasks_id', $taskIds)
            ->orderBy('purchase_lines.purchases_id')
            ->orderBy('purchase_lines.ordre')
            ->toBase()
            ->get([
                'purchase_lines.id', 'purchase_lines.purchases_id', 'purchase_lines.tasks_id', 'purchase_lines.label',
                'purchase_lines.qty', 'purchase_lines.receipt_qty', 'purchase_lines.total_selling_price',
                'purchase_lines.delivery_date', 'purchases.code as purchase_code', 'purchases.statu as purchase_statu',
            ]);
    }

    /**
     * Factures avec leurs montants HT/TTC (prix figé sur la ligne de facture,
     * sinon celui de la ligne de commande, comme InvoiceLines::resolved_*) et
     * la somme des règlements, en une requête.
     *
     * @param int[] $orderIds
     * @param int[] $orderLineIds
     */
    private function loadInvoices(array $orderIds, array $orderLineIds): Collection
    {
        if ($orderIds === []) {
            return collect();
        }

        $lineHt = 'invoice_lines.qty * COALESCE(invoice_lines.unit_price, ol.selling_price, 0)'
            . ' * (1 - COALESCE(invoice_lines.discount, ol.discount, 0) / 100.0)';

        $linesTotal = fn (string $expression) => InvoiceLines::query()
            ->leftJoin('order_lines as ol', 'ol.id', '=', 'invoice_lines.order_line_id')
            ->leftJoin('accounting_vats as av', 'av.id', '=', 'ol.accounting_vats_id')
            ->whereColumn('invoice_lines.invoices_id', 'invoices.id')
            ->selectRaw("COALESCE(SUM($expression), 0)");

        return AffairScope::invoicesQuery($orderIds, $orderLineIds)
            ->select(['invoices.id', 'invoices.code', 'invoices.label', 'invoices.statu', 'invoices.invoice_type', 'invoices.due_date', 'invoices.created_at'])
            ->selectSub($linesTotal("ROUND($lineHt, 2)"), 'amount_ht')
            ->selectSub($linesTotal("($lineHt) * (1 + COALESCE(invoice_lines.vat_rate, av.rate, 0) / 100.0)"), 'amount_ttc')
            ->selectSub(
                InvoicePayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('invoice_payments.invoice_id', 'invoices.id'),
                'paid'
            )
            ->orderBy('invoices.id')
            ->toBase()
            ->get()
            ->map(function ($invoice) {
                $invoice->remaining = max(0, round((float) $invoice->amount_ttc - (float) $invoice->paid, 2));
                return $invoice;
            });
    }

    // ------------------------------------------------------------------
    // Règles de blocage
    // ------------------------------------------------------------------

    private function detectBlockers(
        Collection $quotes, Collection $orders, Collection $lines, Collection $tasks,
        Collection $purchaseLines, Collection $invoices, Collection $visits,
        Carbon $now, Carbon $today
    ): array {
        $blockers = [];
        $quotesWithOrder = $orders->pluck('quotes_id')->map(fn ($id) => (int) $id)->flip();
        $followUpDays    = (int) config('affairs.quote_follow_up_days', 10);
        $visitDraftHours = (int) config('affairs.visit_draft_hours', 48);

        foreach ($quotes as $quote) {
            $statu = (int) $quote->statu;
            if (!in_array($statu, [self::QUOTE_OPEN, self::QUOTE_SENT], true) || $quotesWithOrder->has((int) $quote->id)) {
                continue;
            }

            // Devis expiré : ouvert ou envoyé, date de validité dépassée.
            if ($quote->validity_date && Carbon::parse($quote->validity_date)->lt($today)) {
                $blockers[] = $this->blocker('quote_expired', 'danger', 'quotes', $quote->code,
                    $quote->validity_date, route('quotes.show', $quote->id));
                continue;
            }

            // Devis envoyé sans réponse : dernier envoi plus vieux que le délai de relance.
            if ($statu === self::QUOTE_SENT) {
                $sentAt = Carbon::parse($quote->last_sent_at ?? $quote->updated_at);
                if ($sentAt->copy()->startOfDay()->addDays($followUpDays)->lte($today)) {
                    $blockers[] = $this->blocker('quote_unanswered', 'warning', 'quotes', $quote->code,
                        $sentAt, route('quotes.show', $quote->id), ['days' => (int) $sentAt->copy()->startOfDay()->diffInDays($today)]);
                }
            }
        }

        foreach ($purchaseLines as $line) {
            $statu    = (int) $line->purchase_statu;
            $received = (float) $line->receipt_qty;
            $ordered  = (float) $line->qty;
            if ($statu === self::PURCHASE_CANCELED || $received >= $ordered) {
                continue;
            }

            // Achat en retard : commandé, date de livraison passée, pas entièrement reçu.
            if (in_array($statu, self::PURCHASE_AWAITING, true)
                && $line->delivery_date && Carbon::parse($line->delivery_date)->lt($today)) {
                $blockers[] = $this->blocker('purchase_late', 'danger', 'purchases', $line->purchase_code,
                    $line->delivery_date, route('purchases.show', $line->purchases_id), ['label' => $line->label]);
                continue;
            }

            // Réception partielle (non doublée avec le retard ci-dessus).
            if ($received > 0) {
                $blockers[] = $this->blocker('purchase_partial', 'warning', 'purchases', $line->purchase_code,
                    null, route('purchases.show', $line->purchases_id),
                    ['label' => $line->label, 'received' => $received, 'qty' => $ordered]);
            }
        }

        // Tâche suspendue (statut repéré par son titre, comme OrdersController).
        foreach ($tasks->filter(fn ($t) => strcasecmp((string) $t->status_title, 'Suspended') === 0) as $task) {
            $blockers[] = $this->blocker('task_suspended', 'warning', 'orders', $task->order_code,
                null, $task->orders_id ? route('orders.show', $task->orders_id) : null, ['label' => $task->label]);
        }

        // Commande en retard : une ligne non livrée dont l'échéance est passée
        // (même règle que le widget du tableau de bord). Un blocage par commande.
        $lateLines = $lines->filter(fn ($l) => (int) $l->delivery_status < 3
            && !in_array((int) $l->order_statu, self::ORDER_INACTIVE, true)
            && $l->due_date && Carbon::parse($l->due_date)->lt($today));
        foreach ($lateLines->groupBy('orders_id') as $orderId => $group) {
            $dueDate = $group->min('due_date');
            $blockers[] = $this->blocker('order_late', 'danger', 'orders', $group->first()->order_code,
                $dueDate, route('orders.show', $orderId),
                ['days' => (int) Carbon::parse($dueDate)->startOfDay()->diffInDays($today), 'lines' => $group->count()]);
        }

        // Facture échue impayée.
        foreach ($invoices as $invoice) {
            if (in_array((int) $invoice->invoice_type, self::INVOICE_TYPES_BILLED, true)
                && !in_array((int) $invoice->statu, [self::INVOICE_DRAFT, self::INVOICE_PAID], true)
                && $invoice->due_date && Carbon::parse($invoice->due_date)->lt($today)
                && $invoice->remaining > 0.005) {
                $blockers[] = $this->blocker('invoice_overdue', 'danger', 'invoices', $invoice->code,
                    $invoice->due_date, route('invoices.show', $invoice->id),
                    ['amount' => $invoice->remaining, 'days' => (int) Carbon::parse($invoice->due_date)->startOfDay()->diffInDays($today)]);
            }
        }

        // Visite restée en brouillon au-delà du délai.
        foreach ($visits as $visit) {
            if ((int) $visit->statu === OpportunityVisits::STATU_DRAFT
                && Carbon::parse($visit->visited_at)->addHours($visitDraftHours)->lte($now)) {
                $blockers[] = $this->blocker('visit_unvalidated', 'info', 'visits', null,
                    $visit->visited_at, null, ['visit_id' => (int) $visit->id]);
            }
        }

        return $blockers;
    }

    private function blocker(string $code, string $severity, string $section, ?string $ref, $date, ?string $url, array $params = []): array
    {
        return [
            'code'     => $code,
            'severity' => $severity,
            'section'  => $section,
            'ref'      => $ref,
            'date'     => $this->date($date),
            'url'      => $url,
            'params'   => $params,
        ];
    }

    // ------------------------------------------------------------------
    // Étape et prochaine action
    // ------------------------------------------------------------------

    /**
     * Étape la plus avancée atteinte. Les documents font foi : une commande
     * existe → on est au moins en achats/planning, quel que soit le statut
     * saisi sur l'opportunité.
     */
    private function resolveStage(
        Opportunities $opportunity, Collection $quotes, Collection $orders, Collection $lines,
        Collection $tasks, Collection $invoices, Collection $visits, bool $onsiteEvent
    ): AffairStage {
        $activeOrderIds = $orders->where('statu', '!=', self::ORDER_CANCELED)->pluck('id')->flip();

        if ($activeOrderIds->isNotEmpty()) {
            $activeLines = $lines->filter(fn ($l) => $activeOrderIds->has($l->orders_id));
            $allDelivered = $activeLines->isNotEmpty()
                && $activeLines->every(fn ($l) => (int) $l->delivery_status >= 3);

            if ($allDelivered) {
                $allInvoiced = $activeLines->every(fn ($l) => (int) $l->invoice_status === 3);
                $outstanding = (float) $invoices->whereIn('invoice_type', self::INVOICE_TYPES_BILLED)->sum('remaining');

                return $allInvoiced && $outstanding <= 0.005 ? AffairStage::Closed : AffairStage::Invoicing;
            }

            $started = $tasks->contains(fn ($t) => $t->started)
                || $activeLines->contains(fn ($l) => (float) $l->delivered_qty > 0 || (int) $l->delivery_status === 2);

            return $started ? AffairStage::Production : AffairStage::Procurement;
        }

        if ((int) $opportunity->statu === self::OPPORTUNITY_LOST) {
            return AffairStage::Lost;
        }
        if ($quotes->contains(fn ($q) => (int) $q->statu === self::QUOTE_WON)) {
            return AffairStage::Acceptance;
        }
        if ($quotes->contains(fn ($q) => in_array((int) $q->statu, [self::QUOTE_OPEN, self::QUOTE_SENT], true))) {
            return AffairStage::Quote;
        }
        if ($quotes->isNotEmpty() && $quotes->every(fn ($q) => in_array((int) $q->statu, self::QUOTE_DEAD, true))) {
            return AffairStage::Lost;
        }
        if ($onsiteEvent || $visits->contains(fn ($v) => (int) $v->statu === OpportunityVisits::STATU_VALIDATED)) {
            return AffairStage::Definition;
        }

        return AffairStage::Visit;
    }

    private function stageAction(
        AffairStage $stage, Opportunities $opportunity, Collection $quotes, Collection $orders, Collection $lines,
        Collection $tasks, Collection $purchaseLines, Collection $invoices, Collection $visits, array $visible
    ): ?array {
        $action = fn (string $code, string $section, ?string $ref = null, ?string $url = null) =>
            isset($visible[$section]) ? ['code' => $code, 'ref' => $ref, 'url' => $url, 'blocker' => null] : null;
        $firstActiveOrder = $orders->first(fn ($o) => (int) $o->statu !== self::ORDER_CANCELED);
        $orderAction = fn (string $code) => $firstActiveOrder
            ? $action($code, 'orders', $firstActiveOrder->code, route('orders.show', $firstActiveOrder->id))
            : null;

        switch ($stage) {
            case AffairStage::Visit:
                $draft = $visits->firstWhere('statu', OpportunityVisits::STATU_DRAFT);
                return $action($draft ? 'finish_visit' : 'plan_visit', 'visits', null, $draft
                    ? route('opportunities.visit', ['id' => $opportunity->id, 'visit' => $draft->id])
                    : route('opportunities.visit', $opportunity->id));

            case AffairStage::Definition:
                return $action('create_quote', 'quotes', null, route('opportunities.store.quote', $opportunity->id));

            case AffairStage::Quote:
                $open = $quotes->first(fn ($q) => (int) $q->statu === self::QUOTE_OPEN);
                $quote = $open ?? $quotes->first(fn ($q) => (int) $q->statu === self::QUOTE_SENT);
                return $action($open ? 'send_quote' : 'await_answer', 'quotes', $quote->code, route('quotes.show', $quote->id));

            case AffairStage::Acceptance:
                $won = $quotes->first(fn ($q) => (int) $q->statu === self::QUOTE_WON);
                return $action('convert_quote', 'quotes', $won->code, route('quotes.show', $won->id));

            case AffairStage::Procurement:
                $draft = $purchaseLines->first(fn ($l) => (int) $l->purchase_statu === self::PURCHASE_DRAFT);
                if ($draft) {
                    return $action('order_purchases', 'purchases', $draft->purchase_code, route('purchases.show', $draft->purchases_id));
                }
                return $orderAction($tasks->isEmpty() ? 'plan_production' : 'start_production');

            case AffairStage::Production:
                $allFinished = $tasks->isNotEmpty()
                    && $tasks->every(fn ($t) => strcasecmp((string) $t->status_title, 'Finished') === 0);
                return $orderAction($allFinished ? 'deliver' : 'follow_production');

            case AffairStage::Invoicing:
                if ($lines->contains(fn ($l) => (int) $l->invoice_status !== 3)) {
                    return $orderAction('invoice');
                }
                $unpaid = $invoices->first(fn ($i) => in_array((int) $i->invoice_type, self::INVOICE_TYPES_BILLED, true) && $i->remaining > 0.005);
                return $unpaid ? $action('collect_payment', 'invoices', $unpaid->code, route('invoices.show', $unpaid->id)) : null;

            default:
                return null;
        }
    }

    private function date($value, string $format = 'Y-m-d'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof CarbonInterface ? $value : Carbon::parse($value))->format($format);
    }
}
