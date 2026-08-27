<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Quality\QualityControlDevice;
use App\Http\Requests\Quality\StoreQualityControlDeviceRequest;
use App\Http\Requests\Quality\UpdateQualityControlDeviceRequest;

class QualityControlDeviceController extends Controller
{
    private const PICTURE_DIRECTORY = 'quality-devices';

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityControlDeviceRequest $request)
    {
        $controlDevice = QualityControlDevice::create($this->prepareDeviceData($request, [
            'code',
            'label',
            'service_id',
            'user_id',
            'serial_number',
            'calibrated_at',
            'calibration_due_at',
            'calibration_status',
            'calibration_provider',
            'location',
            'capability_index',
        ]));

        if ($request->hasFile('picture')) {
            $filename = $this->storePicture($request->file('picture'));
            $controlDevice->update(['picture' => $filename]);
        }

        return redirect()->route('quality')->with('success', 'Successfully created device.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
    */
    public function update(UpdateQualityControlDeviceRequest $request)
    {
        $controlDevice = QualityControlDevice::findOrFail($request->id);
        $controlDevice->update($this->prepareDeviceData($request, [
            'label',
            'service_id',
            'user_id',
            'serial_number',
            'calibrated_at',
            'calibration_due_at',
            'calibration_status',
            'calibration_provider',
            'location',
            'capability_index',
        ]));

        return redirect()->route('quality')->with('success', 'Successfully updated device.');
    }

    /**
     * JSON: paginated list of devices.
     */
    public function jsonList(Request $request)
    {
        $devices = QualityControlDevice::with(['UserManagement', 'service'])
            ->orderBy('id')
            ->paginate(10);

        $devices->getCollection()->transform(fn ($d) => $this->deviceToArray($d));

        return response()->json($devices);
    }

    /**
     * JSON: create a device (multipart/form-data).
     */
    public function jsonStore(StoreQualityControlDeviceRequest $request)
    {
        $data = $this->prepareDeviceData($request, [
            'code', 'label', 'service_id', 'user_id', 'serial_number',
            'calibrated_at', 'calibration_due_at', 'calibration_status',
            'calibration_provider', 'location', 'capability_index',
        ]);

        $device = QualityControlDevice::create($data);

        if ($request->hasFile('picture')) {
            $filename = $this->storePicture($request->file('picture'));
            $device->update(['picture' => $filename]);
        }

        $device->load(['UserManagement', 'service']);

        return response()->json(['device' => $this->deviceToArray($device)], 201);
    }

    /**
     * Serve an uploaded device picture behind the auth middleware.
     * Filenames in DB are server-generated UUIDs, but basename() defends
     * against future direct calls with untrusted input.
     */
    public function servePicture(string $filename)
    {
        $path = self::PICTURE_DIRECTORY . '/' . basename($filename);

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }

    /**
     * Store an uploaded device picture under a UUID name on the private disk.
     * Never trusts the client-provided filename or extension.
     */
    private function storePicture(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: 'bin';
        $filename  = Str::uuid() . '.' . $extension;
        Storage::disk('local')->putFileAs(self::PICTURE_DIRECTORY, $file, $filename);
        return $filename;
    }

    /**
     * JSON: update a device.
     */
    public function jsonUpdate(Request $request, $id)
    {
        $device = QualityControlDevice::findOrFail($id);
        $device->update($this->prepareDeviceData($request, [
            'label', 'service_id', 'user_id', 'serial_number',
            'calibrated_at', 'calibration_due_at', 'calibration_status',
            'calibration_provider', 'location', 'capability_index',
        ]));

        $device->load(['UserManagement', 'service']);

        return response()->json(['device' => $this->deviceToArray($device)]);
    }

    protected function prepareDeviceData($request, array $fields): array
    {
        $data = $request->only($fields);

        foreach (['calibrated_at', 'calibration_due_at'] as $field) {
            if (array_key_exists($field, $data) && empty($data[$field])) {
                $data[$field] = null;
            }
        }

        foreach (['calibration_status', 'calibration_provider', 'location'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        if (array_key_exists('capability_index', $data) && $data['capability_index'] === '') {
            $data['capability_index'] = null;
        }

        return $data;
    }

    private function deviceToArray(QualityControlDevice $d): array
    {
        return [
            'id'                   => $d->id,
            'code'                 => $d->code,
            'label'                => $d->label,
            'service_id'           => $d->service_id,
            'service_label'        => optional($d->service)->label,
            'user_id'              => $d->user_id,
            'user_name'            => optional($d->UserManagement)->name,
            'serial_number'        => $d->serial_number,
            'calibrated_at'        => optional($d->calibrated_at)->toDateString(),
            'calibration_due_at'   => optional($d->calibration_due_at)->toDateString(),
            'calibration_status'   => $d->calibration_status,
            'calibration_provider' => $d->calibration_provider,
            'calibration_alert_class' => $d->calibration_alert_class,
            'location'             => $d->location,
            'capability_index'     => $d->capability_index,
            'created_at'           => optional($d->created_at)->toDateString(),
        ];
    }
}
