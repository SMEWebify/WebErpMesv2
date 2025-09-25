<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Models\Methods\MethodsUnits;
use App\Models\Quality\QualityCause;
use App\Models\Methods\MethodsSection;
use App\Models\Quality\QualityFailure;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsRessources;
use App\Models\Quality\QualityCorrection;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Quality\QualityNonConformity;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class SelectDataService
{
    /**
     * Retrieve a list of users with their id and name.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsers()
    {
        return User::select('id', 'name')->get();
    }

    
    /**
     * Retrieve a list of active companies with specific customer statuses,
     * optionally filtered by provided company IDs.
     *
     * @param  \Illuminate\Support\Collection|array|null  $companyIdsInOrderLines
     * @return \Illuminate\Support\Collection
     */
    public function getCompanies($companyIdsInOrderLines = null)
    {
        $query = Companies::select('id', 'code', 'client_type', 'civility', 'label', 'last_name')
                        ->where('active', 1)
                        ->whereIn('statu_customer', [2, 3]);

        if (!empty($companyIdsInOrderLines)) {
            $query->whereIn('id', $companyIdsInOrderLines);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Retrieve a list of suppliers from the Companies table.
     *
     * This function selects specific columns from the Companies table,
     * orders the results by the 'label' column, and filters the results
     * to include only those where the 'statu_supplier' column has a value of 2.
     *
     * @param  \Illuminate\Support\Collection|array|null  $companyIdsInPurchaseLines
     * @return \Illuminate\Support\Collection
     */
    public function getSupplier($companyIdsInPurchaseLines = null)
    {
        $query = Companies::select('id', 'code','client_type','civility','label','last_name')
                        ->where('statu_supplier', 2);

        if (!empty($companyIdsInPurchaseLines)) {
            $query->whereIn('id', $companyIdsInPurchaseLines);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Retrieve addresses for a given company or all addresses if no company ID is provided.
     *
     * @param int|null $companiesId The ID of the company to filter addresses by. If null, retrieves all addresses.
     * @return \Illuminate\Database\Eloquent\Collection The collection of addresses.
     */
    public function getAddress($companiesId = null)
    {
        $query = CompaniesAddresses::select('id', 'label', 'adress');
        
        if ($companiesId) {
            $query->where('companies_id', $companiesId);
        }

        return $query->get();
    }

    /**
     * Retrieve contact information for a given company.
     *
     * This function fetches the contact details including 'id', 'first_name', and 'name'
     * from the CompaniesContacts model. If a company ID is provided, it filters the 
     * contacts to only include those associated with the specified company.
     *
     * @param int|null $companiesId The ID of the company to filter contacts by. If null, all contacts are retrieved.
     * @return \Illuminate\Database\Eloquent\Collection The collection of contact records.
     */
    public function getContact($companiesId = null)
    {
        $query = CompaniesContacts::select('id', 'first_name', 'name');
        
        if ($companiesId) {
            $query->where('companies_id', $companiesId);
        }
    
        return $query->get();
    }

    /**
     * Retrieve a list of accounting payment conditions.
     *
     * This function fetches all accounting payment conditions from the database,
     * selecting the 'id', 'code', and 'label' fields.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of accounting payment conditions.
     */
    public function getAccountingPaymentConditions()
    {
        return AccountingPaymentConditions::select('id', 'code','label')->get();
    }

    /**
     * Retrieve a list of accounting payment methods.
     *
     * This function fetches all accounting payment methods from the database
     * and returns them with their id, code, and label.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountingPaymentMethod()
    {
        return AccountingPaymentMethod::select('id', 'code','label')->get();
    }

    /**
     * Retrieve a list of accounting deliveries.
     *
     * This method fetches all accounting deliveries from the database
     * and returns their id, code, and label.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountingDelivery()
    {
        return AccountingDelivery::select('id', 'code','label')->get();
    }

    /**
     * Retrieve a list of VAT options.
     *
     * This method fetches VAT records from the AccountingVat model,
     * selecting only the 'id' and 'label' fields, and orders them by the 'rate' field.
     *
     * @return \Illuminate\Support\Collection A collection of VAT records.
     */
    public function getVATSelect()
    {
        return AccountingVat::select('id', 'label')->orderBy('rate')->get();
    }

    /**
     * Retrieve a list of products with selected fields.
     *
     * This method fetches products from the database, selecting the 'id', 'label', 'code',
     * and 'methods_services_id' fields. The results are ordered by the 'code' field.
     *
     * @return \Illuminate\Support\Collection A collection of products.
     */
    public function getProductsSelect()
    {
        return Products::select('id', 'label', 'code', 'methods_services_id')->orderBy('code')->get();
    }

    /**
     * Retrieve a list of units for selection.
     *
     * This method fetches all units from the MethodsUnits model, selecting the 'id', 'label', and 'code' fields.
     * The results are ordered by the 'label' field in ascending order.
     *
     * @return \Illuminate\Support\Collection A collection of units with 'id', 'label', and 'code' fields.
     */
    public function getUnitsSelect()
    {
        return MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
    }

    /**
     * Retrieve a list of services.
     *
     * This method fetches all services from the MethodsServices model,
     * selecting only the 'id' and 'label' columns, and orders them by 'ordre'.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of services.
     */
    public function getServices()
    {
        return MethodsServices::select('id', 'label')->orderBy('ordre')->get();
    }

    /**
     * Retrieve workshop locations list.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMethodsLocations()
    {
        return MethodsLocation::select('id', 'label')->orderBy('label')->get();
    }

    /**
     * Retrieve technical services from the MethodsServices model.
     *
     * This function selects the 'id', 'code', 'label', and 'type' fields from the MethodsServices model
     * where the 'type' is either 1 or 7. The results are ordered by the 'ordre' field.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of technical services.
     */
    public function getTechServices()
    {
        return MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();
    }

    /**
     * Retrieve a list of BOM (Bill of Materials) services.
     *
     * This method selects the 'id', 'code', 'label', and 'type' fields from the MethodsServices table
     * where the 'type' is either 2, 3, 4, 5, 6, or 8. The results are ordered by the 'ordre' field.
     *
     * @return \Illuminate\Support\Collection A collection of BOM services.
     */
    public function getBOMServices()
    {
        return MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                ->orWhere('type', '=', 3)
                                ->orWhere('type', '=', 4)
                                ->orWhere('type', '=', 5)
                                ->orWhere('type', '=', 6)
                                ->orWhere('type', '=', 8)
                                ->orderBy('ordre')->get();
    }

    /**
     * Retrieve a list of sections.
     *
     * This method fetches all sections from the MethodsSection model,
     * selecting only the 'id' and 'label' columns, and orders them by 'ordre'.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of sections.
     */
    public function getSection()
    {
        return MethodsSection::select('id', 'label')->orderBy('ordre')->get();
    }
    
    /**
     * Retrieve a list of families.
     *
     * This method fetches all records from the MethodsFamilies table,
     * selecting only the 'id' and 'label' columns, and orders them by 'label'.
     *
     * @return \Illuminate\Support\Collection A collection of families with 'id' and 'label' attributes.
     */
    public function getFamilies()
    {
        return MethodsFamilies::select('id', 'label')->orderBy('label')->get();
    }

    /**
     * Retrieve a list of resources.
     *
     * This method fetches resources from the MethodsRessources model,
     * selecting only the 'id' and 'label' columns, and orders them by 'ordre'.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of resources.
     */
    public function getRessources()
    {
        return MethodsRessources::select('id', 'label')->orderBy('ordre')->get();
    }

    /**
     * Retrieve a list of quality causes.
     *
     * This function fetches all quality causes from the database, selecting
     * only the 'id' and 'label' columns. The results are ordered by the 'label'
     * column in ascending order.
     *
     * @return \Illuminate\Support\Collection A collection of quality causes.
     */
    public function getQualityCause()
    {
        return QualityCause::select('id', 'label')->orderBy('label')->get();
    }

    /**
     * Retrieve a list of quality failures.
     *
     * This method fetches all quality failures from the database, selecting
     * only the 'id' and 'label' columns. The results are ordered by the 'label'
     * column in ascending order.
     *
     * @return \Illuminate\Support\Collection A collection of quality failures.
     */
    public function getQualityFailure()
    {
        return QualityFailure::select('id', 'label')->orderBy('label')->get();
    }

    /**
     * Retrieve a list of quality corrections.
     *
     * This method fetches all quality corrections from the database,
     * selecting only the 'id' and 'label' columns. The results are
     * ordered by the 'label' column in ascending order.
     *
     * @return \Illuminate\Support\Collection A collection of quality corrections.
     */
    public function getQualityCorrection()
    {
        return QualityCorrection::select('id', 'label')->orderBy('label')->get();
    }
    
    /**
     * Retrieve a list of quality non-conformities.
     *
     * This function fetches all quality non-conformities from the database,
     * selecting only the 'id' and 'code' columns, and orders them by 'code'.
     *
     * @return \Illuminate\Support\Collection A collection of quality non-conformities.
     */
    public function getQualityNonConformity()
    {
        return QualityNonConformity::select('id', 'code')->orderBy('code')->get();
    }

    /**
     * Retrieve a list of quotes with their IDs and codes, ordered by code.
     *
     * @return \Illuminate\Support\Collection A collection of quotes with 'id' and 'code' fields.
     */
    public function getQuotes()
    {
        return Quotes::select('id', 'code')->orderBy('code')->get();
    }

    /**
     * Retrieve a list of orders with their IDs and codes.
     *
     * This method fetches all orders from the database, selecting only the 'id' and 'code' columns.
     * The results are ordered by the 'code' column in ascending order.
     *
     * @return \Illuminate\Support\Collection A collection of orders with 'id' and 'code' fields.
     */
    public function getOrders()
    {
        return Orders::select('id', 'code')->orderBy('code')->get();
    }

    /**
     * Retrieve a list of order lines.
     *
     * This function selects the 'id' and 'code' columns from the Orders table,
     * orders the results by the 'code' column, and returns the result set.
     *
     * @return \Illuminate\Support\Collection A collection of order lines with 'id' and 'code' columns.
     */
    public function getOrdersLines()
    {
        return Orders::select('id', 'code')->orderBy('code')->get();
    }

    /**
     * Retrieve a list of purchases with their IDs and codes, ordered by code.
     *
     * @return \Illuminate\Support\Collection A collection of purchases with 'id' and 'code' fields.
     */
    public function getPurchases()
    {
        return Purchases::select('id', 'code')->orderBy('code')->get();
    }
}