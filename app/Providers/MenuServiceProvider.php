<?php

namespace App\Providers;

use App\Models\Purchases\PurchaseLines;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class MenuServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $events): void
    {
        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {

            $ordersNotFinishCount = Cache::remember('menu_orders_not_finish', 60, function () {
                return Orders::whereNotIn('statu', [3, 6])->count();
            });

            $deliverysRequestsCount = Cache::remember('menu_deliverys_requests', 60, function () {
                return OrderLines::where(function ($query) {
                    $query->where('delivery_status', '=', '1')
                          ->orWhere('delivery_status', '=', '2');
                })->whereHas('order', function ($q) {
                    $q->whereNotIn('statu', [5, 6])->where('type', '=', '1');
                })->count();
            });

            $invoicesRequestsCount = Cache::remember('menu_invoices_requests', 60, function () {
                // Facturable (1) + Partiellement (3). Exclut non facturable (2) et facturé (4).
                return DeliveryLines::whereIn('invoice_status', ['1', '3'])->count();
            });

            $purchasesWaitingReceiptCount = Cache::remember('menu_purchases_receipt', 60, function () {
                return PurchaseLines::whereColumn('receipt_qty', '<=', 'qty')->count();
            });

            $purchasesWaitingInvoiceCount = Cache::remember('menu_purchases_invoice', 60, function () {
                return PurchaseLines::whereColumn('invoiced_qty', '<=', 'qty')->count();
            });

            $event->menu->addBefore('orders_lines_list', [
                'text'        => 'orders_list_trans_key',
                'url'         => 'orders',
                'label'       => $ordersNotFinishCount,
                'label_color' => 'success',
            ]);

            $event->menu->addIn('delivery_notes', [
                'text'        => 'deliverys_notes_request_trans_key',
                'url'         => 'deliverys/request',
                'label'       => $deliverysRequestsCount,
                'label_color' => 'warning',
            ]);

            $event->menu->addIn('invoices', [
                'text'        => 'invoices_request_trans_key',
                'url'         => 'invoices/request',
                'label'       => $invoicesRequestsCount,
                'label_color' => 'warning',
            ]);

            $event->menu->addBefore('po_receipt', [
                'text'        => 'waiting_to_receipt_trans_key',
                'url'         => 'purchases/waiting/receipt',
                'label'       => $purchasesWaitingReceiptCount,
                'label_color' => 'warning',
            ]);

            $event->menu->addBefore('invoice_supplier', [
                'text'        => 'waiting_to_invoice_trans_key',
                'url'         => 'purchases/waiting/invoice',
                'label'       => $purchasesWaitingInvoiceCount,
                'label_color' => 'warning',
            ]);
        });
    }
}
