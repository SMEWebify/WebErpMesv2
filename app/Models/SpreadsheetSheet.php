<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpreadsheetSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'spreadsheet_id',
        'name',
        'order',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function spreadsheet()
    {
        return $this->belongsTo(Spreadsheet::class);
    }
}
