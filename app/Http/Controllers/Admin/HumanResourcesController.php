<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsSection;
use App\Models\Admin\UserEmploymentContracts;
use App\Http\Requests\Admin\StoreUserEmploymentContractRequest;
use App\Http\Requests\Admin\UpdateUserEmploymentContractRequest;

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
        $UserEmploymentContracts = UserEmploymentContracts::where('user_id', $id)->get();
        $Roles = Role::all();
        //DB information mustn't be empty.
        $Factory = Factory::first();
        return view('admin/users-show', [
            'Factory' => $Factory,
            'User' => $User,
            'Roles' => $Roles,
            'userSelect' => $userSelect,
            'SectionsSelect' =>  $SectionsSelect,
            'UserEmploymentContracts' =>  $UserEmploymentContracts,
        ]);
    }

    /**
     * @param Request $request
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

        DB::table('model_has_roles')->where('model_id',$id)->delete();
        $UserUpdate->save();

        $UserUpdate->assignRole($request->role);

        return redirect()->route('human.resources.show.user', ['id' => $id])->with('success', 'Successfully updated user inforamations');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function storeUserEmploymentContract(StoreUserEmploymentContractRequest $request)
    {
        // Create Line
        $UserEmploymentContract = UserEmploymentContracts::create([
                                                                    'user_id'=>$request->user_id, 
                                                                    'statu'=>$request->statu,  
                                                                    'methods_section_id'=>$request->methods_section_id, 
                                                                    'signature_date'=>$request->signature_date,  
                                                                    'type_of_contract'=>$request->type_of_contract,  
                                                                    'start_date'=>$request->start_date,  
                                                                    'duration_trial_period'=>$request->duration_trial_period,  
                                                                    'end_date'=>$request->end_date,  
                                                                    'weekly_duration'=>$request->weekly_duration,  
                                                                    'position'=>$request->position,  
                                                                    'coefficient'=>$request->coefficient,  
                                                                    'hourly_gross_salary'=>$request->hourly_gross_salary,  
                                                                    'minimum_monthly_salary'=>$request->minimum_monthly_salary,  
                                                                    'annual_gross_salary'=>$request->annual_gross_salary,  
                                                                    'end_of_contract_reason'=>$request->end_of_contract_reason,
                                                                ]);

        return redirect()->route('human.resources.show.user', ['id' => $UserEmploymentContract->user_id])->with('success', 'Successfully add contract');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function updateUserEmploymentContract(UpdateUserEmploymentContractRequest $request)
    {
        // Create Line
        $UserEmploymentContract = UserEmploymentContracts::findOrFail($request->id);
        $UserEmploymentContract->statu  =$request->statu;
        $UserEmploymentContract->methods_section_id =$request->methods_section_id;
        $UserEmploymentContract->signature_date  =$request->signature_date;
        $UserEmploymentContract->type_of_contract  =$request->type_of_contract; 
        $UserEmploymentContract->start_date = $request->start_date; 
        $UserEmploymentContract->duration_trial_period = $request->duration_trial_period; 
        $UserEmploymentContract->end_date = $request->end_date; 
        $UserEmploymentContract->weekly_duration =$request->weekly_duration; 
        $UserEmploymentContract->position = $request->position;
        $UserEmploymentContract->coefficient =$request->coefficient;
        $UserEmploymentContract->hourly_gross_salary =$request->hourly_gross_salary;
        $UserEmploymentContract->minimum_monthly_salary =$request->minimum_monthly_salary;
        $UserEmploymentContract->annual_gross_salary =$request->annual_gross_salary;
        $UserEmploymentContract->end_of_contract_reason =$request->end_of_contract_reason;
        $UserEmploymentContract->save();

        return redirect()->route('human.resources.show.user', ['id' => $UserEmploymentContract->user_id])->with('success', 'Successfully update contract');
    }
    
}
