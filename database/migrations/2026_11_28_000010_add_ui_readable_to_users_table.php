<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Lecture facilitée » (Profil → Apparence) : police très lisible, interlignes et
 * espacements plus grands, propres à l'utilisateur. Faux pour tous les comptes
 * existants : leur affichage ne change pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ui_readable')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ui_readable');
        });
    }
};
