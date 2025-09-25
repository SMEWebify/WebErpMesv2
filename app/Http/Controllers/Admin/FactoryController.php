<?php

namespace App\Http\Controllers\Admin;

use App\Models\DocumentCodeTemplate;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Admin\CustomField;
use App\Models\Admin\Announcements;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

        return redirect()->route('admin.factory')->with('success', 'Successfully updated factory inforamations');
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

        return redirect()->route('admin.factory')->with('success', 'Successfully add announcement');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAnnouncement($id)
    {
        // Delete Line
        $AnnouncementDelete= Announcements::where('id', $id)->delete();

        return redirect()->route('admin.factory')->with('success', 'Successfully delete announcement');
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
        return view('admin/factory-estimated-budgets-settings');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function kanbanSettingView()
    {
        return view('admin/factory-kanban-settings');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function logsView()
    {
        return view('admin/factory-logs-view');
    }
}
