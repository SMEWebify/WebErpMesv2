<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    public function List()
    {

        $Companies = Companies::orderBy('id')->paginate(10);

        return view('companies/companies-list', [
            'Companieslist' => $Companies
        ]);
    }

    public function show($id)
    {

        $Companie = Companies::findOrFail($id);

        return view('companies/companies-show', [
            'Companie' => $Companie
        ]);
    }
}
