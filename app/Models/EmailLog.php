<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailLog extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'to',
        'subject',
        'message',
        'attachment',
        'attachment_original_name',
        'status',
        'sent_at',
        'error',
        'sent_by_user_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function emailable()
    {
        return $this->morphTo();
    }

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by_user_id');
    }
}
