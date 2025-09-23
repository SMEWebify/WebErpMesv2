<?php

namespace App\Notifications;

use App\Models\Quality\QualityControlDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QualityDeviceCalibrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private QualityControlDevice $device, private string $alertType)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'quality_control_device_id' => $this->device->id,
            'code' => $this->device->code,
            'label' => $this->device->label,
            'alert_type' => $this->alertType,
            'calibration_due_at' => optional($this->device->calibration_due_at)->toDateString(),
            'calibration_status' => $this->device->calibration_status,
            'calibration_provider' => $this->device->calibration_provider,
            'location' => $this->device->location,
        ];
    }
}
