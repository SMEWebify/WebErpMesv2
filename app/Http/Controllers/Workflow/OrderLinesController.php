<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest;
use App\Models\Workflow\OrderLines;

class OrderLinesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        return view('workflow/orders-lines-index');
    }

        /**
     * @param Request $request
     * @return View
     */
    public function update($idOrder, UpdateOrderLineDetailsRequest $request)
    {
        
        $OrderLineDetails = OrderLineDetails::find($request->id);
        $OrderLineDetails->x_size=$request->x_size;
        $OrderLineDetails->y_size=$request->y_size;
        $OrderLineDetails->z_size=$request->z_size;
        $OrderLineDetails->x_oversize=$request->x_oversize;
        $OrderLineDetails->y_oversize=$request->y_oversize;
        $OrderLineDetails->z_oversize=$request->z_oversize;
        $OrderLineDetails->diameter=$request->diameter;
        $OrderLineDetails->diameter_oversize=$request->diameter_oversize;
        $OrderLineDetails->material=$request->material;
        $OrderLineDetails->thickness=$request->thickness;
        $OrderLineDetails->weight=$request->weight;
        $OrderLineDetails->material_loss_rate=$request->material_loss_rate;
        $OrderLineDetails->save();
        return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully updated order detail line');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function StoreImage($idOrder,Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/order-lines'), $filename);
            $OrderLineDetails->update(['picture' => $filename]);
            $OrderLineDetails->save();
            return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    public function import($idOrder, Request $request)
    {   
        $file = $request->file('import_file');
        if ($file) {
            $filename = $file->getClientOriginalName(); //Get file name
            $extension = $file->getClientOriginalExtension(); //Get extension of uploaded file
            $tempPath = $file->getRealPath(); //Get Path
            $fileSize = $file->getSize(); //Get size of uploaded file in bytes
            //Where uploaded file will be stored on the server 
            $location = 'imports'; //Created an "uploads" folder for that
            // Upload file
            $file->move($location, $filename);
            // In case the uploaded file path is to be stored in the database 
            $filepath = public_path("/" . $location . "/" . $filename);
            // Reading file
            $file = fopen($filepath, "r");
            $importData_arr = array(); // Read through the file and store the contents as an array
            $i = 0;
            //Read the contents of the uploaded file 
            while (($filedata = fgetcsv($file, 1000, ";")) !== FALSE) {
                $num = count($filedata);
                // Skip first row (Remove below comment if you want to skip the first row)
                if ($i == 0 && $request->header  ) {
                    $i++;
                    continue;
                }
                for ($c = 0; $c < $num; $c++) {
                    //dd(trim(strip_tags($filedata[$c])));
                    $importData_arr[$i][] = trim(strip_tags($filedata[$c]));
                }
                $i++;
            }
            
            fclose($file); //Close after reading
            $j = 0;
            $maxKey = 5;

            $idDefautUnitMethode = MethodsUnits::where('default',1)->first();
            $idDefautAccountingVat = AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){

                foreach ($importData_arr as $importData) {
                    if($maxKey>=count($importData)){
                        //no column match
                        return redirect()->route('orders.show', ['id' =>  $idOrder])->withErrors('imports failed, no column match');
                    }
                    
                
                    try {
                        $NewOrderLine = OrderLines::create([
                            'orders_id'=>$idOrder,
                            //'ordre'=> array_key_exists($request->ordre,  $importData) ? $importData[$request->ordre] : null,
                            'code'=>utf8_encode($importData[$request->code]),
                            'label'=>array_key_exists($request->label,  $importData) ? $importData[$request->label] : null,
                            'qty'=>array_key_exists($request->qty,  $importData) ? $importData[$request->qty] : null,
                            'methods_units_id'=>$idDefautUnitMethode->id,
                            'selling_price'=>array_key_exists($request->selling_price,  $importData) ? $importData[$request->selling_price] : null,
                            'discount'=>array_key_exists($request->discount,  $importData) ? $importData[$request->discount] : null,
                            'accounting_vats_id'=>$idDefautAccountingVat->id,
                            'delivery_date'=>array_key_exists($request->delivery_date,  $importData) ? $importData[$request->delivery_date] : null,
                        ]);
                        
                        //add line detail
                        OrderLineDetails::create(['order_lines_id'=>$NewOrderLine->id]);

                        $j++;
                    } catch (\Exception $e) {
                        dd($e);
                    }
                }
                
                return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully imports ,'. $j .' lines added.');
            }
            else {
                //no value default for MethodsUnits & AccountingVat
                return redirect()->route('orders.show', ['id' =>  $idOrder])->withErrors('imports failed, unit or accounting vat default');
            }
        } 
        else {
            //no file was uploaded
            return redirect()->route('orders.show', ['id' =>  $idOrder])->withErrors('imports failed, no file');
        }
    }
}