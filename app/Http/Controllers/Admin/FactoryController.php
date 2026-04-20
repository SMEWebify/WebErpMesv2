<?php

namespace App\Http\Controllers\Admin;

use App\Models\DocumentCodeTemplate;
use App\Models\Planning\Status;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Admin\Factory;
use App\Models\Admin\CustomField;
use App\Models\Admin\Announcements;
use App\Models\Admin\EstimatedBudgets;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Admin\CustomFieldValue;
use App\Http\Requests\Admin\UpdateFactoryRequest;
use App\Http\Requests\Admin\StoreCustomFieldRequest;
use App\Http\Requests\Admin\StoreAnnouncementRequest;

class FactoryController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $AnnouncementLines = Announcements::get()->All();
        $VATSelect = $this->SelectDataService->getVATSelect();
        $Factory = Factory::first();
        $CustomFields = CustomField::orderBy('related_type')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        $DocumentCodeTemplates = DocumentCodeTemplate::all();
        $pdfThemes = array_keys(config('pdf.themes', []));
        $pdfFallbackTheme = config('pdf.fallback_theme');

        if (!$Factory) {
            $Factory = Factory::create([
                'id' => 1,
                'name' => 'Company name',
                'address' => 'Address',
                'zipcode' => 'Zipcode',
                'mail' => 'your @',
                'web_site' => 'Your web site',
                'pdf_header_font_color' => '#60A7A6',
                'pdf_theme' => $pdfFallbackTheme,
                'pdf_custom_css' => null,
                'add_day_validity_quote' => '0',
                'add_delivery_delay_order' => '0',
            ]);
        }

        return view('admin/factory-index', [
            'AnnouncementLines' => $AnnouncementLines,
            'VATSelect' => $VATSelect,
            'Factory' => $Factory,
            'CustomFields' => $CustomFields,
            'DocumentCodeTemplates' => $DocumentCodeTemplates,
            'pdfThemes' => $pdfThemes,
            'pdfFallbackTheme' => $pdfFallbackTheme,
        ]);
    }

    /**
     * Update the specified factory in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateFactoryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateFactoryRequest $request)
    {
        $Factory = Factory::first();
        $Factory->name = $request->name;
        $Factory->address = $request->address;
        $Factory->city = $request->city; 
        $Factory->zipcode = $request->zipcode;
        $Factory->region = $request->region;
        $Factory->country = $request->country;
        $Factory->phone_number = $request->phone_number; 
        $Factory->mail = $request->mail;
        $Factory->web_site = $request->web_site;
        $Factory->siren = $request->siren; 
        $Factory->nat_regis_num = $request->nat_regis_num;
        $Factory->vat_num = $request->vat_num;
        $Factory->accounting_vats_id = $request->accounting_vats_id;
        $Factory->share_capital = $request->share_capital; 
        $Factory->curency = $request->curency;
        $Factory->pdf_header_font_color = $request->pdf_header_font_color;
        $Factory->pdf_theme = $request->pdf_theme;
        $Factory->pdf_custom_css = $request->pdf_custom_css;
        $Factory->add_day_validity_quote = $request->add_day_validity_quote;
        $Factory->add_delivery_delay_order =  $request->add_delivery_delay_order;
        $Factory->task_barre_code =  $request->task_barre_code;
        $Factory->public_link_cgv =  $request->public_link_cgv;
        $Factory->add_cgv_to_pdf =  $request->add_cgv_to_pdf;
        $Factory->enable_construction_site = $request->boolean('enable_construction_site');
        $Factory->fiscal_year_start_month  = (int) $request->fiscal_year_start_month;
        $Factory->iban = $request->iban;
        $Factory->bic  = $request->bic;

        // Secure file validation https://github.com/SMEWebify/WebErpMesv2/issues/654
        $request->validate([
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'cgv_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Logo (image) management
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension(); // Sécurisé par validation
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/factory'), $filename);
            $Factory->picture = $filename;
            Cache::forget('branding_factory_logo');
        }

        // CGV file (PDF only) with magic number verification
        if ($request->hasFile('cgv_file')) {
            $file = $request->file('cgv_file');

            // 🧪 Vérification du contenu (magic bytes : %PDF)
            $handle = fopen($file->getRealPath(), 'rb');
            $magic = fread($handle, 4);
            fclose($handle);

            if ($magic !== '%PDF') {
                return back()->withErrors(['cgv_file' => 'The uploaded file is not a valid PDF (invalid header).']);
            }

            // 🔐 Stockage sécurisé
            $filename = 'cgv_' . time() . '_' . uniqid() . '.pdf';
            $file->move(public_path('cgv/factory'), $filename);
            $Factory->cgv_file = $filename;
        }

        $Factory->save();

        return redirect()->route('admin.factory')->with('success', __('general_content.factory_info_updated_success_trans_key'));
    }

    /**
     * Store a newly created announcement in storage.
     *
     * @param \App\Http\Requests\Admin\StoreAnnouncementRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAnnouncement(StoreAnnouncementRequest $request)
    {
        // Create Line
        $AnnouncementCreated = Announcements::create([
                                                    'title'=>$request->title,  
                                                    'user_id'=>Auth::id(),    
                                                    'comment'=>$request->comment, 
                                                    ]);

        return redirect()->route('admin.factory')->with('success', __('general_content.announcement_added_success_trans_key'));
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAnnouncement($id)
    {
        // Delete Line
        $AnnouncementDelete= Announcements::where('id', $id)->delete();

        return redirect()->route('admin.factory')->with('success', __('general_content.announcement_deleted_success_trans_key'));
    }


    /**
    * Store a newly created custom field in storage.
    *
     * @param \App\Http\Requests\Admin\StoreCustomFieldRequest $request
     * @return \Illuminate\Http\RedirectResponse
    */
    public function storeCustomField(StoreCustomFieldRequest $request)
    {
        // Create a new custom field
        $options = null;

        if ($request->type === 'select') {
            $options = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('options', '')))
                ->map(fn ($option) => trim($option))
                ->filter(fn ($option) => $option !== '')
                ->values()
                ->all();

            if (empty($options)) {
                $options = null;
            }
        }

        $customField = CustomField::create([
            'name' => $request->name,
            'type' => $request->type,
            'related_type' => $request->related_type,
            'category' => $request->category,
            'options' => $options,
        ]);

        // Redirect to a confirmation page or other action
        return redirect()->route('admin.factory')->with('success', 'Custom field created successfully.');
    }

    /**
    * Store a newly created custom field in storage.
    *
    * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
    */
    public function storeOrUpdateCustomField(Request $request, $id, $type)
    {
        // Validate the form data
        $validatedData = $request->validate([
            'custom_fields' => 'nullable|array', // You can add additional validation rules here
        ]);

        // Loop through the data submitted by the form and create or update custom field values
        $submittedFields = $validatedData['custom_fields'] ?? [];

        foreach ($submittedFields as $fieldId => $fieldValue) {
            // Check if the custom field value already exists in the database
            $customFieldValue = CustomFieldValue::where('custom_field_id', $fieldId)
                                                ->where('entity_id', $id)
                                                ->where('entity_type', $type)
                                                ->first();

            
            if ($customFieldValue) {
                // If the value exists, update its value
                $customFieldValue->update(['value' => $fieldValue]);
            } else {
                // Otherwise, create a new value for this custom field
                CustomFieldValue::create([
                    'custom_field_id' => $fieldId,
                    'entity_id' => $id,
                    'entity_type' =>  $type, 
                    'value' => $fieldValue,
                ]);
            }
        }
        
        switch ($type) {
            case 'quote':
                return redirect()->route('quotes.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            case 'order':
                return redirect()->route('orders.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            case 'delivery':
                return redirect()->route('deliverys.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            case 'invoice':
                return redirect()->route('invoices.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            case 'purchase':
                return redirect()->route('purchases.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            case 'product':
                return redirect()->route('products.show', ['id' => $id])->with('success', 'Successfully updated custom fields');
            default:
                return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
        }
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function estimatedBudgetsSettingView()
    {
        $factory = Factory::first();

        $reactEndpoints = [
            'list'    => route('admin.estimated.budgets.json.list'),
            'store'   => route('admin.estimated.budgets.json.store'),
            'update'  => route('admin.estimated.budgets.json.update', ['id' => '__ID__']),
            'destroy' => route('admin.estimated.budgets.json.destroy', ['id' => '__ID__']),
        ];

        $reactTrans = [
            'title'       => __('general_content.estimated_budget_trans_key'),
            'year'        => __('general_content.year_trans_key'),
            'select_year' => __('general_content.select_year_trans_key'),
            'total'       => __('general_content.total_trans_key'),
            'action'      => __('general_content.action_trans_key'),
            'submit'      => __('general_content.submit_trans_key'),
            'update'      => __('general_content.update_trans_key'),
            'cancel'      => __('general_content.cancel_trans_key'),
            'search'      => __('general_content.search_trans_key'),
            'no_data'     => __('general_content.no_data_trans_key'),
            'months'      => __('general_content.chart_months_trans_key'),
            'currency'    => $factory->curency ?? '€',
            'note'        => 'Used for dashboard chart.',
        ];

        return view('admin/factory-estimated-budgets-settings', compact('reactEndpoints', 'reactTrans'));
    }

    public function estimatedBudgetsJsonList(Request $request)
    {
        $search    = $request->get('search', '');
        $sortField = $request->get('sort', 'year');
        $sortAsc   = $request->boolean('asc', true);

        if (!in_array($sortField, ['year'])) {
            $sortField = 'year';
        }

        $budgets = EstimatedBudgets::where('year', 'like', '%' . $search . '%')
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate(10);

        return response()->json($budgets);
    }

    public function estimatedBudgetsJsonStore(Request $request)
    {
        $data = $request->validate([
            'year'     => 'required|unique:estimated_budgets,year',
            'amount1'  => 'required|numeric',
            'amount2'  => 'required|numeric',
            'amount3'  => 'required|numeric',
            'amount4'  => 'required|numeric',
            'amount5'  => 'required|numeric',
            'amount6'  => 'required|numeric',
            'amount7'  => 'required|numeric',
            'amount8'  => 'required|numeric',
            'amount9'  => 'required|numeric',
            'amount10' => 'required|numeric',
            'amount11' => 'required|numeric',
            'amount12' => 'required|numeric',
        ]);

        $budget = EstimatedBudgets::create($data);

        return response()->json($budget, 201);
    }

    public function estimatedBudgetsJsonUpdate(Request $request, $id)
    {
        $budget = EstimatedBudgets::findOrFail($id);

        $data = $request->validate([
            'year'     => ['required', Rule::unique('estimated_budgets', 'year')->ignore($budget->id)],
            'amount1'  => 'required|numeric',
            'amount2'  => 'required|numeric',
            'amount3'  => 'required|numeric',
            'amount4'  => 'required|numeric',
            'amount5'  => 'required|numeric',
            'amount6'  => 'required|numeric',
            'amount7'  => 'required|numeric',
            'amount8'  => 'required|numeric',
            'amount9'  => 'required|numeric',
            'amount10' => 'required|numeric',
            'amount11' => 'required|numeric',
            'amount12' => 'required|numeric',
        ]);

        $budget->fill($data)->save();

        return response()->json($budget);
    }

    public function estimatedBudgetsJsonDestroy($id)
    {
        EstimatedBudgets::findOrFail($id)->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function kanbanSettingView()
    {
        $props = [
            'endpoints' => [
                'list'    => route('admin.kanban.settings.json.list'),
                'store'   => route('admin.kanban.settings.json.store'),
                'up'      => route('admin.kanban.settings.json.up', ['id' => '__ID__']),
                'down'    => route('admin.kanban.settings.json.down', ['id' => '__ID__']),
                'destroy' => route('admin.kanban.settings.json.destroy', ['id' => '__ID__']),
            ],
        ];
        return view('admin/factory-kanban-settings', compact('props'));
    }

    public function kanbanSettingJsonList()
    {
        $statuses = Status::withCount('tasks')->orderBy('order')->get(['id', 'title', 'order']);
        return response()->json($statuses->map(fn($s) => [
            'id'        => $s->id,
            'title'     => $s->title,
            'order'     => $s->order,
            'has_tasks' => $s->tasks_count > 0,
        ]));
    }

    public function kanbanSettingJsonStore(Request $request)
    {
        $request->validate([
            'title' => 'required|unique:statuses,title',
            'order' => 'required|integer',
        ]);
        $status = Status::create(['title' => $request->title, 'order' => $request->order]);
        return response()->json(['id' => $status->id, 'title' => $status->title, 'order' => $status->order, 'has_tasks' => false], 201);
    }

    public function kanbanSettingJsonUp(Request $request, $id)
    {
        Status::findOrFail($id)->increment('order');
        return response()->json(['ok' => true]);
    }

    public function kanbanSettingJsonDown(Request $request, $id)
    {
        Status::findOrFail($id)->decrement('order');
        return response()->json(['ok' => true]);
    }

    public function kanbanSettingJsonDestroy(Request $request, $id)
    {
        $status = Status::withCount('tasks')->findOrFail($id);
        if ($status->tasks_count > 0) {
            return response()->json(['error' => 'Status has tasks'], 422);
        }
        $status->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function logsView()
    {
        return view('admin/factory-logs-view');
    }
}
