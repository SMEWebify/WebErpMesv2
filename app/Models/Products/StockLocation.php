<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',
                        'LABEL', 
                        'stocks_id',
                        'user_id',
                        'END_DATE',
                        'COMMENT',];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
  
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
