<?php

namespace App\Models\Collaboration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whiteboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'state',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function snapshots()
    {
        return $this->hasMany(WhiteboardSnapshot::class);
    }

    public function files()
    {
        return $this->hasMany(WhiteboardFile::class);
    }
}
