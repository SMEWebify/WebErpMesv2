<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_sites')) {
            Schema::table('order_sites', function (Blueprint $table) {
                if (!Schema::hasColumn('order_sites', 'location')) {
                    $table->string('location')->nullable()->after('order_id');
                }

                if (!Schema::hasColumn('order_sites', 'characteristics')) {
                    $table->text('characteristics')->nullable()->after('location');
                }

                if (!Schema::hasColumn('order_sites', 'contact_info')) {
                    $table->text('contact_info')->nullable()->after('characteristics');
                }
            });
        } else {
            Schema::create('order_sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->string('location')->nullable();
                $table->text('characteristics')->nullable();
                $table->text('contact_info')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_sites')) {
            Schema::table('order_sites', function (Blueprint $table) {
                if (Schema::hasColumn('order_sites', 'contact_info')) {
                    $table->dropColumn('contact_info');
                }

                if (Schema::hasColumn('order_sites', 'characteristics')) {
                    $table->dropColumn('characteristics');
                }

                if (Schema::hasColumn('order_sites', 'location')) {
                    $table->dropColumn('location');
                }
            });
        }
    }
};
