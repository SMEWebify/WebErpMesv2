<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrderImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'checksum',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function preOrders()
    {
        return $this->hasMany(PreOrder::class);
    }
}

