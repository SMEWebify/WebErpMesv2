<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class ImportCsvService
{
    private $numberOfLinesImported = 0;
    private $header = false;
    protected $orderService;

    public function __construct(
        OrderService $orderService, 
                    ){
        $this->orderService = $orderService;
    }
    /**
     * Validate the uploaded file.
     *
     * This function performs several validations on the uploaded file, including:
     * - Checking if a file was uploaded.
     * - Ensuring the file size does not exceed the maximum allowed size (5MB).
     * - Verifying the file extension is one of the allowed types (csv, xlsx).
     *
     * @param \Illuminate\Http\UploadedFile $file The uploaded file to validate.
     * @return bool Returns true if the file passes all validations, false otherwise.
     */
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

    /**
     * Stores the uploaded file in the specified location and returns the public path to the file.
     *
     * @param \Illuminate\Http\UploadedFile $file The uploaded file instance.
     * @return string The public path to the stored file.
     */
    private function storeUploadedFile($file)
    {
        $filename = $file->getClientOriginalName();
        $location = 'imports'; // Adjust if needed

        $file->move($location, $filename);
        return public_path("/" . $location . "/" . $filename);
    }

    /**
     * Reads data from a CSV file and returns it as an array.
     *
     * @param string $filepath The path to the CSV file.
     * @return array The imported data from the CSV file.
     */
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

    /**
     * Validates the imported data for companies.
     *
     * This function checks if the required fields 'code' and 'label' are present in the data.
     * It skips the header row by performing a more robust check.
     *
     * @param array $data The data to be validated.
     * @param \Illuminate\Http\Request|null $request The request object containing the 'code' and 'label' fields.
     * @param int $importedLines The number of lines that have been imported.
     * 
     * @return bool Returns true if the header row is detected or if the required fields are missing.
     */
    private function validateCompaniesImportData($data, $request = null, Int $importedLines)
    {
        // Skip the header row (more robust check)
        if (empty($data[$request->code]) || empty($data[$request->label])) { 
            return true;
        }
    }

    /**
     * Validates the imported quote lines data.
     *
     * This function checks if the required fields in the provided data are not empty.
     * It skips the header row by performing a more robust check on the fields.
     *
     * @param array $data The data to be validated.
     * @param \Illuminate\Http\Request|null $request The request object containing the field names.
     * @param int $importedLines The number of imported lines.
     * @return bool Returns true if the data is valid, otherwise false.
     */
    private function validateQuoteLinesImportData($data, $request = null, Int $importedLines)
    {
        // Skip the header row (more robust check)
        if (empty($data[$request->code]) || empty($data[$request->label]) || empty($data[$request->qty]) || empty($data[$request->selling_price])) { 
            return true;
        }
    }

    /**
     * Get default settings for payment conditions, methods, and deliveries.
     *
     * @return array
     */
    private function getDefaultSettings()
    {
        return [
            'payment_conditions' => AccountingPaymentConditions::getDefault(),
            'payment_methods' => AccountingPaymentMethod::getDefault(),
            'deliveries' => AccountingDelivery::getDefault()
        ];
    }

    /**
     * Check if the current line is a header line.
     *
     * @param array $data
     * @return bool
     */
    private function isHeaderLine($data)
    {
        if ($this->header) {
            $this->header = false;
            return true;
        }
        return false;
    }

    /**
     * Get the company based on the provided data.
     *
     * @param array $data
     * @param \Illuminate\Http\Request $request
     * @param int $importedLines
     * @return \App\Models\Companies\Companies|null
     */
    private function getCompany($data, $request, $importedLines)
    {
        $companyCode = $data[$request->companies_id] ?? null;
        return Companies::where('code', $companyCode)->first();
    }

    /**
     * Get the default address and contact for the company.
     *
     * @param \App\Models\Companies\Companies $company
     */
    private function getDefaultAddressAndContact($company)
    {
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $company->id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $company->id]);

        if (is_null($defaultAddress) || is_null($defaultContact)) {
            redirect()->back()->withErrors('Import failed. Not default for '. $company->code );
            return true;
        }

        return [$defaultAddress, $defaultContact];
    }

    /**
     * Create a quote based on the provided data.
     *
     * @param array $data
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Companies\\Companies $company
     * @param array $defaultSettings
     * @param string $filename
     * @param \App\Models\Companies\\CompaniesAddresses $defaultAddress
     * @param \App\Models\Companies\\CompaniesContacts $defaultContact
     * @return void
     */
    private function createQuote($data, $request, $company, $defaultSettings, $filename, $defaultAddress, $defaultContact)
    {
        Quotes::create([
            'uuid' => Str::uuid(),
            'code' => utf8_encode($data[$request->code]),
            'label' => $data[$request->label] ?? null,
            'customer_reference' => $data[$request->customer_reference] ?? null,
            'companies_id' => $company->id,
            'companies_contacts_id' => $defaultAddress->id,
            'companies_addresses_id' => $defaultContact->id,
            'validity_date' => $data[$request->validity_date] ?? null,
            'user_id' => Auth::id(),
            'accounting_payment_conditions_id' => $defaultSettings['payment_conditions']->id,
            'accounting_payment_methods_id' => $defaultSettings['payment_methods']->id,
            'accounting_deliveries_id' => $defaultSettings['deliveries']->id,
            'comment' => $data[$request->comment] ?? null,
            'csv_file_name' => $filename,
        ]);
    }

    /**
     * Create a quote based on the provided data.
     *
     * @param array $data
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Companies\\Companies $company
     * @param array $defaultSettings
     * @param string $filename
     * @param \App\Models\Companies\\CompaniesAddresses $defaultAddress
     * @param \App\Models\Companies\\CompaniesContacts $defaultContact
     * @return void
     */
    private function createOrder($data, $request, $company, $defaultSettings, $filename, $defaultAddress, $defaultContact)
    {

        // Create order
        $user = Auth::user();
        $this->orderService->createOrder(
            utf8_encode($data[$request->code]),
            $data[$request->label] ?? null,
            $data[$request->customer_reference] ?? null,
            $company->id,
            $defaultAddress->id,
            $defaultContact->id,
            $data[$request->validity_date] ?? null,
            null,
            $user->id,
            $defaultSettings['payment_conditions']->id,
            $defaultSettings['payment_methods']->id,
            $defaultSettings['deliveries']->id,
            $data[$request->comment] ?? null,
            1,
            null,
            $filename
        );
    }

    /**
     * Create a product based on the provided data.
     *
     * @param array $data
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return void
     */
    private function createProduct($data, $request,  $filename)
    {
        Products::create([
            'uuid' => Str::uuid(),  // Génération d'un UUID unique pour chaque produit
            'code' => utf8_encode($data[$request->code]),  // Champ obligatoire
            'label' => $data[$request->label] ?? null,  // Champ obligatoire
            'ind' => $data[$request->ind] ?? null,
            'methods_services_id' => $request->methods_services_id,
            'methods_families_id' => $request->methods_families_id,
            'purchased' => $request->input('purchased', 2),
            'purchased_price' => $data[$request->purchased_price] ?? null,
            'sold' => $request->input('sold', 2),
            'selling_price' => $data[$request->selling_price] ?? null,
            'methods_units_id' => $request->methods_units_id,
            'material' => $data[$request->material] ?? null,
            'thickness' => $data[$request->thickness] ?? null,
            'weight' => $data[$request->weight] ?? null,
            'x_size' => $data[$request->x_size] ?? null,
            'y_size' => $data[$request->y_size] ?? null,
            'z_size' => $data[$request->z_size] ?? null,
            'x_oversize' => $data[$request->x_oversize] ?? null,
            'y_oversize' => $data[$request->y_oversize] ?? null,
            'z_oversize' => $data[$request->z_oversize] ?? null,
            'comment' => $data[$request->comment] ?? null,
            'qty_eco_min' => $data[$request->qty_eco_min] ?? null,
            'qty_eco_max' => $data[$request->qty_eco_max] ?? null,
            'diameter' => $data[$request->diameter] ?? null,
            'diameter_oversize' => $data[$request->diameter_oversize] ?? null,
            'section_size' => $data[$request->section_size] ?? null,
            'finishing' => $data[$request->finishing] ?? null,
            'csv_file_name' => $filename,  // Stockage du nom du fichier CSV importé
        ]);
    }

    /**
     * Get the number of lines imported from the CSV file.
     *
     * This method returns the total number of lines that have been successfully
     * imported from the CSV file during the import process.
     *
     * @return int The number of lines imported.
     */
    public function getNumberOfLinesImported()
    {
        return $this->numberOfLinesImported;
    }

    /**
     * Import companies from a CSV file.
     *
     * This function handles the import of company data from a CSV file uploaded via a request.
     * It validates the uploaded file, reads the data, and creates company records in the database.
     * If any errors are encountered during the import process, they are returned to the user.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing the uploaded file and other parameters.
     * @return \Illuminate\Http\RedirectResponse A redirect response indicating the result of the import process.
     */
    public function importCompanies(Request $request)
    {
        $file = $request->file('import_file');
        // Validate the uploaded file (optional, but recommended)
        if($this->validateFile($file)){
            $user_id = Auth::id();
            $importedLines = 0;
            // Read and process the file
            
            $filename = $file->getClientOriginalName();
            $filepath = $this->storeUploadedFile($file);
            $importData = $this->readImportData($filepath);
            $this->header = $request->header;

            foreach ($importData as $data) {
                $importedLines++;
                // Validate each row (optional)
                if($this->validateCompaniesImportData($data, $request, $importedLines)){
                    $errors[] = '(skpi) Please check the value for required code and label column at line '. $importedLines;
                    continue;
                }

                if ($this->isHeaderLine($data)) {
                    $errors[] = 'Header skip ' . $importedLines;
                    continue;
                }

                // Create the company record
                Companies::create([
                        'uuid'=> Str::uuid(),
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
                        'csv_file_name'=>$filename,
                    ]);
            }

            // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
            if (count($errors) > 0) {
                return redirect()->back()->withErrors($errors);
            }

            return redirect()->route('companies')->with('success', 'Successfully imports companies,'.  $importedLines .' lines added.');
        }
    }

    /**
     * Import quotes from a CSV file.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing the uploaded CSV file.
     * @return \Illuminate\Http\RedirectResponse Redirects back with errors if any issues are encountered, 
     *                                           or redirects to the quotes route with a success message.
     *
     * This function performs the following steps:
     * 1. Validates the uploaded CSV file.
     * 2. Retrieves default settings and checks for any missing settings.
     * 3. Stores the uploaded file and reads its data.
     * 4. Iterates through each row of the CSV data:
     *    - Validates the data for required columns.
     *    - Skips header lines.
     *    - Retrieves the company associated with the data.
     *    - Retrieves the default address and contact for the company.
     *    - Creates a quote using the data, company, default settings, filename, default address, and default contact.
     * 5. Collects any errors encountered during the import process.
     * 6. Returns a redirect response with errors if any were encountered, or a success message if the import was successful.
     */
    public function importQuotes(Request $request)
    {
        $file = $request->file('import_file');

        // Valider le fichier CSV
        if ($this->validateFile($file)) {
            $importedLines = 0;
            $errors = [];
            $defaultSettings = $this->getDefaultSettings();

            foreach ($defaultSettings as $key => $setting) {
                if (is_null($setting)) {
                    return redirect()->back()
                                    ->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
                }
            }

            $filename = $file->getClientOriginalName();
            $filepath = $this->storeUploadedFile($file);
            $importData = $this->readImportData($filepath);
            $this->header = $request->header;
    
            foreach ($importData as $data) {
                $importedLines++;
        
                if($this->validateCompaniesImportData($data, $request, $importedLines)){
                    $errors[] = '(skip) Please check the value for required code and label column at line '. $importedLines;
                    continue;
                }
                
                if ($this->isHeaderLine($data)) {
                    $errors[] = 'Header skip ' . $importedLines;
                    continue;
                }

                $company = $this->getCompany($data, $request, $importedLines);
                if (!$company) {
                    $errors[] = 'Company with code ' . $data[$request->companies_id] . ' not found at line ' . $importedLines;
                    continue;
                }
                 // Appel de getDefaultAddressAndContact
                $defaultAddressAndContact = $this->getDefaultAddressAndContact($company);
                // Appel de getDefaultAddressAndContact
                if ($defaultAddressAndContact === true) {
                    $errors[] = 'No default address or contact for company with code ' . $company->code . ' at line ' . $importedLines;
                    continue;
                }

                // Si tout est correct, continuez avec la déstructuration
                list($defaultAddress, $defaultContact) = $defaultAddressAndContact;

                $this->createQuote($data, $request, $company, $defaultSettings, $filename, $defaultAddress, $defaultContact);
            }
    
            // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
            if (count($errors) > 0) {
                return redirect()->back()->withErrors($errors);
            }

            return redirect()->route('quotes')->with('success', $importedLines . ' lines imported successfully.');
        }
    }

    /**
     * Import orders from a CSV file.
     *
     * This function handles the import of orders from a CSV file uploaded via a request.
     * It validates the file, processes each line of the CSV, and creates orders based on the data.
     * If any errors are encountered during the import process, they are collected and returned to the user.
     *
     * @param Request $request The HTTP request containing the uploaded CSV file and other necessary data.
     * @return \Illuminate\Http\RedirectResponse A redirect response indicating the result of the import process.
     */
    public function importOrders(Request $request)
    {
        $file = $request->file('import_file');

        // Valider le fichier CSV
        if ($this->validateFile($file)) {
            $importedLines = 0;
            $errors = [];
            $defaultSettings = $this->getDefaultSettings();

            foreach ($defaultSettings as $key => $setting) {
                if (is_null($setting)) {
                    return redirect()->back()
                                    ->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
                }
            }

            $filename = $file->getClientOriginalName();
            $filepath = $this->storeUploadedFile($file);
            $importData = $this->readImportData($filepath);
            $this->header = $request->header;
    
            foreach ($importData as $data) {
                $importedLines++;
        
                if($this->validateCompaniesImportData($data, $request, $importedLines)){
                    
                
                    $errors[] = '(skip) Please check the value for required code and label column at line '. $importedLines;
                    continue;
                }
                
                if ($this->isHeaderLine($data)) {
                    $errors[] = 'Header skip ' . $importedLines;
                    continue;
                }

                $company = $this->getCompany($data, $request, $importedLines);
                if (!$company) {
                    $errors[] = 'Company with code ' . $data[$request->companies_id] . ' not found at line ' . $importedLines;
                    continue;
                }
                 // Appel de getDefaultAddressAndContact
                $defaultAddressAndContact = $this->getDefaultAddressAndContact($company);
                // Appel de getDefaultAddressAndContact
                if ($defaultAddressAndContact === true) {
                    $errors[] = 'No default address or contact for company with code ' . $company->code . ' at line ' . $importedLines;
                    continue;
                }

                // Si tout est correct, continuez avec la déstructuration
                list($defaultAddress, $defaultContact) = $defaultAddressAndContact;

                $this->createOrder($data, $request, $company, $defaultSettings, $filename, $defaultAddress, $defaultContact);
            }
    
            // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
            if (count($errors) > 0) {
                return redirect()->back()->withErrors($errors);
            }

            return redirect()->route('orders')->with('success', $importedLines . ' lines imported successfully.');
        }
    }

    /**
     * Import products from a CSV file.
     *
     * This function handles the import of products from a CSV file uploaded via a request.
     * It validates the file, reads the data, and processes each line to create products.
     * If any errors are encountered during the import process, they are returned to the user.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the uploaded CSV file.
     * @return \Illuminate\Http\RedirectResponse A redirect response indicating the result of the import process.
     */
    public function importProducts(Request $request)
    {
        $file = $request->file('import_file');

        // Valider le fichier CSV
        if ($this->validateFile($file)) {
            $importedLines = 0;
            $errors = [];

            $filename = $file->getClientOriginalName();
            $filepath = $this->storeUploadedFile($file);
            $importData = $this->readImportData($filepath);
            $this->header = $request->header;
    
            foreach ($importData as $data) {
                $importedLines++;
        
                if($this->validateCompaniesImportData($data, $request, $importedLines)){
                    $errors[] = '(skip) Please check the value for required code and label column at line '. $importedLines;
                    continue;
                }
                
                if ($this->isHeaderLine($data)) {
                    $errors[] = 'Header skip ' . $importedLines;
                    continue;
                }

                $this->createProduct($data, $request, $filename);
            }
    
            // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
            if (count($errors) > 0) {
                return redirect()->back()->withErrors($errors);
            }

            return redirect()->route('products')->with('success', $importedLines . ' lines imported successfully.');
        }
    }

    /**
     * Imports quote lines from a CSV file.
     *
     * @param int $idQuote The ID of the quote to which the lines will be imported.
     * @param \Illuminate\Http\Request $request The HTTP request containing the uploaded CSV file and header information.
     * @return \Illuminate\Http\RedirectResponse Redirects back with success or error messages.
     *
     * This function performs the following steps:
     * 1. Validates the uploaded file.
     * 2. Retrieves default method unit and accounting VAT.
     * 3. Reads and processes the CSV file.
     * 4. Validates and imports each row from the CSV file.
     * 5. Creates new quote lines and their details.
     * 6. Returns success or error messages based on the import process.
     *
     * @throws \Illuminate\Validation\ValidationException If validation of the file or data fails.
     */
    public function importQuoteLines($idQuote, Request $request)
    {
        $file = $request->file('import_file');
         // Validate the uploaded file (optional, but recommended)
        if($this->validateFile($file)){

            $idDefautUnitMethode = MethodsUnits::where('default',1)->first();
            $idDefautAccountingVat = AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){

                $importedLines = 0;
                // Read and process the file
                $filepath = $this->storeUploadedFile($file);
                $importData = $this->readImportData($filepath);
                $this->header = $request->header;
                foreach ($importData as $data) {
                    $importedLines++;
                    // Validate each row (optional)
                    if($this->validateQuoteLinesImportData($data, $request, $importedLines)){
                        $errors[] = '(skip) Please check the value for required code and label column at line '. $importedLines;
                        continue; 
                    }

                    if ($this->isHeaderLine($data)) {
                        $errors[] = 'Header skip ' . $importedLines;
                        continue;
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

                // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
                if (count($errors) > 0) {
                    return redirect()->back()->withErrors($errors);
                }
            
                return redirect()->back()->with('success', $importedLines . ' lines imported successfully.');
            }
            else{
                return redirect()->back()->withErrors('imports failed, unit or accounting vat default');
            }
        }
    }

    /**
     * Import order lines from a CSV file.
     *
     * @param int $idOrder The ID of the order to which the lines will be imported.
     * @param \Illuminate\Http\Request $request The HTTP request containing the uploaded file and other parameters.
     * @return \Illuminate\Http\RedirectResponse Redirects back with success or error messages.
     *
     * This function performs the following steps:
     * 1. Validates the uploaded file.
     * 2. Retrieves default unit method and accounting VAT.
     * 3. Reads and processes the CSV file.
     * 4. Validates and imports each row of data.
     * 5. Creates order lines and their details.
     * 6. Returns success or error messages based on the import result.
     *
     * @throws \Illuminate\Validation\ValidationException If the file validation fails.
     */
    public function importOrderLines($idOrder, Request $request)
    {
        $file = $request->file('import_file');
         // Validate the uploaded file (optional, but recommended)
        if($this->validateFile($file)){

            $idDefautUnitMethode = MethodsUnits::where('default',1)->first();
            $idDefautAccountingVat = AccountingVat::where('default',1)->first();
            if(!empty($idDefautUnitMethode->id) && !empty($idDefautAccountingVat->id)){

                $importedLines = 0;
                // Read and process the file
                $filepath = $this->storeUploadedFile($file);
                $importData = $this->readImportData($filepath);
                $this->header = $request->header;
                foreach ($importData as $data) {
                    $importedLines++;
                    // Validate each row (optional)
                    if($this->validateQuoteLinesImportData($data, $request, $importedLines)){
                        $errors[] = '(skip) Please check the value for required code and label column at line '. $importedLines;
                        continue; 
                    }

                    if ($this->isHeaderLine($data)) {
                        $errors[] = 'Header skip ' . $importedLines;
                        continue;
                    }

                    // Create the company record
                    $NewOrderLine = OrderLines::create([
                        'orders_id'=>$idOrder,
                        //'ordre'=> array_key_exists($request->ordre,  $importData) ? $importData[$request->ordre] : null,
                        'code'=>utf8_encode($data[$request->code]),
                        'label'=>array_key_exists($request->label,  $importData) ? $data[$request->label] : null,
                        'qty'=>array_key_exists($request->qty,  $importData) ? $data[$request->qty] : null,
                        'delivered_remaining_qty'=>array_key_exists($request->qty,  $importData) ? $data[$request->qty] : null,
                        'invoiced_remaining_qty'=>array_key_exists($request->qty,  $importData) ? $data[$request->qty] : null,
                        'methods_units_id'=>$idDefautUnitMethode->id,
                        'selling_price'=>array_key_exists($request->selling_price,  $importData) ? $data[$request->selling_price] : null,
                        'discount'=>array_key_exists($request->discount,  $importData) ? $data[$request->discount] : null,
                        'accounting_vats_id'=>$idDefautAccountingVat->id,
                        'delivery_date'=>array_key_exists($request->delivery_date,  $importData) ? $data[$request->delivery_date] : null,
                    ]);
                    
                    //add line detail
                    OrderLineDetails::create(['order_lines_id'=>$NewOrderLine->id]);
                }

                // Si des erreurs ont été rencontrées, les retourner à l'utilisateur
                if (count($errors) > 0) {
                    return redirect()->back()->withErrors($errors);
                }
            
                return redirect()->back()->with('success', $importedLines . ' lines imported successfully.');
            }
            else{
                return redirect()->back()->withErrors('imports failed, unit or accounting vat default');
            }
        }
    }
}
