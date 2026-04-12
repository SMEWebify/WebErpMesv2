<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditProcess extends Model
{
    use SoftDeletes;

    protected $table = 'audit_processes';

    protected $fillable = [
        'name',
        'description',
        'responsible_user_id',
    ];

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function schedules()
    {
        return $this->hasMany(AuditSchedule::class, 'audit_process_id');
    }

    public function checklists()
    {
        return $this->hasMany(AuditChecklist::class, 'audit_process_id');
    }
}
