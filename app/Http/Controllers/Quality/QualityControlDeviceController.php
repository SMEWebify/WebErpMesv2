<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
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

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityControlDeviceRequest $request)
    {
        
        $ControlDevice = QualityControlDevice::find($request->id);
        $ControlDevice->label=$request->label;
        $ControlDevice->service_id=$request->service_id;
        $ControlDevice->user_id=$request->user_id;
        $ControlDevice->serial_number=$request->serial_number;
        $ControlDevice->save();

    /* if($request->hasFile('picture')){
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $ControlDevice->update(['picture' => $filename]);
            $ControlDevice->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }*/

        return redirect()->route('quality')->with('success', 'Successfully updated device.');
    }
}
