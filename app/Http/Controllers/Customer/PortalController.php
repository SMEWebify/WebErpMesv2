<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Services\InvoiceCalculatorService;
use App\Services\OrderCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    private const ORDER_STATUS_LABELS = [
        1 => 'general_content.open_trans_key',
        2 => 'general_content.in_progress_trans_key',
        3 => 'general_content.delivered_trans_key',
        4 => 'general_content.partly_delivered_trans_key',
    ];

    private const ORDER_STATUS_BADGES = [
        1 => 'info',
        2 => 'warning',
        3 => 'success',
        4 => 'danger',
    ];

    private const INVOICE_STATUS_LABELS = [
        1 => 'general_content.in_progress_trans_key',
        2 => 'general_content.send_trans_key',
        3 => 'general_content.pending_trans_key',
        4 => 'general_content.unpaid_trans_key',
        5 => 'general_content.paid_trans_key',
    ];

    private const INVOICE_STATUS_BADGES = [
        1 => 'info',
        2 => 'primary',
        3 => 'warning',
        4 => 'danger',
        5 => 'success',
    ];

    private const DELIVERY_STATUS_LABELS = [
        1 => 'general_content.in_progress_trans_key',
        2 => 'general_content.send_trans_key',
    ];

    private const DELIVERY_STATUS_BADGES = [
        1 => 'info',
        2 => 'warning',
    ];

    /**
     * Display the customer dashboard.
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $ordersBaseQuery = Orders::query()
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            });

        $ordersQuery = (clone $ordersBaseQuery)->with('companie');
        if ($status = $request->integer('order_status')) {
            $ordersQuery->where('statu', $status);
        }
        if ($search = $request->input('order_search')) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('customer_reference', 'like', "%{$search}%");
            });
        }

        $orders = $ordersQuery
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        $openOrdersCount = (clone $ordersBaseQuery)->whereIn('statu', [1, 2, 4])->count();

        $deliveriesBaseQuery = Deliverys::query()
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            });

        $deliveries = (clone $deliveriesBaseQuery)
            ->with('Orders')
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'deliveries_page')
            ->withQueryString();

        $pendingDeliveriesCount = (clone $deliveriesBaseQuery)->where('statu', 1)->count();

        $invoicesBaseQuery = Invoices::query()
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            });

        $invoicesQuery = (clone $invoicesBaseQuery)->with('companie');
        if ($status = $request->integer('invoice_status')) {
            $invoicesQuery->where('statu', $status);
        }
        if ($search = $request->input('invoice_search')) {
            $invoicesQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $invoices = $invoicesQuery
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'invoices_page')
            ->withQueryString();

        $unpaidInvoicesCount = (clone $invoicesBaseQuery)->whereIn('statu', [3, 4])->count();

        $notifications = [
            [
                'title' => __('general_content.orders_trans_key'),
                'subtitle' => __('general_content.in_progress_trans_key'),
                'value' => $openOrdersCount,
                'variant' => 'info',
            ],
            [
                'title' => __('general_content.delivery_notes_trans_key'),
                'subtitle' => __('general_content.in_progress_trans_key'),
                'value' => $pendingDeliveriesCount,
                'variant' => 'warning',
            ],
            [
                'title' => __('general_content.invoices_trans_key'),
                'subtitle' => __('general_content.unpaid_trans_key'),
                'value' => $unpaidInvoicesCount,
                'variant' => 'danger',
            ],
        ];

        return view('customer.dashboard', [
            'customer' => $customer,
            'orders' => $orders,
            'invoices' => $invoices,
            'deliveries' => $deliveries,
            'notifications' => $notifications,
            'orderStatusOptions' => $this->getOrderStatusLabels(),
            'invoiceStatusOptions' => $this->getInvoiceStatusLabels(),
            'orderStatusBadges' => self::ORDER_STATUS_BADGES,
            'invoiceStatusBadges' => self::INVOICE_STATUS_BADGES,
            'deliveryStatusBadges' => self::DELIVERY_STATUS_BADGES,
            'deliveryStatusLabels' => $this->getDeliveryStatusLabels(),
        ]);
    }

    /**
     * Display an order.
     */
    public function showOrder(string $uuid)
    {
        $customer = Auth::guard('customer')->user();

        $order = Orders::where('uuid', $uuid)
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            })
            ->with([
                'OrderLines.Unit',
                'OrderLines.VAT',
                'OrderLines.OrderLineDetails',
                'OrderLines.DeliveryLines.delivery',
                'payment_method',
                'payment_condition',
                'delevery_method',
                'companie',
                'contact',
                'adresse',
                'Rating',
            ])
            ->firstOrFail();

        $calculator = new OrderCalculatorService($order);

        return view('customer.orders.show', [
            'order' => $order,
            'totalPrices' => $calculator->getTotalPrice(),
            'subPrice' => $calculator->getSubTotal(),
            'vatPrice' => $calculator->getVatTotal(),
            'orderStatusBadges' => self::ORDER_STATUS_BADGES,
            'orderStatusLabels' => $this->getOrderStatusLabels(),
        ]);
    }

    /**
     * Display a delivery note.
     */
    public function showDelivery(string $uuid)
    {
        $customer = Auth::guard('customer')->user();

        $delivery = Deliverys::where('uuid', $uuid)
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            })
            ->with([
                'DeliveryLines.OrderLine.Unit',
                'DeliveryLines.OrderLine.order',
                'DeliveryLines.QualityNonConformity',
            ])
            ->firstOrFail();

        return view('customer.deliveries.show', [
            'delivery' => $delivery,
            'deliveryStatusBadges' => self::DELIVERY_STATUS_BADGES,
            'deliveryStatusLabels' => $this->getDeliveryStatusLabels(),
        ]);
    }

    /**
     * Display an invoice.
     */
    public function showInvoice(string $uuid)
    {
        $customer = Auth::guard('customer')->user();

        $invoice = Invoices::where('uuid', $uuid)
            ->where(function ($query) use ($customer) {
                $query->where('companies_id', $customer->companies_id)
                    ->orWhere('companies_contacts_id', $customer->getKey());
            })
            ->with([
                'invoiceLines.orderLine.Unit',
                'invoiceLines.orderLine.VAT',
                'invoiceLines.deliveryLine.delivery',
                'companie',
                'contact',
                'adresse',
            ])
            ->firstOrFail();

        $calculator = new InvoiceCalculatorService($invoice);

        return view('customer.invoices.show', [
            'invoice' => $invoice,
            'totalPrices' => $calculator->getTotalPrice(),
            'subPrice' => $calculator->getSubTotal(),
            'vatPrice' => $calculator->getVatTotal(),
            'invoiceStatusBadges' => self::INVOICE_STATUS_BADGES,
            'invoiceStatusLabels' => $this->getInvoiceStatusLabels(),
        ]);
    }

    /**
     * Translate order status labels.
     */
    private function getOrderStatusLabels(): array
    {
        return $this->translateLabels(self::ORDER_STATUS_LABELS);
    }

    /**
     * Translate invoice status labels.
     */
    private function getInvoiceStatusLabels(): array
    {
        return $this->translateLabels(self::INVOICE_STATUS_LABELS);
    }

    /**
     * Translate delivery status labels.
     */
    private function getDeliveryStatusLabels(): array
    {
        return $this->translateLabels(self::DELIVERY_STATUS_LABELS);
    }

    /**
     * Translate status labels with localization support.
     */
    private function translateLabels(array $labels): array
    {
        $translated = [];
        foreach ($labels as $status => $translationKey) {
            $translated[$status] = __($translationKey);
        }

        return $translated;
    }
}
