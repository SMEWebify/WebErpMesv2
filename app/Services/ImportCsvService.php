<?php
namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use Illuminate\Support\Facades\Log;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\QuoteLineDetails;

class ImportCsvService
{
    private $numberOfLinesImported = 0;
    private $header = false;

    private function validateFile($file)
    {
        // Implement file size, type, and extension validations
        if (!$file) {
            redirect()->back()->withErrors('No file uploaded');
            return false;
        }

        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file->getSize() > $maxFileSize) {
            redirect()->back()->withErrors('File exceeds maximum size: ' . $maxFileSize . ' bytes.');
            return false;
        }

        $allowedExtensions = ['csv', 'xlsx'];
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        if (!in_array($extension, $allowedExtensions)) {
            redirect()->back()->withErrors('Invalid file extension. Allowed extensions: ' . implode(', ', $allowedExtensions));
            return false;
        }
        
        return true;
    }

    private function storeUploadedFile($file)
    {
        $filename = $file->getClientOriginalName();
        $location = 'imports'; // Adjust if needed

        $file->move($location, $filename);
        return public_path("/" . $location . "/" . $filename);
    }

    private function readImportData($filepath)
    {
        $importData = [];
        $file = fopen($filepath, "r");

        while (($filedata = fgetcsv($file, 1000, ";")) !== FALSE) {
            $importData[] = $filedata;
        }

        fclose($file);
        return $importData;
    }

    private function validateCompaniesImportData($data, $request = null, Int $importedLines)
    {
        // Skip the header row (more robust check)
        if (empty($data[$request->code]) || empty($data[$request->label])) { 
            
            redirect()->back()->withErrors('Import failed. Please check the value for required code and label column at line '. $importedLines);
            return true;
        }
    }

    private function validateQuoteLinesImportData($data, $request = null, Int $importedLines)
    {
        // Skip the header row (more robust check)
        if (empty($data[$request->code]) || empty($data[$request->label]) || empty($data[$request->qty]) || empty($data[$request->selling_price])) { 
            
            redirect()->back()->withErrors('Import failed. Please check the value for required column at line '. $importedLines);
            return true;
        }
    }

    public function getNumberOfLinesImported()
    {
        return $this->numberOfLinesImported;
    }

    public function importCompanies(Request $request)
    {
        $file = $request->file('import_file');
        // Validate the uploaded file (optional, but recommended)
        if($this->validateFile($file)){
            $user_id = Auth::id();
            $importedLines = 0;
            try {
                // Read and process the file
                $filepath = $this->storeUploadedFile($file);
                $importData = $this->readImportData($filepath);
                $this->header = $request->header;

                foreach ($importData as $data) {
                    $importedLines++;
                    // Validate each row (optional)
                    if($this->validateCompaniesImportData($data, $request, $importedLines)){
                        continue; 
                    }

                    if ($this->header) {
                        $this->header = false;
                        continue;  // Skip validation for the header row
                    }

                    // Create the company record
                    Companies::create([
                            'code'=>utf8_encode($data[$request->code]),
                            'client_type'=> 1,
                            'label'=> array_key_exists($request->label,  $importData) ? $data[$request->label] : null,
                            'website'=> array_key_exists($request->website,  $importData) ? $data[$request->website] : null,
                            'fbsite'=> array_key_exists($request->fbsite,  $importData) ? $data[$request->fbsite] : null,
                            'twittersite'=> array_key_exists($request->twittersite,  $data) ? $data[$request->twittersite] : null,
                            'lkdsite'=> array_key_exists($request->lkdsite,  $importData) ? $data[$request->lkdsite] : null,
                            'siren'=> array_key_exists($request->siren,  $importData) ? $data[$request->siren] : null,
                            'naf_code'=> array_key_exists($request->naf_code,  $importData) ? $data[$request->naf_code] : null,
                            'intra_community_vat'=> array_key_exists($request->intra_community_vat,  $importData) ? $data[$request->intra_community_vat] : null,
                            'discount'=> array_key_exists($request->discount,  $importData) ? $data[$request->discount] : null,
                            'user_id'=>$user_id,
                        ]);
                    }
        
            } catch (Exception $e) {
                // Handle import failure
                Log::error('Import companies failed: ' . $e->getMessage());
                return redirect()->back()->withErrors('Import failed. Please check the file format or data (line ('. $importedLines .').');
            } finally {
                // Clean up resources (optional)
                if (isset($filepath)) {
                    unlink($filepath); // Delete the temporary file
                }

            }

            return redirect()->route('companies')->with('success', 'Successfully imports companies,'.  $importedLines .' lines added.');
        }
    }

    public function importQuoteLines($idQuote, Request $request)
    {
        $file = $request->file('import_file');
         // Validate the uploaded file (optional, but recommended)
         if($this->validateFile($file)){

            $idDefautUnitMethode = MethodsUnits::where('default',1)->first();
            $idDefautAccountingVat = AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){

                $importedLines = 0;
                try {
                    // Read and process the file
                    $filepath = $this->storeUploadedFile($file);
                    $importData = $this->readImportData($filepath);
                    $this->header = $request->header;
                    foreach ($importData as $data) {
                        $importedLines++;
                        // Validate each row (optional)
                        if($this->validateQuoteLinesImportData($data, $request, $importedLines)){
                            continue; 
                        }

                        if ($this->header) {
                            $this->header = false;
                            continue;  // Skip validation for the header row
                        }

                        // Create the company record
                        $NewQuoteLine = Quotelines::create([
                            'quotes_id'=>$idQuote,
                            //'ordre'=> array_key_exists($request->ordre,  $importData) ? $importData[$request->ordre] : null,
                            'code'=>utf8_encode($data[$request->code]),
                            'label'=>array_key_exists($request->label,  $importData) ? $data[$request->label] : null,
                            'qty'=>array_key_exists($request->qty,  $importData) ? $data[$request->qty] : null,
                            'methods_units_id'=>$idDefautUnitMethode->id,
                            'selling_price'=>array_key_exists($request->selling_price,  $importData) ? $data[$request->selling_price] : null,
                            'discount'=>array_key_exists($request->discount,  $importData) ? $data[$request->discount] : null,
                            'accounting_vats_id'=>$idDefautAccountingVat->id,
                            'delivery_date'=>array_key_exists($request->delivery_date,  $importData) ? $data[$request->delivery_date] : null,
                        ]);
                        
                        //add line detail
                        QuoteLineDetails::create(['quote_lines_id'=>$NewQuoteLine->id]);
                    }
            
                } catch (Exception $e) {
                    // Handle import failure
                    Log::error('Import line failed: ' . $e->getMessage());
                    return redirect()->back()->withErrors('Import failed. Please check the file format or data (line ('. $importedLines .').');
                } finally {
                    // Clean up resources (optional)
                    if (isset($filepath)) {
                        unlink($filepath); // Delete the temporary file
                    }
                }
            }
            else{
                return redirect()->back()->withErrors('imports failed, unit or accounting vat default');
            }
        }
    }

    public function importOrderLines($idOrder, Request $request)
    {
        $file = $request->file('import_file');
         // Validate the uploaded file (optional, but recommended)
         if($this->validateFile($file)){

            $idDefautUnitMethode = MethodsUnits::where('default',1)->first();
            $idDefautAccountingVat = AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){

                $importedLines = 0;
                try {
                    // Read and process the file
                    $filepath = $this->storeUploadedFile($file);
                    $importData = $this->readImportData($filepath);
                    $this->header = $request->header;
                    foreach ($importData as $data) {
                        $importedLines++;
                        // Validate each row (optional)
                        if($this->validateQuoteLinesImportData($data, $request, $importedLines)){
                            continue; 
                        }

                        if ($this->header) {
                            $this->header = false;
                            continue;  // Skip validation for the header row
                        }

                        // Create the company record
                        $NewOrderLine = OrderLines::create([
                            'orders_id'=>$idOrder,
                            //'ordre'=> array_key_exists($request->ordre,  $importData) ? $importData[$request->ordre] : null,
                            'code'=>utf8_encode($data[$request->code]),
                            'label'=>array_key_exists($request->label,  $importData) ? $data[$request->label] : null,
                            'qty'=>array_key_exists($request->qty,  $importData) ? $data[$request->qty] : null,
                            'methods_units_id'=>$idDefautUnitMethode->id,
                            'selling_price'=>array_key_exists($request->selling_price,  $importData) ? $data[$request->selling_price] : null,
                            'discount'=>array_key_exists($request->discount,  $importData) ? $data[$request->discount] : null,
                            'accounting_vats_id'=>$idDefautAccountingVat->id,
                            'delivery_date'=>array_key_exists($request->delivery_date,  $importData) ? $data[$request->delivery_date] : null,
                        ]);
                        
                        //add line detail
                        OrderLineDetails::create(['order_lines_id'=>$NewOrderLine->id]);
                    }
            
                } catch (Exception $e) {
                    // Handle import failure
                    Log::error('Import line failed: ' . $e->getMessage());
                    return redirect()->back()->withErrors('Import failed. Please check the file format or data (line ('. $importedLines .').');
                } finally {
                    // Clean up resources (optional)
                    if (isset($filepath)) {
                        unlink($filepath); // Delete the temporary file
                    }
                }
            }
            else{
                return redirect()->back()->withErrors('imports failed, unit or accounting vat default');
            }
        }
    }
}
