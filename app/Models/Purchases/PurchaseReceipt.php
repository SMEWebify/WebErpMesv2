<?php

namespace App\Models\Purchases;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReceipt extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['code', 
                            'label', 
                            'companies_id', 
                            'delivery_note_number',  
                            'statu',  
                            'user_id',
                            'comment',
                            'reception_controlled',  
                            'reception_control_date',
                            'reception_control_user_id',
                        ];

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function UserReceptionControl()
    {
        return $this->belongsTo(User::class, 'reception_control_user_id');
    }

    public function PurchaseReceiptLines()
    {
        return $this->hasMany(PurchaseReceiptLines::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class, 'purchase_receipts_id');
    }

    public function GetshortCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function GetPrettyControlDateAttribute()
    {
        return date('d F Y', strtotime($this->reception_control_date));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
