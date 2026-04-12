<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessDiagram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'nodes',
        'edges',
        'created_by',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
