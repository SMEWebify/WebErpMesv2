<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsSection;

class HumanResourcesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $Users = User::orderBy('id')->paginate(20);
        $userSelect = User::select('id', 'name')->get();
        $SectionsSelect = MethodsSection::select('id', 'label')->orderBy('label')->get();
        //DB information mustn't be empty.
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return view('admin/human-resources-index', [
            'Factory' => $Factory,
            'Users' => $Users,
            'userSelect' => $userSelect,
            'SectionsSelect' =>  $SectionsSelect,
        ]);
    }

    /**
     * @return View
     */
    public function ShowUser($id)
    {
        $User = User::find($id);
        $userSelect = User::select('id', 'name')->get();
        $SectionsSelect = MethodsSection::select('id', 'label')->orderBy('label')->get();
        //DB information mustn't be empty.
        $Factory = Factory::first();
        return view('admin/users-show', [
            'Factory' => $Factory,
            'User' => $User,
            'userSelect' => $userSelect,
            'SectionsSelect' =>  $SectionsSelect,
        ]);
    }

    /**
     * @return View
     */
    public function UpdateUser(Request $request, int $id)
    {
        $UserUpdate = User::findOrFail($id);
        $UserUpdate->job_title = $request->job_title;
        $UserUpdate->pay_grade = $request->pay_grade;
        $UserUpdate->work_station_id = $request->work_station_id; 
        $UserUpdate->joined_date = $request->joined_date;
        $UserUpdate->confirmation_date = $request->confirmation_date;
        $UserUpdate->termination_date = $request->termination_date;
        $UserUpdate->employment_status = $request->employment_status; 
        $UserUpdate->supervisor_id = $request->supervisor_id; 
        $UserUpdate->section_id = $request->section_id;
        $UserUpdate->statu = $request->statu;

        $UserUpdate->save();

        return redirect()->route('human.resources.show.user', ['id' => $id])->with('success', 'Successfully updated user inforamations');
    }
}
