<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable = ['name', 'type', 'related_type', 'category', 'options'];

    protected $casts = [
        'options' => 'array',
    ];

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
