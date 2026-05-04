<?php

use Illuminate\Database\Migrations\Migration;

// Migration documentaire — aucun changement de schéma.
//
// Valeurs du champ tasks.origin (string nullable) :
//   0 : WEM Enterprise task screen
//   1 : Import of standard task (task or BOM)
//   2 : Import of standard breakdown
//   3 : Breakdown of composed component
//   4 : Created from a PO
//   5 : Duplication
//   6 : Wizard to convert a quotation into a sales order
//   7 : Interface RadQuote
//   8 : Interface Nest4Quote (API /api/quote)

return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
