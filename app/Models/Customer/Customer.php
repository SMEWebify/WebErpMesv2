<?php

namespace App\Models\Customer;

use App\Models\Companies\Companies;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'companies_contacts';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'companies_id',
        'ordre',
        'civility',
        'first_name',
        'name',
        'function',
        'number',
        'mobile',
        'mail',
        'default',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * Automatically hash passwords.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if (! empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Customer company relation.
     */
    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    /**
     * Orders associated with the customer.
     */
    public function orders()
    {
        return $this->hasMany(Orders::class, 'companies_contacts_id');
    }

    /**
     * Deliveries associated with the customer.
     */
    public function deliveries()
    {
        return $this->hasMany(Deliverys::class, 'companies_contacts_id');
    }

    /**
     * Invoices associated with the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'companies_contacts_id');
    }
}
