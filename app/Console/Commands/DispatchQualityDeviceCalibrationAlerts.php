<?php

namespace App\Console\Commands;

use App\Models\Quality\QualityControlDevice;
use App\Notifications\QualityDeviceCalibrationNotification;
use Illuminate\Console\Command;

class DispatchQualityDeviceCalibrationAlerts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quality:dispatch-calibration-alerts';

    /**
     * The console command description.
     */
    protected $description = 'Notify responsible users when a quality control device calibration is due soon or overdue.';

    public function handle(): int
    {
        $dueSoonDevices = QualityControlDevice::with('UserManagement')
            ->calibrationDueSoon()
            ->get();

        $overdueDevices = QualityControlDevice::with('UserManagement')
            ->calibrationOverdue()
            ->get();

        $this->notifyUsers($dueSoonDevices, 'due_soon');
        $this->notifyUsers($overdueDevices, 'overdue');

        $this->info('Calibration alerts dispatched successfully.');

        return Command::SUCCESS;
    }

    protected function notifyUsers(iterable $devices, string $alertType): void
    {
        foreach ($devices as $device) {
            $user = $device->UserManagement;

            if (! $user) {
                continue;
            }

            $user->notify(new QualityDeviceCalibrationNotification($device, $alertType));
        }
    }
}
