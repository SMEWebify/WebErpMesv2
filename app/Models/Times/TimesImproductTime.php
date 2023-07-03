<?php

namespace App\Models\Times;

use App\Models\Times\TimesMachineEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimesImproductTime extends Model
{
    use HasFactory;

    protected $fillable = ['label',  'times_machine_events_id',  'resources_required',  'mask_time'];

    public function MachineEvent()
    {
        return $this->belongsTo(TimesMachineEvent::class, 'times_machine_events_id');
    }


}    
