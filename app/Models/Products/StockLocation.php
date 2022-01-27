<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = ['code',
                        'label', 
                        'stocks_id',
                        'user_id',
                        'end_date',
                        'comment',];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
  
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
