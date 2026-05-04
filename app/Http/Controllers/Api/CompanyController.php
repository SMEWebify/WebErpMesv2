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
     * @param  \App\Models\Companies\Companies  $company
     */
    public function show(Companies $company)
    {
        return new CompanieResource($company->load(['Contacts', 'Addresses']));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CompanieResource::collection(Companies::with(['Contacts', 'Addresses'])->paginate(10));
    }

    /**
     * Active clients (statu_customer = 2) with name and addresses.
     */
    public function clients(Request $request)
    {
        $query = Companies::with(['Addresses', 'Contacts'])
            ->where('statu_customer', 2)
            ->orderBy('label');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        return $query->get()->map(fn($c) => [
            'id'        => $c->id,
            'code'      => $c->code,
            'label'     => $c->label,
            'contacts'  => $c->Contacts->map(fn($ct) => [
                'id'         => $ct->id,
                'first_name' => $ct->first_name,
                'name'       => $ct->name,
                'mail'       => $ct->mail,
                'number'     => $ct->number,
                'mobile'     => $ct->mobile,
                'default'    => (bool) $ct->default,
            ]),
            'addresses' => $c->Addresses->map(fn($a) => [
                'id'       => $a->id,
                'label'    => $a->label,
                'adress'   => $a->adress,
                'zipcode'  => $a->zipcode,
                'city'     => $a->city,
                'country'  => $a->country,
                'default'  => (bool) $a->default,
            ]),
        ]);
    }

    /**
     * Create new company
     */
    public function store(Request $request)
    {
        // validate the request
        $validatedData = $request->validate([
            'code'                => 'required|string|unique:companies,code|max:50',
            'label'               => 'required|string|max:255',
            'siren'               => 'nullable|string|max:14',
            'naf_code'            => 'nullable|string|max:10',
            'intra_community_vat' => 'nullable|string|max:20',
            'website'             => 'nullable|url|max:255',
            'longitude'           => 'nullable|string|max:50',
            'latitude'            => 'nullable|string|max:50',
        ]);

        $company = Companies::create(array_merge($validatedData, [
            'user_id' => auth()->id(),
        ]));

        // return the company
        return new CompanieResource($company);
    }
}
