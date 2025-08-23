<?php

namespace App\Services;

use App\Models\Products\SerialNumberComponent;

class SerialNumberComponentService
{
    public function linkComponent(int $parentSerialId, int $componentSerialId, int $taskId): SerialNumberComponent
    {
        return SerialNumberComponent::create([
            'parent_serial_id' => $parentSerialId,
            'component_serial_id' => $componentSerialId,
            'task_id' => $taskId,
        ]);
    }
}
