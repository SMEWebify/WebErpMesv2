<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityControlDevice;
use App\Http\Requests\Quality\StoreQualityControlDeviceRequest;

class QualityControlDeviceController extends Controller
{
    //
    public function store(StoreQualityControlDeviceRequest $request)
    {
        $Service =  QualityControlDevice::create($request->only('CODE', 'LABEL','service_id', 'user_id','SERIAL_NUMBER', 'PICTURE'));
        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/quality','public');
            $Service->update(['PICTURE' => $path]);
        }
        return redirect()->route('quality')->with('success', 'Successfully created device.');
    }
}
