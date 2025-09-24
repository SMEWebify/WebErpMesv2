<?php

namespace App\Models\Collaboration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WhiteboardFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'whiteboard_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $appends = [
        'url',
    ];

    protected $hidden = [
        'path',
    ];

    public function whiteboard()
    {
        return $this->belongsTo(Whiteboard::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }
}
