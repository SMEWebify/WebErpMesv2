<?php

namespace App\Models\Products;

use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SerialNumberComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_serial_id',
        'component_serial_id',
        'task_id',
    ];

    public function parentSerial()
    {
        return $this->belongsTo(SerialNumbers::class, 'parent_serial_id');
    }

    public function componentSerial()
    {
        return $this->belongsTo(SerialNumbers::class, 'component_serial_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
