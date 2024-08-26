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
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->boolean('reception_controlled')->default(false)->after('comment');
            $table->dateTime('reception_control_date')->nullable()->after('reception_controlled');
            $table->unsignedBigInteger('reception_control_user_id')->nullable()->after('reception_control_date');

            $table->foreign('reception_control_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->dropForeign(['reception_control_user_id']);
            $table->dropColumn('reception_controlled');
            $table->dropColumn('reception_control_date');
            $table->dropColumn('reception_control_user_id');
        });
    }
};
