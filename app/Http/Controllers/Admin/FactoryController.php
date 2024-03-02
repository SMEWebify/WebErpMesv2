<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Factory;
use Spatie\Permission\Models\Role;
use App\Models\Admin\Announcements;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\Admin\UpdateFactoryRequest;
use App\Http\Requests\Admin\StoreAnnouncementRequest;

class FactoryController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return View
     */
    public function index()
    {
        $AnnouncementLines = Announcements::get()->All();
        $VATSelect = $this->SelectDataService->getVATSelect();
        $Roles = Role::all();
        $Permissions = Permission::all();
        $Factory = Factory::first();
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
            'Roles' => $Roles,
            'Permissions' => $Permissions,
            'Factory' => $Factory,
        ]);
    }

    /**
     * @param Request $request
     * @return View
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
     * @param Request $request
     * @return View
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
     * @param id $id
     * @return View
     */
    public function deleteAnnouncement($id)
    {
        // Delete Line
        $AnnouncementDelete= Announcements::where('id', $id)->delete();

        return redirect()->route('admin.factory')->with('success', 'Successfully delete announcement');
    }
    
}
