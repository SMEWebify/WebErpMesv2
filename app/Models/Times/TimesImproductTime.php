<?php

namespace App\Models\Times;

use App\Models\Times\TimesMachineEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimesImproductTime extends Model
{
    use HasFactory;

    protected $fillable = ['LABEL',  'MACHINE_statuS',  'RESOURCE_REQUIRED',  'MASK_TIME'];

    public function MachineEvent()
    {
        return $this->belongsTo(TimesMachineEvent::class, 'times_machine_events_id');
    }


}    
