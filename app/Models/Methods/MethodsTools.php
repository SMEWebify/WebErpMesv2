<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsTools extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',  'label',  'ETAT', 'cost' , 'picture',  'end_date',  'comment',  'qty', 'availability'];

    protected $casts = [
        'availability' => 'boolean',
    ];

    public function Task()
    {
        return $this->hasMany(Task::class);
    }


    public function products()
    {
        return $this->belongsToMany(
            Products::class,
            'product_tool',
            'methods_tools_id',
            'product_id'
        )->withTimestamps();
    }
  
    public function scopeAvailable($query)
    {
        return $query->where('availability', true)
                    ->where('ETAT', 1);
    }
}
