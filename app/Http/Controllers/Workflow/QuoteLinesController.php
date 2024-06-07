<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest;

class QuoteLinesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        return view('workflow/quotes-lines-index');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update($idQuote, UpdateQuoteLineDetailsRequest $request)
    {
        
        $QuoteLineDetails = QuoteLineDetails::find($request->id);
        $QuoteLineDetails->x_size=$request->x_size;
        $QuoteLineDetails->y_size=$request->y_size;
        $QuoteLineDetails->z_size=$request->z_size;
        $QuoteLineDetails->x_oversize=$request->x_oversize;
        $QuoteLineDetails->y_oversize=$request->y_oversize;
        $QuoteLineDetails->z_oversize=$request->z_oversize;
        $QuoteLineDetails->diameter=$request->diameter;
        $QuoteLineDetails->diameter_oversize=$request->diameter_oversize;
        $QuoteLineDetails->material=$request->material;
        $QuoteLineDetails->thickness=$request->thickness;
        $QuoteLineDetails->finishing=$request->finishing;
        $QuoteLineDetails->weight=$request->weight;
        $QuoteLineDetails->material_loss_rate=$request->material_loss_rate;
        $QuoteLineDetails->internal_comment=$request->internal_comment;
        $QuoteLineDetails->external_comment=$request->external_comment;
        $QuoteLineDetails->save();
        return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated quote detail line');
    }
    
    /**
     * @param Request $request
     * @return View
     */
    public function StoreImage($idQuote,Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/quote-lines'), $filename);
            $QuoteLineDetails->update(['picture' => $filename]);
            $QuoteLineDetails->save();
            return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    public function import($idQuote, Request $request)
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
            $idDefautAccountingVat =AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){
                foreach ($importData_arr as $importData) {
                    if($maxKey>=count($importData)){
                        //no column match
                        return redirect()->route('quotes.show', ['id' =>  $idQuote])->withErrors('imports failed, no column match');
                    }
                    
                
                    try {
                        $NewQuoteLine = Quotelines::create([
                            'quotes_id'=>$idQuote,
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
                        QuoteLineDetails::create(['quote_lines_id'=>$NewQuoteLine->id]);

                        $j++;
                    } catch (\Exception $e) {
                        dd($e);
                    }
                }
                
                return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully imports,'. $j .' lines added.');
            }
            else {
                //no value default for MethodsUnits & AccountingVat
                return redirect()->route('quotes.show', ['id' =>  $idQuote])->withErrors('imports failed, unit or accounting vat default');
            }
        } else {
            //no file was uploaded
            return redirect()->route('quotes.show', ['id' =>  $idQuote])->withErrors('imports failed, no file');
        }
    }
}