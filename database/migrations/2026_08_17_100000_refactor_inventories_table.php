<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // all | location | category
            $table->string('scope_type', 20)->default('all')->after('label');
            $table->json('scope_ids')->nullable()->after('scope_type');

            $table->timestamp('frozen_at')->nullable()->after('end_date');
            $table->timestamp('validated_at')->nullable()->after('frozen_at');

            $table->foreignId('validated_by')
                ->nullable()
                ->after('validated_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->after('validated_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('file_id')
                ->nullable()
                ->after('created_by')
                ->constrained('files')
                ->nullOnDelete();

            $table->foreignId('entry_move_id')
                ->nullable()
                ->after('file_id')
                ->constrained('stock_moves')
                ->nullOnDelete();

            $table->foreignId('exit_move_id')
                ->nullable()
                ->after('entry_move_id')
                ->constrained('stock_moves')
                ->nullOnDelete();

            $table->index('statu');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex(['statu']);
            $table->dropConstrainedForeignId('exit_move_id');
            $table->dropConstrainedForeignId('entry_move_id');
            $table->dropConstrainedForeignId('file_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['scope_type', 'scope_ids', 'frozen_at', 'validated_at']);
        });
    }
};
