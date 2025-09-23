<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('supplier_ratings', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('comment');
            $table->date('next_review_at')->nullable()->after('approved_at');
            $table->string('evaluation_status')->default('pending')->after('next_review_at');
            $table->unsignedTinyInteger('evaluation_score_quality')->nullable()->after('evaluation_status');
            $table->unsignedTinyInteger('evaluation_score_logistics')->nullable()->after('evaluation_score_quality');
            $table->unsignedTinyInteger('evaluation_score_service')->nullable()->after('evaluation_score_logistics');
            $table->text('action_plan')->nullable()->after('evaluation_score_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('supplier_ratings', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'next_review_at',
                'evaluation_status',
                'evaluation_score_quality',
                'evaluation_score_logistics',
                'evaluation_score_service',
                'action_plan',
            ]);
        });
    }
};
