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
        $Service =  QualityControlDevice::create($request->only('code', 'label','service_id', 'user_id','serial_number', 'picture'));
        if($request->hasFile('picture')){
            $path = $request->picture->store('images/quality','public');
            $Service->update(['picture' => $path]);
        }
        return redirect()->route('quality')->with('success', 'Successfully created device.');
    }
}
