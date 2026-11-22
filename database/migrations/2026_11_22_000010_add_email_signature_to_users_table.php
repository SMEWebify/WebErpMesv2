<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signature des e-mails de l'utilisateur (Profil → E-mail), HTML nettoyé
     * par SafeHtml. Null = pas de signature : envoi identique à avant.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_signature')) {
                $table->text('email_signature')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_signature')) {
                $table->dropColumn('email_signature');
            }
        });
    }
};
