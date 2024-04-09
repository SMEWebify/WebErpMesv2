<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'related_type'];

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
