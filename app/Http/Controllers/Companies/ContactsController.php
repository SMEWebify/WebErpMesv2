<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Http\Requests\Companies\StoreContactRequest;
use App\Http\Requests\Companies\UpdateContactRequest;

class ContactsController extends Controller
{
    //
    public function edit($id)
    {
        $contact = CompaniesContacts::findOrFail($id);
        return view('companies/contacts-edit', [
            'contact' => $contact
        ]);
    }

    public function store(StoreContactRequest $request)
    {
        $contact = CompaniesContacts::create($request->only('companies_id', 'ORDRE', 'CIVILITY', 'FIRST_NAME','NAME','FUNCTION','NUMBER','MOBILE','MAIL'));
        return redirect()->route('companies.show', ['id' => $request->companies_id])->with('success', 'Successfully created contact');
    }

    public function update(UpdateContactRequest $request)
    {
        $contact = CompaniesContacts::find($request->id);
        $contact->ORDRE=$request->ORDRE;
        $contact->CIVILITY=$request->CIVILITY;
        $contact->FIRST_NAME=$request->FIRST_NAME;
        $contact->NAME=$request->NAME;
        $contact->FUNCTION=$request->FUNCTION;
        $contact->NUMBER=$request->NUMBER;
        $contact->MOBILE=$request->MOBILE;
        $contact->MAIL=$request->MAIL;
        $contact->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated contact');
    }
}
