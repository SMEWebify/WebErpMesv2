<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = ['custom_field_id', 'entity_id', 'entity_type', 'value'];

    public function field()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function entity()
    {
        return $this->morphTo();
    }
}
