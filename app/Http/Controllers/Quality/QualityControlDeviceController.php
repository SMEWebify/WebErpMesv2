<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityControlDevice;
use App\Http\Requests\Quality\StoreQualityControlDeviceRequest;

class QualityControlDeviceController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreQualityControlDeviceRequest $request)
    {
        $ControlDevice =  QualityControlDevice::create($request->only('code', 'label','service_id', 'user_id','serial_number'));

        if($request->hasFile('picture')){
            $ControlDevice = QualityControlDevice::findOrFail($ControlDevice->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/quality'), $filename);
            $ControlDevice->update(['picture' => $filename]);
            $ControlDevice->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('quality')->with('success', 'Successfully created device.');
    }
}
