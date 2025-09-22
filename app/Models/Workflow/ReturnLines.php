<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Planning\Task;
use App\Models\Workflow\DeliveryLines;


class ReturnLines extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'delivery_line_id',
        'original_task_id',
        'rework_task_id',
        'qty',
        'issue_description',
        'rework_instructions',
    ];

    public function ReturnDocument()
    {
        return $this->belongsTo(Returns::class, 'return_id');
    }

    public function deliveryLine()
    {
        return $this->belongsTo(DeliveryLines::class, 'delivery_line_id');
    }

    public function originalTask()
    {
        return $this->belongsTo(Task::class, 'original_task_id');
    }

    public function reworkTask()
    {
        return $this->belongsTo(Task::class, 'rework_task_id');
    }
}
