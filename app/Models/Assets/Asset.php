<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Accounting\AccountingEntry;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'category',
        'acquisition_value',
        'acquisition_date',
        'depreciation_duration',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_value' => 'decimal:2',
        'depreciation_duration' => 'integer',
    ];

    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }
}
