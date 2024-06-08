<?php

namespace App\Services;

use App\Models\User;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsUnits;
use App\Models\Quality\QualityCause;
use App\Models\Methods\MethodsSection;
use App\Models\Quality\QualityFailure;
use App\Models\Methods\MethodsFamilies;
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
    public function getUsers()
    {
        return User::select('id', 'name')->get();
    }

    
    public function getCompanies()
    {
        return Companies::select('id', 'code','client_type','civility','label','last_name')->where('active', 1)->get();
    }

    public function getSupplier()
    {
        return Companies::select('id', 'code','client_type','civility','label','last_name')->orderBy('label')->where('statu_supplier', 2)->get();
    }

    public function getAddress()
    {
        return CompaniesAddresses::select('id', 'label','adress')->get();
    }

    public function getContact()
    {
        return CompaniesContacts::select('id', 'first_name','name')->get();
    }

    public function getAccountingPaymentConditions()
    {
        return AccountingPaymentConditions::select('id', 'code','label')->get();
    }

    public function getAccountingPaymentMethod()
    {
        return AccountingPaymentMethod::select('id', 'code','label')->get();
    }

    public function getAccountingDelivery()
    {
        return AccountingDelivery::select('id', 'code','label')->get();
    }

    public function getVATSelect()
    {
        return AccountingVat::select('id', 'label')->orderBy('rate')->get();
    }

    public function getProductsSelect()
    {
        return Products::select('id', 'label', 'code', 'methods_services_id')->orderBy('code')->get();
    }

    public function getUnitsSelect()
    {
        return MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
    }

    public function getServices()
    {
        return MethodsServices::select('id', 'label')->orderBy('ordre')->get();
    }

    public function getSection()
    {
        return MethodsSection::select('id', 'label')->orderBy('ordre')->get();
    }
    
    public function getFamilies()
    {
        return MethodsFamilies::select('id', 'label')->orderBy('label')->get();
    }

    public function getRessources()
    {
        return MethodsRessources::select('id', 'label')->orderBy('ordre')->get();
    }

    public function getQualityCause()
    {
        return QualityCause::select('id', 'label')->orderBy('label')->get();
    }

    public function getQualityFailure()
    {
        return QualityFailure::select('id', 'label')->orderBy('label')->get();
    }

    public function getQualityCorrection()
    {
        return QualityCorrection::select('id', 'label')->orderBy('label')->get();
    }
    
    public function getQualityNonConformity()
    {
        return QualityNonConformity::select('id', 'code')->orderBy('code')->get();
    }
}