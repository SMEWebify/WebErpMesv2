<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename existing duplicate codes before enforcing uniqueness
        $duplicateCodes = DB::table('products')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        foreach ($duplicateCodes as $code) {
            $rows = DB::table('products')
                ->where('code', $code)
                ->orderBy('id')
                ->get()
                ->skip(1);

            foreach ($rows as $i => $row) {
                DB::table('products')
                    ->where('id', $row->id)
                    ->update(['code' => $code . '_dup' . ($i + 1)]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};
