<?php

namespace App\Models\Companies;

use App\Traits\HasDefaultTrait;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CompaniesContacts extends Model
{
    use HasFactory, HasDefaultTrait, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['companies_id', 'ordre', 'civility', 'first_name','name','function','number','mobile','mail',  'default'];

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'name', 'mail', 'function', 'number', 'mobile'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Contact {$event}");
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchases::class);
    }
}
