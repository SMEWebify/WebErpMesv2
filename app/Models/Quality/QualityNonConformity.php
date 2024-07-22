<?php

namespace App\Models\Quality;

use App\Models\File;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Workflow\DeliveryLines;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityNonConformity extends Model
{
    use HasFactory;
    
    protected $fillable = ['code',
                        'label', 
                        'statu',
                        'type', 
                        'user_id',
                        'service_id',  
                        'failure_id',  
                        'failure_comment', 
                        'causes_id', 
                        'causes_comment',  
                        'correction_id',  
                        'correction_comment', 
                        'causes_comment',  
                        'companie_id',  
                        'order_lines_id', 
                        'deliverys_id',
                        'delivery_line_id', 
                        'task_id',
                        'qty',
                        'resolution_date'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Action()
    {
        return $this->hasMany(QualityAction::class);
    }

    public function Derogation()
    {
        return $this->hasMany(QualityDerogation::class);
    }

    public function Failure()
    {
        return $this->belongsTo(QualityFailure::class, 'failure_id');
    }

    public function Cause()
    {
        return $this->belongsTo(QualityCause::class, 'causes_id');
    }

    public function Correction()
    {
        return $this->belongsTo(QualityCorrection::class, 'correction_id');
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companie_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    public function delivery()
    {
        return $this->belongsTo(Deliverys::class, 'deliverys_id');
    }

    public function DeliveryLines()
    {
        return $this->belongsTo(DeliveryLines::class, 'delivery_line_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
