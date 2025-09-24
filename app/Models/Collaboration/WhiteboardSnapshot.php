<?php

namespace App\Models\Collaboration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteboardSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'whiteboard_id',
        'state',
        'created_by',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function whiteboard()
    {
        return $this->belongsTo(Whiteboard::class);
    }
}
