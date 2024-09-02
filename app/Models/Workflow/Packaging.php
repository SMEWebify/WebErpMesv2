<?php

namespace App\Models\Workflow;

use App\Models\User;
use App\Models\Workflow\Deliverys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Packaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverys_id',
        'code',
        'type',
        'status',
        'user_id',
        'gross_weight',
        'net_weight',
        'length',
        'width',
        'height',
        'comment',
        'packing_date',
        'loaded_date',
        'load_comment'
    ];

    public function delivery()
    {
        return $this->belongsTo(Deliverys::class, 'deliverys_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        // Mutator pour arrondir gross_weight à 2 décimales
        public function setGrossWeightAttribute($value)
        {
            $this->attributes['gross_weight'] = round($value, 2);
        }
    
        // Mutator pour arrondir net_weight à 2 décimales
        public function setNetWeightAttribute($value)
        {
            $this->attributes['net_weight'] = round($value, 2);
        }
    
        // Mutator pour arrondir length à 2 décimales
        public function setLengthAttribute($value)
        {
            $this->attributes['length'] = round($value, 2);
        }
    
        // Mutator pour arrondir width à 2 décimales
        public function setWidthAttribute($value)
        {
            $this->attributes['width'] = round($value, 2);
        }
    
        // Mutator pour arrondir height à 2 décimales
        public function setHeightAttribute($value)
        {
            $this->attributes['height'] = round($value, 2);
        }
}
