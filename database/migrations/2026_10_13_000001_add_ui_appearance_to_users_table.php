<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apparence choisie par l'utilisateur (App\Support\UiTheme) : habillage de
     * la sidebar et de la navbar, couleur primaire. Null = valeur par défaut.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ui_preset')) {
                $table->string('ui_preset', 40)->nullable();
            }
            if (! Schema::hasColumn('users', 'ui_primary')) {
                $table->string('ui_primary', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ui_primary')) {
                $table->dropColumn('ui_primary');
            }
            if (Schema::hasColumn('users', 'ui_preset')) {
                $table->dropColumn('ui_preset');
            }
        });
    }
};
