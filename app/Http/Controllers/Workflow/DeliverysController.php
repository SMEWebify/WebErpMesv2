<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Packaging;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DeliveryKPIService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Workflow\StorePackagingRequest;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;
use App\Http\Requests\Workflow\UpdatePackagingRequest;

class DeliverysController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $deliveryKPIService;
    protected $customFieldService;

    public function __construct(SelectDataService $SelectDataService,
                                DeliveryKPIService $deliveryKPIService,
                                CustomFieldService $customFieldService
                    ){
        $this->SelectDataService = $SelectDataService;
        $this->deliveryKPIService = $deliveryKPIService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        $CurentYear = Carbon::now()->format('Y');
        $data['deliverysDataRate'] = $this->deliveryKPIService->getDeliveriesDataRate();
        $data['deliveryMonthlyRecap'] = $this->deliveryKPIService->getDeliveryMonthlyRecap( $CurentYear);

        return view('workflow/deliverys-index')->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function request()
    {    
        return view('workflow/deliverys-request');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Deliverys $id)
    {
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Deliverys(), $id->id);
        
        
        $PruchasesSelect = $this->SelectDataService->getPurchases();
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('delivery', $id->id);
        $allDelivered = $id->DeliveryLines->every(function($line) {
            return $line->invoice_status == 4;
        });

        return view('workflow/deliverys-show', [
            'Delivery' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
            'allDelivered' => $allDelivered,
            'PruchasesSelect' => $PruchasesSelect,
        ]);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateDeliveryRequest $request)
    {
        $Delivery = Deliverys::find($request->id);
        $Delivery->label=$request->label;
        $Delivery->statu=$request->statu;
        $Delivery->purchases_id=$request->purchases_id;
        $Delivery->tracking_number=$request->tracking_number;
        $Delivery->comment=$request->comment;
        $Delivery->save();

        return redirect()->route('deliverys.show', ['id' =>  $Delivery->id])->with('success', 'Successfully updated Delivery');
    }

    public function packagingsStore(StorePackagingRequest $request, Deliverys $id)
    {
        // Création et sauvegarde du packaging
        Packaging::create(
            $request->only([
                'deliverys_id',
                'code',
                'type',
                'status',
                'gross_weight',
                'net_weight',
                'length',
                'width',
                'height',
                'comment',
                'packing_date',
                'loaded_date',
                'load_comment',
            ]) + [
                'user_id' => Auth::id(),
                ] 
        );

        // Redirection avec un message de succès
        return redirect()->route('deliverys.show', ['id' =>  $id->id])->with('success', 'Successfully add packaging');
    }

    public function packagingsUpdate(UpdatePackagingRequest $request, Deliverys $id)
    {
        $packaging = Packaging::findOrFail($request->id);
        $packaging->update([
            'code' => $request->code,
            'type' => $request->type,
            'status' => $request->status,
            'gross_weight' => $request->gross_weight,
            'net_weight' => $request->net_weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'comment' => $request->comment,
            'packing_date' => $request->packing_date,
            'loaded_date' => $request->loaded_date,
        ]);

        return redirect()->route('deliverys.show', ['id' =>  $packaging->deliverys_id])->with('success', 'Successfully update packaging');
    }

}
