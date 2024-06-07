<?php

namespace App\Models\Workflow;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteLineDetails extends Model
{
    use HasFactory;

    protected $fillable = [
                            'quote_lines_id',
                            'x_size', 
                            'y_size', 
                            'z_size', 
                            'x_oversize',
                            'y_oversize',
                            'z_oversize',
                            'diameter',
                            'diameter_oversize',
                            'material', 
                            'thickness', 
                            'finishing',
                            'weight', 
                            'material_loss_rate', 
                            'cad_file',  
                            'picture', 
                            'internal_comment', 
                            'external_comment', 
                        ];

    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }
}
