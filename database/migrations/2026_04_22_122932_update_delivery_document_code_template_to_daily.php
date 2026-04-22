<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('document_code_templates')
            ->where('document_type', 'delivery')
            ->first();

        if ($existing) {
            DB::table('document_code_templates')
                ->where('document_type', 'delivery')
                ->update([
                    'template'     => '{day}/{month}/{year}/{id}',
                    'reset_period' => 'daily',
                    'updated_at'   => now(),
                ]);
        } else {
            DB::table('document_code_templates')->insert([
                'document_type' => 'delivery',
                'template'      => '{day}/{month}/{year}/{id}',
                'reset_period'  => 'daily',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('document_code_templates')
            ->where('document_type', 'delivery')
            ->update([
                'template'     => '{type}-{id}',
                'reset_period' => 'none',
                'updated_at'   => now(),
            ]);
    }
};
