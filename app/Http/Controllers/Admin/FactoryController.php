<?php

namespace App\Http\Controllers\Admin;

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
        $CustomFields = CustomField::all();

        if (!$Factory) {
            $Factory = Factory::create([
                'id' => 1,
                'name' => 'Company name',
                'address' => 'Address',
                'zipcode' => 'Zipcode',
                'mail' => 'your @',
                'web_site' => 'Your web site',
                'pdf_header_font_color' => '#60A7A6',
                'add_day_validity_quote' => '0',
                'add_delivery_delay_order' => '0',
            ]);
        }

        return view('admin/factory-index', [
            'AnnouncementLines' => $AnnouncementLines,
            'VATSelect' => $VATSelect,
            'Factory' => $Factory,
            'CustomFields' => $CustomFields,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
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
        $Factory->add_day_validity_quote = $request->add_day_validity_quote;
        $Factory->add_delivery_delay_order =  $request->add_delivery_delay_order;
        $Factory->task_barre_code =  $request->task_barre_code;
        $Factory->public_link_cgv =  $request->public_link_cgv;
        $Factory->add_cgv_to_pdf =  $request->add_cgv_to_pdf;

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/factory'), $filename);
            $Factory->picture =  $filename;
        }

        $request->validate([
            'file' => "mimes:pdf|max:10240"
        ]);
        
        if($request->hasFile('cgv_file')){
            $file =  $request->file('cgv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->cgv_file->move(public_path('cgv/factory'), $filename);
            $Factory->cgv_file =  $filename;
        }

        $Factory->save();

        return redirect()->route('admin.factory')->with('success', 'Successfully updated factory inforamations');
    }

    /**
     * @param \Illuminate\Http\Request $request
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
    * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
    */
    public function storeCustomField(StoreCustomFieldRequest $request)
    {
        // Création d'un nouveau champ personnalisé
        $customField = CustomField::create([
            'name' => $request->name,
            'type' => $request->type,
            'related_type' => $request->related_type,
        ]);

        // Redirection vers une page de confirmation ou une autre action
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
        // Validez les données du formulaire
        $validatedData = $request->validate([
            'custom_fields' => 'array', // Vous pouvez ajouter des règles de validation supplémentaires ici
        ]);

        // Parcourez les données soumises par le formulaire et créez ou mettez à jour les valeurs des champs personnalisés
        foreach ($validatedData['custom_fields'] as $fieldId => $fieldValue) {
            // Vérifiez si la valeur du champ personnalisé existe déjà en base de données
            $customFieldValue = CustomFieldValue::where('custom_field_id', $fieldId)
                                                ->where('entity_id', $id)
                                                ->where('entity_type', $type)
                                                ->first();

            
            if ($customFieldValue) {
                // Si la valeur existe, mettez à jour sa valeur
                $customFieldValue->update(['value' => $fieldValue]);
            } else {
                // Sinon, créez une nouvelle valeur pour ce champ personnalisé
                CustomFieldValue::create([
                    'custom_field_id' => $fieldId,
                    'entity_id' => $id,
                    'entity_type' =>  $type, // Vous pouvez adapter cela en fonction de votre logique métier
                    'value' => $fieldValue,
                ]);
            }
        }

        if( $type=='quote'){
            return redirect()->route('quotes.show', ['id' =>   $id])->with('success', 'Successfully updated custom fields');
        }
        elseif( $type=='order'){
            return redirect()->route('orders.show', ['id' =>   $id])->with('success', 'Successfully updated custom fields');
        }
        elseif( $type=='delivery'){
            return redirect()->route('deliverys.show', ['id' =>   $id])->with('success', 'Successfully updated custom fields');
        }
        elseif( $type=='invoice'){
            return redirect()->route('invoices.show', ['id' =>   $id])->with('success', 'Successfully updated custom fields');
        }
        elseif( $type=='purchase'){
            return redirect()->route('purchases.show', ['id' =>   $id])->with('success', 'Successfully updated custom fields');
        }
        else{
            return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
        }
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function logsView()
    {
        return view('admin/factory-logs-view');
    }
}
