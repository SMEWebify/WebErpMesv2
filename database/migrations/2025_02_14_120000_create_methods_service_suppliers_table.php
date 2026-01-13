<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('methods_service_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('methods_service_id')->constrained('methods_services')->cascadeOnDelete();
            $table->foreignId('companies_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['methods_service_id', 'companies_id']);
        });

        $services = DB::table('methods_services')
            ->select('methods_services.id', 'methods_services.companies_id')
            ->whereNotNull('methods_services.companies_id')
            ->whereRaw('methods_services.companies_id REGEXP "^[0-9]+$"')
            ->join('companies', 'companies.id', '=', DB::raw('methods_services.companies_id'))
            ->get();

        foreach ($services as $service) {
            DB::table('methods_service_suppliers')->insert([
                'methods_service_id' => $service->id,
                'companies_id' => (int) $service->companies_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('methods_service_suppliers');
    }
};
