<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Companies\UpdateCompanieRequest;

class CompaniesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        
        //Quote data for chart
        $data['ClientCountRate'] = DB::table('companies')->where('statu_customer', '=', 3)->count();
        $data['ProspectCountRate'] = DB::table('companies')->where('statu_customer', '=', 2)->count();
        $data['SupplierCountRate']= DB::table('companies')->where('statu_supplier', '=', 2)->count();
        $data['ClientSupplierCountRate']= DB::table('companies')->where('statu_customer', '>', 1)->where('statu_supplier', 2)->count();
         //5 lastest Companies add 
        $LastComapnies = Companies::orderBy('id', 'desc')->take(5)->get();
        
        return view('companies/companies-index', [
            'LastComapnies' => $LastComapnies
        ])->with('data',$data);;
    }

    /**
     * @param $id
     * @return View
     */
    public function show($id)
    {
        $Companie = Companies::findOrFail($id);
        $userSelect = User::select('id', 'name')->get();
        $previousUrl = route('companies.show', ['id' => $Companie->id-1]);
        $nextUrl = route('companies.show', ['id' => $Companie->id+1]);

        return view('companies/companies-show', [
            'Companie' => $Companie,
            'userSelect' => $userSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    public function import(Request $request)
    {   
        $this->user_id = Auth::id();
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
            $maxKey = max($request->code, $request->discount, 1, 6, 7);

            foreach ($importData_arr as $importData) {
                if($maxKey>=count($importData)){
                    //no column match
                    return redirect()->route('admin.factory')->withErrors('imports failed, no column match');
                }
                
            
                try {
                    $CompaniesCreated = Companies::create([
                        'code'=>utf8_encode($importData[$request->code]),
                        'label'=> array_key_exists($request->label,  $importData) ? $importData[$request->label] : null,
                        'website'=> array_key_exists($request->website,  $importData) ? $importData[$request->website] : null,
                        'fbsite'=> array_key_exists($request->fbsite,  $importData) ? $importData[$request->fbsite] : null,
                        'twittersite'=> array_key_exists($request->twittersite,  $importData) ? $importData[$request->twittersite] : null,
                        'lkdsite'=> array_key_exists($request->lkdsite,  $importData) ? $importData[$request->lkdsite] : null,
                        'siren'=> array_key_exists($request->siren,  $importData) ? $importData[$request->siren] : null,
                        'naf_code'=> array_key_exists($request->naf_code,  $importData) ? $importData[$request->naf_code] : null,
                        'intra_community_vat'=> array_key_exists($request->intra_community_vat,  $importData) ? $importData[$request->intra_community_vat] : null,
                        'discount'=> array_key_exists($request->discount,  $importData) ? $importData[$request->discount] : null,
                        'user_id'=>$this->user_id,
                    ]);

                    $j++;
                } catch (\Exception $e) {
                    dd($e);
                }
            }
            
            return redirect()->route('companies')->with('success', 'Successfully imports companies,'. $j .' lines added.');

        } else {
            //no file was uploaded
            return redirect()->route('admin.factory')->withErrors('imports failed, no file');
        }
    }

    /**
     * @param $id
     * @return View
     */
    public function update(UpdateCompanieRequest $request)
    {
        $Companie = Companies::findOrFail($request->id);
        $Companie->label  =$request->label;
        $Companie->website =$request->website;
        $Companie->fbsite  =$request->fbsite;
        $Companie->twittersite  =$request->twittersite; 
        $Companie->lkdsite = $request->lkdsite; 
        $Companie->siren = $request->siren; 
        $Companie->naf_code = $request->naf_code; 
        $Companie->intra_community_vat =$request->intra_community_vat; 
        $Companie->statu_customer = $request->statu_customer;
        $Companie->discount =$request->discount;
        $Companie->user_id =$request->user_id;
        $Companie->account_general_customer =$request->account_general_customer;
        $Companie->account_auxiliary_customer =$request->account_auxiliary_customer;
        $Companie->statu_supplier =$request->statu_supplier;
        $Companie->account_general_supplier =$request->account_general_supplier;
        $Companie->account_auxiliary_supplier =$request->account_auxiliary_supplier;
        $Companie->recept_controle =$request->recept_controle;
        $Companie->comment =$request->comment;
        $Companie->save();
        return redirect()->route('companies.show', ['id' =>  $Companie->id])->with('success', 'Successfully updated companie');
    }
}
