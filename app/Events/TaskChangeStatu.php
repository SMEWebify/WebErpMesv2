<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskChangeStatu
{
    use Dispatchable, SerializesModels;

    public $taskId;

    public function __construct($taskId)
    {
        $this->taskId = $taskId;
    }
}
