<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('methods_services_id');
            $table->index('component_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('methods_families_id');
        });

        Schema::table('methods_services', function (Blueprint $table) {
            $table->index('is_nesting');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['methods_services_id']);
            $table->dropIndex(['component_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['methods_families_id']);
        });

        Schema::table('methods_services', function (Blueprint $table) {
            $table->dropIndex(['is_nesting']);
        });
    }
};
