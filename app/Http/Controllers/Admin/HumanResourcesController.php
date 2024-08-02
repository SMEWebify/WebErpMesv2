<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserExpense;
use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\UserExpenseReport;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\UserExpenseCategory;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\UserEmploymentContracts;
use App\Http\Requests\Admin\StoreUserExpenseRequest;
use App\Http\Requests\Admin\UpdateUserExpenseRequest;
use App\Http\Requests\Admin\StoreUserExpenseReportRequest;
use App\Http\Requests\Admin\UpdateUserExpenseReportRequest;
use App\Http\Requests\Admin\StoreUserExpenseCategorieRequest;
use App\Http\Requests\Admin\UpdateUserExpenseCategorieRequest;
use App\Http\Requests\Admin\StoreUserEmploymentContractRequest;
use App\Http\Requests\Admin\UpdateUserEmploymentContractRequest;

class HumanResourcesController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $Users = User::orderBy('id')->paginate(20);
        $userSelect = $this->SelectDataService->getUsers();
        $SectionsSelect = $this->SelectDataService->getSection();
        $ExpenseReports = UserExpenseReport::where('status', 3)
                                            ->orWhere('status', 5)
                                            ->get();
        $UserExpenseCategories = UserExpenseCategory::All();

        return view('admin/human-resources-index', [
            'Users' => $Users,
            'userSelect' => $userSelect,
            'SectionsSelect' =>  $SectionsSelect,
            'UserExpenseCategories' =>  $UserExpenseCategories,
            'ExpenseReports' =>  $ExpenseReports,
        ]);
    }

    /**
     * Display the specified user.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function ShowUser($id)
    {
        $User = User::findOrFail($id);
        $userSelect = $this->SelectDataService->getUsers();
        $SectionsSelect  = $this->SelectDataService->getSection();
        $UserEmploymentContracts = UserEmploymentContracts::where('user_id', $id)->get();
        $UserExpenseReports = UserExpenseReport::where('user_id', $id)->get();
        $Roles = Role::all();

        return view('admin/users-show', [
            'User' => $User,
            'Roles' => $Roles,
            'userSelect' => $userSelect,
            'SectionsSelect' =>  $SectionsSelect,
            'UserEmploymentContracts' =>  $UserEmploymentContracts,
            'UserExpenseReports' =>  $UserExpenseReports,
        ]);
    }

    /**
     * Update the specified user.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
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
     * Lock a user until a specified date.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function LockUser(Request $request, int $id)
    {
        $UserUpdate = User::findOrFail($id);
        $UserUpdate->banned_until = $request->banned_until;
        $UserUpdate->save();
        return redirect()->route('human.resources.show.user', ['id' => $id])->with('success', 'Successfully lock user');
    }

    /**
     * Store a new user employment contract.
     *
      * @param \App\Http\Requests\Admin\StoreUserEmploymentContractRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeUserEmploymentContract(StoreUserEmploymentContractRequest $request)
    {
        // Create a new user employment contract
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
     * Update the user employment contract.
     *
     * @param \App\Http\Requests\Admin\UpdateUserEmploymentContractRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUserEmploymentContract(UpdateUserEmploymentContractRequest $request)
    {
        // Find the contract by ID and update it
        $userEmploymentContract = UserEmploymentContracts::findOrFail($request->id);
        $userEmploymentContract->update($request->only([
            'statu',
            'methods_section_id',
            'signature_date',
            'type_of_contract',
            'start_date',
            'duration_trial_period',
            'end_date',
            'weekly_duration',
            'position',
            'coefficient',
            'hourly_gross_salary',
            'minimum_monthly_salary',
            'annual_gross_salary',
            'end_of_contract_reason'
        ]));
        return redirect()->route('human.resources.show.user', ['id' => $userEmploymentContract->user_id])->with('success', 'Successfully update contract');
    }
    
    /**
     * Store a new user expense category.
     *
     * @param \App\Http\Requests\Admin\StoreUserExpenseCategorieRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeUserExpenseCategorie(StoreUserExpenseCategorieRequest $request)
    {
        // Create Line
        $UserExpenseCategory = UserExpenseCategory::create([
                                                            'label'=>$request->label,  
                                                            'description'=>$request->description, 
                                                        ]);

        return redirect()->route('human.resources')->with('success', 'Successfully add category');
    }

    /**
     * Update the user expense category.
     *
     * @param \App\Http\Requests\Admin\UpdateUserExpenseCategorieRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUserExpenseCategorie(UpdateUserExpenseCategorieRequest $request)
    {
        // Find the category by ID and update it
        $userExpenseCategory = UserExpenseCategory::findOrFail($request->id);
        $userExpenseCategory->update($request->only(['label', 'description']));

        return redirect()->route('human.resources')->with('success', 'Successfully update category');
    }

    /**
     * Store a new user expense report.
     *
     * @param \App\Http\Requests\Admin\StoreUserExpenseReportRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeUserExpenseReport(StoreUserExpenseReportRequest $request)
    {
        // Create Line
        $UserExpenseReport = UserExpenseReport::create([
                                                            'user_id'=> Auth::id(),
                                                            'date'=>$request->date, 
                                                            'label'=>$request->label,  
                                                        ]);

        return redirect()->route('user.profile', ['id' => Auth::id()])->with('success', 'Successfully add expense report');
    }

    /**
     * Update the user's expense report.
     *
     * @param \App\Http\Requests\Admin\UpdateUserExpenseReportRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUserExpenseReport(UpdateUserExpenseReportRequest $request)
    {
        // updpate Line
        $userExpenseReport = UserExpenseReport::findOrFail($request->id);
        $userExpenseReport->update($request->only([
            'label',
            'status',
            'date'
        ]));

        return redirect()->route('user.profile', ['id' =>Auth::id()])->with('success', 'Successfully update  expense report');
    }

    /**
     * Display the specified user expense report.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function ShowExpenseUser($id)
    {
        $UserExpenseReports = UserExpenseReport::findOrFail($id);
        $UserExpenseCategoriesSelect = UserExpenseCategory::All();
        $userSelect = $this->SelectDataService->getUsers();
        $OrderLineList = Orders::orderby('created_at', 'desc')->get();
        return view('admin/expenses', [
            'userSelect' =>  $userSelect,
            'UserExpenseCategoriesSelect' =>  $UserExpenseCategoriesSelect,
            'UserExpenseReports' =>  $UserExpenseReports,
            'OrderLineList' =>  $OrderLineList,
        ]);
    }

    /**
     * Store a new user expense.
     *
     * @param \App\Http\Requests\Admin\StoreUserExpenseRequest $request
     * @param int $report_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeExpenseUser(StoreUserExpenseRequest $request, int $report_id)
    {
        // Create Line
        $UserExpense = UserExpense::create([
                                                'report_id'=>$report_id, 
                                                'user_id'=> Auth::id(),
                                                'category_id'=>$request->category_id,  
                                                'expense_date'=>$request->expense_date, 
                                                'location'=>$request->location, 
                                                'description'=>$request->description, 
                                                'amount'=>$request->amount, 
                                                'payer_id'=>$request->payer_id, 
                                                'tax'=>$request->tax, 
                                                'order_id'=>$request->order_id, 
                                            ]);

         // Handle file upload if present
        if($request->hasFile('scan_file')){
            $file =  $request->file('scan_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->scan_file->move(public_path('file/Expense'), $filename);
            $UserExpense->update(['scan_file' => $filename]);
            $UserExpense->save();
        }

        return redirect()->route('human.resources.show.expense', ['id' => $report_id])->with('success', 'Successfully add expense report');
    }

    /**
     * Update the user's expense.
     *
     * @param \App\Http\Requests\Admin\UpdateUserExpenseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateExpenseUser(UpdateUserExpenseRequest $request)
    {
        $userExpense = UserExpense::findOrFail($request->id);
        $userExpense->update($request->only([
            'category_id',
            'expense_date',
            'location',
            'description',
            'amount',
            'payer_id',
            'tax',
            'order_id'
        ]));

        return redirect()->route('human.resources.show.expense', ['id' => $userExpense->report_id])->with('success', 'Successfully add expense report');
    }

    /**
     * Validate the user's expense report.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function valideExpenseUser(Request $request)
    {
        // valide Line
        $UserExpenseReport = UserExpenseReport::findOrFail($request->id);
        $UserExpenseReport->status =$request->status;
        $UserExpenseReport->save();

        return redirect()->route('human.resources')->with('success', 'Successfully valide  expense report');
    }
}
