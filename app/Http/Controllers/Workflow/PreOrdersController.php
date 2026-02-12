<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\PreOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreOrdersController extends Controller
{
    public function index()
    {
        $preOrders = PreOrder::withCount('lines')
            ->with(['convertedOrder', 'importBatch', 'lines'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('workflow.pre-orders-index', compact('preOrders'));
    }


    public function upload(Request $request)
    {
        $data = $request->validate([
            'pdfs' => 'required|array|min:1',
            'pdfs.*' => 'required|file|mimes:pdf|max:20480',
        ]);

        $disk = config('filesystems.default');
        $inputPath = trim(config('pre_orders.input_path', 'pre-orders/input'), '/');

        foreach ($data['pdfs'] as $pdfFile) {
            $originalName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBaseName = Str::slug($originalName, '_') ?: 'pre_order';
            $fileName = $safeBaseName . '_' . now()->format('Ymd_His_u') . '.pdf';
            Storage::disk($disk)->putFileAs($inputPath, $pdfFile, $fileName);
        }

        return redirect()->route('pre-orders.index')->with('success', 'PDF(s) envoyé(s) dans le stockage avec succès.');
    }

    public function show(PreOrder $preOrder)
    {
        $preOrder->load('lines', 'convertedOrder');

        return view('workflow.pre-orders-show', [
            'preOrder' => $preOrder,
            'companies' => Companies::orderBy('code')->get(),
            'users' => User::orderBy('name')->get(),
            'paymentConditions' => AccountingPaymentConditions::orderBy('code')->get(),
            'paymentMethods' => AccountingPaymentMethod::orderBy('code')->get(),
            'deliveries' => AccountingDelivery::orderBy('code')->get(),
            'units' => MethodsUnits::orderBy('code')->get(),
            'vats' => AccountingVat::orderBy('code')->get(),
            'defaultUnit' => MethodsUnits::where('default', 1)->first(),
            'defaultVat' => AccountingVat::where('default', 1)->first(),
        ]);
    }

    public function convert(Request $request, PreOrder $preOrder)
    {
        if ($preOrder->status === PreOrder::STATUS_CONVERTED) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Pré-commande déjà convertie.');
        }

        $data = $request->validate([
            'code' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'companies_id' => 'nullable|exists:companies,id',
            'customer_reference' => 'nullable|string|max:255',
            'validity_date' => 'nullable|date',
            'accounting_payment_conditions_id' => 'nullable|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id' => 'nullable|exists:accounting_payment_methods,id',
            'accounting_deliveries_id' => 'nullable|exists:accounting_deliveries,id',
            'comment' => 'nullable|string',
            'methods_units_id' => 'required|exists:methods_units,id',
            'accounting_vats_id' => 'required|exists:accounting_vats,id',
            'delivery_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'type' => 'required|in:1,2',
        ]);

        $preOrder->load('lines', 'importBatch');

        if ($preOrder->lines->isEmpty()) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Pré-commande vide, impossible de convertir.');
        }

        DB::transaction(function () use ($preOrder, $data) {
            $order = Orders::create([
                'uuid' => Str::uuid(),
                'code' => $data['code'],
                'label' => $data['label'],
                'customer_reference' => $data['customer_reference'] ?? null,
                'companies_id' => $data['companies_id'] ?? null,
                'validity_date' => $data['validity_date'] ?? null,
                'statu' => 1,
                'user_id' => $data['user_id'],
                'accounting_payment_conditions_id' => $data['accounting_payment_conditions_id'] ?? null,
                'accounting_payment_methods_id' => $data['accounting_payment_methods_id'] ?? null,
                'accounting_deliveries_id' => $data['accounting_deliveries_id'] ?? null,
                'comment' => $data['comment'] ?? null,
                'type' => $data['type'],
                'csv_file_name' => $preOrder->importBatch?->file_name,
            ]);

            foreach ($preOrder->lines as $index => $line) {
                $orderLine = OrderLines::create([
                    'orders_id' => $order->id,
                    'ordre' => $index + 1,
                    'code' => $line->reference,
                    'label' => $line->product ?: $line->reference,
                    'qty' => max((int) $line->quantity, 1),
                    'delivered_remaining_qty' => max((int) $line->quantity, 1),
                    'invoiced_remaining_qty' => max((int) $line->quantity, 1),
                    'methods_units_id' => $data['methods_units_id'],
                    'selling_price' => $line->unit_price,
                    'discount' => $data['discount'] ?? 0,
                    'accounting_vats_id' => $data['accounting_vats_id'],
                    'delivery_date' => $data['delivery_date'] ?? null,
                ]);

                OrderLineDetails::create([
                    'order_lines_id' => $orderLine->id,
                ]);
            }

            $preOrder->update([
                'status' => PreOrder::STATUS_CONVERTED,
                'converted_order_id' => $order->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('pre-orders.show', $preOrder)->with('success', 'Pré-commande convertie en commande.');
    }
}
