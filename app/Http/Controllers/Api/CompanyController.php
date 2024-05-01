<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanieResource;

class CompanyController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Companies  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Companies $company)
    {
        return new CompanieResource($company);
    }
}
