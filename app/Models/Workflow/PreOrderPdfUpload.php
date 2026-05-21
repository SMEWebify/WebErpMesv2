<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrderPdfUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'stored_name',
        'checksum',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}
