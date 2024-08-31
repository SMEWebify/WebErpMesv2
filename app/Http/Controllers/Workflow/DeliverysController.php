<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DeliveryKPIService;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;

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

}
