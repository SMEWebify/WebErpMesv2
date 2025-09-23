<?php

namespace App\Http\Controllers\Quality;

use App\Models\Quality\QualityControlDevice;
use App\Http\Requests\Quality\StoreQualityControlDeviceRequest;
use App\Http\Requests\Quality\UpdateQualityControlDeviceRequest;

class QualityControlDeviceController extends Controller
{
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

        if($request->hasFile('picture')){
            $controlDevice = QualityControlDevice::findOrFail($controlDevice->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/quality'), $filename);
            $controlDevice->update(['picture' => $filename]);
            $controlDevice->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
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

    /* if($request->hasFile('picture')){
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $controlDevice->update(['picture' => $filename]);
            $controlDevice->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }*/

        return redirect()->route('quality')->with('success', 'Successfully updated device.');
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
}
