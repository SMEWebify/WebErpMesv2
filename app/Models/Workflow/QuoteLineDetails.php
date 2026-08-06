<?php

namespace App\Models\Workflow;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteLineDetails extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment.
    //
    // cad_file / cam_file / cad_file_path / cam_file_path : référence "locale" à un
    // fichier stocké hors GED (chemin réseau atelier, dossier CAM partagé...). Ces
    // colonnes ne passent pas par le pivot fileables et ne sont pas gérées par
    // FileStorageService — elles restent utiles quand la source de vérité du CAO
    // est sur un partage externe. Pour tout upload passant par l'API ou l'UI, on
    // utilise à la place la relation files() de QuoteLines (GED unifiée).
    protected $fillable= [
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

    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }
}
