<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\DashboardConfig;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Leads;

class TodayController extends Controller
{
    // ─── Config widget ────────────────────────────────────────────────────────

    public function configShow()
    {
        $config = DashboardConfig::where('user_id', Auth::id())->first();
        return response()->json([
            'today_layout' => $config?->today_layout,
            'home_view'    => $config?->home_view ?? 'kpi',
        ]);
    }

    public function configUpdate(Request $request)
    {
        $request->validate([
            'today_layout'           => 'sometimes|array',
            'today_layout.*.id'      => 'required_with:today_layout|string',
            'today_layout.*.enabled' => 'required_with:today_layout|boolean',
            'today_layout.*.order'   => 'required_with:today_layout|integer',
            'home_view'              => 'sometimes|string|in:kpi,today',
        ]);

        $data = [];
        if ($request->has('today_layout')) $data['today_layout'] = $request->today_layout;
        if ($request->has('home_view'))    $data['home_view']    = $request->home_view;

        DashboardConfig::updateOrCreate(['user_id' => Auth::id()], $data);

        return response()->json(['ok' => true]);
    }

    // ─── Widget : Factures échues ─────────────────────────────────────────────

    public function invoicesOverdue()
    {
        if (! Auth::user()->can('invoices-menu')) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $items = Invoices::whereNotIn('statu', [5])
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with('companie:id,label')
            ->select('id', 'code', 'label', 'statu', 'due_date', 'companies_id')
            ->orderBy('due_date')
            ->limit(15)
            ->get()
            ->map(fn($i) => [
                'id'          => $i->id,
                'code'        => $i->code,
                'label'       => $i->label,
                'statu'       => $i->statu,
                'date'        => $i->due_date,
                'company'     => $i->companie?->label,
                'url'         => route('invoices.show', $i->id),
                'days'        => (int) Carbon::today()->diffInDays($i->due_date, false) * -1,
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }

    // ─── Widget : Commandes en retard ─────────────────────────────────────────

    public function ordersLate()
    {
        if (! Auth::user()->can('orders-menu')) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $items = Orders::whereIn('statu', [1, 2])
            ->whereNotNull('validity_date')
            ->where('validity_date', '<', Carbon::today())
            ->with('companie:id,label')
            ->select('id', 'code', 'label', 'statu', 'validity_date', 'companies_id')
            ->orderBy('validity_date')
            ->limit(15)
            ->get()
            ->map(fn($o) => [
                'id'      => $o->id,
                'code'    => $o->code,
                'label'   => $o->label,
                'statu'   => $o->statu,
                'date'    => $o->validity_date,
                'company' => $o->companie?->label,
                'url'     => route('orders.show', $o->id),
                'days'    => (int) Carbon::today()->diffInDays($o->validity_date, false) * -1,
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }

    // ─── Widget : Commandes à livrer cette semaine ────────────────────────────

    public function ordersDueWeek()
    {
        if (! Auth::user()->can('orders-menu')) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $items = Orders::whereIn('statu', [1, 2])
            ->whereNotNull('validity_date')
            ->whereBetween('validity_date', [Carbon::today(), Carbon::today()->addDays(7)])
            ->with('companie:id,label')
            ->select('id', 'code', 'label', 'statu', 'validity_date', 'companies_id')
            ->orderBy('validity_date')
            ->limit(15)
            ->get()
            ->map(fn($o) => [
                'id'      => $o->id,
                'code'    => $o->code,
                'label'   => $o->label,
                'statu'   => $o->statu,
                'date'    => $o->validity_date,
                'company' => $o->companie?->label,
                'url'     => route('orders.show', $o->id),
                'days'    => (int) Carbon::today()->diffInDays($o->validity_date),
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }

    // ─── Widget : Devis qui expirent bientôt ─────────────────────────────────

    public function quotesExpiring()
    {
        if (! Auth::user()->can('quotes-menu')) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $items = Quotes::where('statu', 2) // envoyé
            ->whereNotNull('validity_date')
            ->where('validity_date', '<=', Carbon::today()->addDays(7))
            ->with('companie:id,label')
            ->select('id', 'code', 'label', 'statu', 'validity_date', 'companies_id')
            ->orderBy('validity_date')
            ->limit(15)
            ->get()
            ->map(fn($q) => [
                'id'      => $q->id,
                'code'    => $q->code,
                'label'   => $q->label,
                'statu'   => $q->statu,
                'date'    => $q->validity_date,
                'company' => $q->companie?->label,
                'url'     => route('quotes.show', $q->id),
                'days'    => (int) Carbon::today()->diffInDays($q->validity_date, false),
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }

    // ─── Widget : Leads non traités ───────────────────────────────────────────

    public function leadsPending()
    {
        if (! Auth::user()->can('leads-menu')) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $items = Leads::where('statu', 1) // nouveau
            ->where('created_at', '<', Carbon::today()->subDays(2))
            ->with('companie:id,label')
            ->select('id', 'statu', 'source', 'priority', 'created_at', 'companies_id')
            ->orderBy('created_at')
            ->limit(15)
            ->get()
            ->map(fn($l) => [
                'id'      => $l->id,
                'code'    => null,
                'label'   => $l->source ?? '—',
                'statu'   => $l->statu,
                'date'    => $l->created_at?->toDateString(),
                'company' => $l->companie?->label,
                'url'     => route('leads.show', $l->id),
                'days'    => (int) $l->created_at?->diffInDays(Carbon::today()),
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }

    // ─── Widget : Activité récente (7 derniers jours) ─────────────────────────

    public function recentActivity()
    {
        $since = Carbon::today()->subDays(7);
        $items = collect();

        if (Auth::user()->can('quotes-menu')) {
            Quotes::where('created_at', '>=', $since)
                ->with('companie:id,label')
                ->select('id', 'code', 'label', 'statu', 'created_at', 'companies_id')
                ->orderByDesc('created_at')->limit(5)->get()
                ->each(fn($q) => $items->push([
                    'type'    => 'quote',
                    'id'      => $q->id,
                    'code'    => $q->code,
                    'label'   => $q->label,
                    'statu'   => $q->statu,
                    'date'    => $q->created_at?->toDateString(),
                    'company' => $q->companie?->label,
                    'url'     => route('quotes.show', $q->id),
                ]));
        }

        if (Auth::user()->can('orders-menu')) {
            Orders::where('created_at', '>=', $since)
                ->with('companie:id,label')
                ->select('id', 'code', 'label', 'statu', 'created_at', 'companies_id')
                ->orderByDesc('created_at')->limit(5)->get()
                ->each(fn($o) => $items->push([
                    'type'    => 'order',
                    'id'      => $o->id,
                    'code'    => $o->code,
                    'label'   => $o->label,
                    'statu'   => $o->statu,
                    'date'    => $o->created_at?->toDateString(),
                    'company' => $o->companie?->label,
                    'url'     => route('orders.show', $o->id),
                ]));
        }

        $sorted = $items->sortByDesc('date')->values()->take(15);
        return response()->json(['count' => $sorted->count(), 'items' => $sorted]);
    }
}
