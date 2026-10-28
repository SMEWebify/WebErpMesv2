<?php

namespace App\Models\Quality;

use App\Models\User;
use App\Models\File;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Quality\QualityNonConformity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityDerogation extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_INTERNAL = 1;
    public const TYPE_EXTERNAL = 2;

    public const STATU_IN_PROGRESS = 1;
    public const STATU_WAITING_CUSTOMER = 2;
    public const STATU_VALIDATED = 3;
    public const STATU_CANCELED = 4;

    /** Statuts d'une dérogation qui n'a pas encore reçu de décision. */
    public const OPEN = [self::STATU_IN_PROGRESS, self::STATU_WAITING_CUSTOMER];

    public const VALIDATE_PERMISSION = 'quality-derogation-validate';

    // Fillable attributes for mass assignment
    protected $fillable= ['code',
                            'label',
                            'statu',
                            'type',
                            'user_id',
                            'pb_descp',
                            'proposal',
                            'reply',
                            'customer_reply_date',
                            'customer_signatory',
                            'quality_non_conformitie_id',
                            'companies_id',
                            'companies_contacts_id',
                            'order_lines_id',
                            'decision'
                        ];

    protected $casts = [
        'customer_reply_date' => 'date',
        'validated_at'        => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'label', 'statu', 'type', 'user_id', 'pb_descp', 'proposal', 'reply',
                'customer_reply_date', 'customer_signatory', 'quality_non_conformitie_id',
                'companies_id', 'companies_contacts_id', 'order_lines_id', 'decision',
                'validated_by', 'validated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Dérogation {$event}");
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function QualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformitie_id');
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    public function signatures()
    {
        return $this->hasMany(QualityDerogationSignature::class, 'quality_derogation_id');
    }

    // Relationship with the files associated with the derogation (GED)
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    public function isExternal(): bool
    {
        return (int) $this->type === self::TYPE_EXTERNAL;
    }

    public function isValidated(): bool
    {
        return (int) $this->statu === self::STATU_VALIDATED;
    }

    /**
     * Empreinte de ce que le client accepte : une enveloppe envoyée sur une autre
     * empreinte ne correspond plus à la dérogation et doit être annulée.
     */
    public function contentHash(): string
    {
        return hash('sha256', json_encode([
            $this->label,
            $this->pb_descp,
            $this->proposal,
            (int) $this->companies_id,
            (int) $this->companies_contacts_id,
            (int) $this->order_lines_id,
            (int) $this->quality_non_conformitie_id,
        ]));
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
