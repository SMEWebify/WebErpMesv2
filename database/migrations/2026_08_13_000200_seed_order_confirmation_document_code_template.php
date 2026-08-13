<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Le seeder ne rejoue pas sur les installations existantes : sans ce template
        // le générateur retomberait sur '{type}-{id}' et sortirait des codes du genre
        // ORDER-CONFIRMATION-12.
        $exists = DB::table('document_code_templates')
            ->where('document_type', 'order-confirmation')
            ->exists();

        if (!$exists) {
            DB::table('document_code_templates')->insert([
                'document_type' => 'order-confirmation',
                'template'      => 'ARC-{yyyy}-{id}',
                'reset_period'  => 'none',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('document_code_templates')
            ->where('document_type', 'order-confirmation')
            ->delete();
    }
};
