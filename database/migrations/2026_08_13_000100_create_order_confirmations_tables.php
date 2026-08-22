<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ARC (Accusé de Réception de Commande) — document contractuel figé.
        //
        // L'ARC est une photo de la commande prise au moment de la revue : le header
        // et les lignes recopient les valeurs plutôt que de les lire via les relations,
        // sinon on republierait toujours l'état courant de la commande et le document
        // ne prouverait plus rien.
        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
            $table->string('label')->nullable();
            $table->string('customer_reference')->nullable();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Indice de révision : A pour l'ARC initial, B, C... après chaque avenant.
            $table->string('revision', 2)->default('A');

            $table->unsignedTinyInteger('statu')->default(1);
            #1 = En cours (modifiable)
            #2 = Envoyé (figé)
            #3 = Accepté par le client
            #4 = Remplacé par un indice supérieur

            // Un seul ARC courant par commande : celui qui fait foi pour la comparaison
            // avec les lignes de commande. Un brouillon n'engage rien, le drapeau
            // n'est posé qu'à l'envoi.
            $table->boolean('is_current')->default(false);

            $table->foreignId('supersedes_id')->nullable()
                ->constrained('order_confirmations')->nullOnDelete();

            // Snapshot du tiers : l'adresse ou le contact peuvent changer sur la
            // commande après l'envoi, l'engagement lui ne change pas.
            $table->foreignId('companies_id')->nullable()
                ->constrained('companies')->nullOnDelete();
            $table->foreignId('companies_contacts_id')->nullable()
                ->constrained('companies_contacts')->nullOnDelete();
            $table->foreignId('companies_addresses_id')->nullable()
                ->constrained('companies_addresses')->nullOnDelete();

            // Conditions commerciales : sans FK, comme sur orders.
            $table->unsignedBigInteger('accounting_payment_conditions_id')->nullable();
            $table->unsignedBigInteger('accounting_payment_methods_id')->nullable();
            $table->unsignedBigInteger('accounting_deliveries_id')->nullable();

            $table->date('validity_date')->nullable();

            // Émetteur de l'ARC (pas forcément le chargé d'affaires de la commande).
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->text('comment')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('customer_accepted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'revision']);
            $table->index(['order_id', 'is_current']);
            $table->index('statu');
            $table->index('uuid');
        });

        Schema::create('order_confirmation_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_confirmation_id')
                ->constrained('order_confirmations')->cascadeOnDelete();

            // Traçabilité uniquement : jamais utilisé pour l'affichage ni les calculs.
            // nullOnDelete et pas cascade — supprimer une ligne de commande ne doit
            // pas amputer un ARC déjà envoyé.
            $table->foreignId('order_line_id')->nullable()
                ->constrained('order_lines')->nullOnDelete();

            $table->integer('ordre')->default(0);

            // Valeurs figées.
            $table->string('code')->nullable();
            $table->string('label');
            $table->decimal('qty', 10, 3)->default(0);
            $table->unsignedBigInteger('methods_units_id')->nullable();
            $table->string('unit_label')->nullable();
            $table->decimal('selling_price', 10, 3)->default(0);
            $table->decimal('discount', 10, 3)->default(0);
            $table->unsignedBigInteger('accounting_vats_id')->nullable();
            $table->decimal('vat_rate', 10, 3)->default(0);
            $table->date('delivery_date')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('order_confirmation_id');
            $table->index('order_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmation_lines');
        Schema::dropIfExists('order_confirmations');
    }
};
