<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * invoices.payment_date n'était jamais écrite : les factures déjà payées
 * reçoivent la date de leur dernier règlement. Ne touche qu'aux lignes vides.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->whereNull('payment_date')
            ->where('statu', 5)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('invoice_payments')
                ->whereColumn('invoice_payments.invoice_id', 'invoices.id'))
            ->update([
                'payment_date' => DB::raw('(SELECT MAX(invoice_payments.payment_date) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id)'),
            ]);
    }

    public function down(): void
    {
        // Rien à défaire : la date était absente, elle est désormais renseignée.
    }
};
