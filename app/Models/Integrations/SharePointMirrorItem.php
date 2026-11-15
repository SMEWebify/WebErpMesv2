<?php

namespace App\Models\Integrations;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une lignée de fichier de la GED recopiée dans le dossier SharePoint d'une entité.
 *
 * Écrite seulement par App\Services\Integrations\SharePoint\SharePointMirror.
 */
class SharePointMirrorItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    /** Rien à recopier : fichier ou entité disparus, trame de devis. */
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'sharepoint_mirror_items';

    protected $guarded = ['id'];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
