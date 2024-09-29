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
        Schema::create('quote_project_estimates', function (Blueprint $table) {
            $table->id();
            $table->integer('quotes_id');
            // Analyse des Besoins du Client
            $table->text('client_requirements')->nullable();
            $table->boolean('show_client_requirements_on_pdf')->default(false);

            // Plan d'Agencement
            $table->string('layout_plan')->nullable();
            $table->text('layout_improvements')->nullable();
            $table->boolean('show_layout_on_pdf')->default(false);

            // Matériaux et Finitions
            $table->text('materials_finishes')->nullable();
            $table->boolean('show_materials_on_pdf')->default(false);

            // Logistique et Livraison
            $table->text('logistics')->nullable();
            $table->decimal('logistics_cost', 10, 2)->nullable();
            $table->boolean('show_logistics_on_pdf')->default(false);

            // Coordination avec d'autres Entrepreneurs
            $table->text('coordination_with_contractors')->nullable();
            $table->decimal('contractors_cost', 10, 2)->nullable();
            $table->boolean('show_contractors_on_pdf')->default(false);

            // Gestion des Déchets
            $table->text('waste_management')->nullable();
            $table->decimal('waste_management_cost', 10, 2)->nullable();
            $table->boolean('show_waste_on_pdf')->default(false);

            // Taxes et Frais Additionnels
            $table->text('taxes_and_fees')->nullable();
            $table->decimal('taxes_cost', 10, 2)->nullable();
            $table->boolean('show_taxes_on_pdf')->default(false);

            // Échéancier de Travail
            $table->date('work_start_date')->nullable();
            $table->date('work_end_date')->nullable();
            $table->integer('contingency_days')->nullable();

            // Options et Variantes
            $table->text('options_variants')->nullable();
            $table->boolean('show_options_on_pdf')->default(false);

            // Assurance et Responsabilité
            $table->text('insurance_liability')->nullable();
            $table->decimal('insurance_cost', 10, 2)->nullable();
            $table->boolean('show_insurance_on_pdf')->default(false);

            // Clause de Révision
            $table->text('revision_clause')->nullable();
            $table->boolean('show_revision_clause_on_pdf')->default(false);

            // Clause de Garantie
            $table->text('warranty_clause')->nullable();
            $table->boolean('show_warranty_clause_on_pdf')->default(false);

            // Présentation Professionnelle
            $table->string('professional_presentation')->nullable();
            $table->boolean('show_presentation_on_pdf')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes_project_estimates');
    }
};
