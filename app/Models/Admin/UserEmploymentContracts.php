<?php

namespace App\Models\Admin;

use App\Models\User;
use App\Models\Methods\MethodsSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserEmploymentContracts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'statu',
        'methods_section_id',
        'signature_date',
        'type_of_contract',
        'start_date',
        'duration_trial_period',
        'end_date',
        'weekly_duration',
        'position',
        'coefficient',
        'hourly_gross_salary',
        'minimum_monthly_salary',
        'annual_gross_salary',
        'end_of_contract_reason',
        'coment',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function section()
    {
        return $this->belongsTo(MethodsSection::class);
    }
}
