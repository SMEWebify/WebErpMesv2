<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentCodeTemplate;

class DocumentCodeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['document_type' => 'quote',               'template' => 'QT-{yyyy}-{mm}-{dd}-{id}'],
            ['document_type' => 'order',               'template' => '{dd}-OR-{id}'],
            ['document_type' => 'internal-order',      'template' => 'INT-{dd}'],
            ['document_type' => 'invoice',             'template' => '{dd}{mm}{yy}{id(2)}'],
            ['document_type' => 'credit-note',         'template' => 'AV-{yyyy}-{id}'],
            ['document_type' => 'return',              'template' => 'RET-{yyyy}-{id}'],
            ['document_type' => 'delivery',            'template' => 'BON-{id}'],
            ['document_type' => 'company',             'template' => 'STE-{id}'],
            ['document_type' => 'purchase',            'template' => 'ACH-{id}'],
            ['document_type' => 'purchase-quotation',  'template' => 'DEVIS-ACH-{id}'],
            ['document_type' => 'purchase-receipt',    'template' => 'RECPT-{yyyy}-{id}'],
            ['document_type' => 'purchase-invoice',    'template' => 'FACFOUR-{id}'],
            ['document_type' => 'action',              'template' => 'ACT-{id}'],
            ['document_type' => 'derogation',          'template' => 'DER-{yyyy}-{id}'],
            ['document_type' => 'non-conformities',    'template' => 'NC-{yyyy}-{id}'],
            ['document_type' => 'inspection-projects', 'template' => 'INSP-{yyyy}-{id}'],
        ];

        foreach ($templates as $data) {
            DocumentCodeTemplate::firstOrCreate(
                ['document_type' => $data['document_type']],
                ['template' => $data['template'], 'reset_period' => 'none']
            );
        }
    }
}
