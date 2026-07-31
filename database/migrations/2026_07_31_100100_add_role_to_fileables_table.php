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
        Schema::table('fileables', function (Blueprint $table) {
            // Business role of the file for that entity: plan, modele_3d, cam, photo...
            // This replaces the dedicated drawing_file / stl_file / svg_file columns.
            $table->string('role', 32)->nullable()->after('fileable_type');
            // Marks the file shown by default for a given role (the "current" drawing).
            $table->boolean('is_primary')->default(false)->after('role');

            $table->index(['fileable_type', 'fileable_id', 'role'], 'fileables_entity_role_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fileables', function (Blueprint $table) {
            $table->dropIndex('fileables_entity_role_index');
            $table->dropColumn(['role', 'is_primary']);
        });
    }
};
