<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\Admin\CustomField;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;

class DeliverysController extends Controller
{
    use NextPreviousTrait;
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        $CurentYear = Carbon::now()->format('Y');
        //Delivery data for chart
        $data['deliverysDataRate'] = DB::table('deliverys')
                                    ->select('statu', DB::raw('count(*) as DeliveryCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Delivery data for chart
        $data['deliveryMonthlyRecap'] = DB::table('delivery_lines')
                                    ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                                    ->selectRaw('
                                        MONTH(delivery_lines.created_at) AS month,
                                        SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100)) AS orderSum
                                    ')
                                    ->whereYear('delivery_lines.created_at', $CurentYear)
                                    ->groupByRaw('MONTH(delivery_lines.created_at) ')
                                    ->get();

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
        $CustomFields = CustomField::where('custom_fields.related_type', '=', 'delivery')
                                    ->leftJoin('custom_field_values  as cfv', function($join) use ($id) {
                                        $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                                                ->where('cfv.entity_type', '=', 'delivery')
                                                ->where('cfv.entity_id', '=', $id->id);
                                    })
                                    ->select('custom_fields.*', 'cfv.value as field_value')
                                    ->get();
        $allDelivered = $id->DeliveryLines->every(function($line) {
            return $line->invoice_status == 4;
        });

        return view('workflow/deliverys-show', [
            'Delivery' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
            'allDelivered' => $allDelivered,
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
        $Delivery->comment=$request->comment;
        $Delivery->save();

        return redirect()->route('deliverys.show', ['id' =>  $Delivery->id])->with('success', 'Successfully updated Delivery');
    }

}
