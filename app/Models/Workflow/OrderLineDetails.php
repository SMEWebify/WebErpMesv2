<?php

namespace App\Models\Workflow;

use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderLineDetails extends Model
{
    use HasFactory;
    
    // Fillable attributes for mass assignment
    protected $fillable= [
                            'order_lines_id',
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
                            'bend_count',
                            'material_loss_rate', 
                            'cad_file',
                            'cam_file',
                            'cad_file_path',
                            'cam_file_path',
                            'picture', 
                            'internal_comment',
                            'external_comment',
                            'custom_requirements',
                        ];

    protected $casts = [
        'custom_requirements' => 'array',
    ];

    public function OrderLines()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }
}
