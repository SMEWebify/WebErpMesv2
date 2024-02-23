<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;

class DeliverysController extends Controller
{
    /**
     * @return View
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
     * @return View
     */
    public function request()
    {    
        return view('workflow/deliverys-request');
    }

    /**
     * @param $id
     * @return View
     */
    public function show(Deliverys $id)
    {
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $previousUrl = route('deliverys.show', ['id' => $id->id-1]);
        $nextUrl = route('deliverys.show', ['id' => $id->id+1]);
    
        return view('workflow/deliverys-show', [
            'Delivery' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }
    
    /**
     * @param Request $request
     * @return View
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
