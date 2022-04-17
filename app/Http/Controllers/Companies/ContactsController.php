<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Http\Requests\Companies\StoreContactRequest;
use App\Http\Requests\Companies\UpdateContactRequest;

class ContactsController extends Controller
{
    /**
     * @param $id
     * @return View
     */
    public function edit($id)
    {
        $contact = CompaniesContacts::findOrFail($id);
        return view('companies/contacts-edit', [
            'contact' => $contact
        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreContactRequest $request)
    {
        $contact = CompaniesContacts::create($request->only('companies_id', 'ordre', 'civility', 'first_name','name','function','number','mobile','mail'));
        return redirect()->route('companies.show', ['id' => $request->companies_id])->with('success', 'Successfully created contact');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateContactRequest $request)
    {
        $contact = CompaniesContacts::find($request->id);
        $contact->ordre=$request->ordre;
        $contact->civility=$request->civility;
        $contact->first_name=$request->first_name;
        $contact->name=$request->name;
        $contact->function=$request->function;
        $contact->number=$request->number;
        $contact->mobile=$request->mobile;
        $contact->mail=$request->mail;
        $contact->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated contact');
    }
}
