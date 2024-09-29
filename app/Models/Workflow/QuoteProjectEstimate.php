<?php

namespace App\Models\Workflow;

use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Model;

class QuoteProjectEstimate extends Model
{
    protected $fillable = [
        'quotes_id',
        'client_requirements',
        'show_client_requirements_on_pdf',
        'layout_plan',
        'layout_improvements',
        'show_layout_on_pdf',
        'materials_finishes',
        'show_materials_on_pdf',
        'logistics',
        'logistics_cost',
        'show_logistics_on_pdf',
        'coordination_with_contractors',
        'contractors_cost',
        'show_contractors_on_pdf',
        'waste_management',
        'waste_management_cost',
        'show_waste_on_pdf',
        'taxes_and_fees',
        'taxes_cost',
        'show_taxes_on_pdf',
        'work_start_date',
        'work_end_date',
        'contingency_days',
        'options_variants',
        'show_options_on_pdf',
        'insurance_liability',
        'insurance_cost',
        'show_insurance_on_pdf',
        'revision_clause',
        'show_revision_clause_on_pdf',
        'warranty_clause',
        'show_warranty_clause_on_pdf',
        'professional_presentation',
        'show_presentation_on_pdf'
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id');
    }
}
