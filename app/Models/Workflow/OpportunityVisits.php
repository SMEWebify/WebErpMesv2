<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Visite sur site d'une opportunité, saisie depuis le téléphone.
 *
 * Les photos ne sont pas portées ici mais par la GED de l'opportunité, marquées
 * du hashtag {@see self::photoHashtag()} : elles restent visibles dans l'onglet
 * Documents sans qu'on ait à dupliquer le stockage.
 */
class OpportunityVisits extends Model
{
    public const STATU_DRAFT = 1;
    public const STATU_VALIDATED = 2;

    protected $fillable = [
        'opportunities_id',
        'user_id',
        'opportunities_events_logs_id',
        'visited_at',
        'notes',
        'measurements',
        'transcript',
        'report',
        'statu',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'measurements' => 'array',
        'statu' => 'integer',
        'user_id' => 'integer',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunities::class, 'opportunities_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(OpportunitiesEventsLogs::class, 'opportunities_events_logs_id');
    }

    public function isDraft(): bool
    {
        return $this->statu === self::STATU_DRAFT;
    }

    public function photoHashtag(): string
    {
        return 'visite-' . $this->id;
    }
}
