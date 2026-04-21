<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders',            fn (Blueprint $t) => $t->unique('code'));
        Schema::table('invoices',          fn (Blueprint $t) => $t->unique('code'));
        Schema::table('deliverys',         fn (Blueprint $t) => $t->unique('code'));
        Schema::table('purchases',         fn (Blueprint $t) => $t->unique('code'));
        Schema::table('purchase_invoices', fn (Blueprint $t) => $t->unique('code'));
        Schema::table('quotes',            fn (Blueprint $t) => $t->unique('code'));
    }

    public function down(): void
    {
        Schema::table('orders',            fn (Blueprint $t) => $t->dropUnique(['code']));
        Schema::table('invoices',          fn (Blueprint $t) => $t->dropUnique(['code']));
        Schema::table('deliverys',         fn (Blueprint $t) => $t->dropUnique(['code']));
        Schema::table('purchases',         fn (Blueprint $t) => $t->dropUnique(['code']));
        Schema::table('purchase_invoices', fn (Blueprint $t) => $t->dropUnique(['code']));
        Schema::table('quotes',            fn (Blueprint $t) => $t->dropUnique(['code']));
    }
};
