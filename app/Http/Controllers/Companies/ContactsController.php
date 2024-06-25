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
     * @param Request $request
     * @return View
     */
    public function store(StoreContactRequest $request)
    {
        $contact = CompaniesContacts::create($request->validated());
        return redirect()->route('companies.show', ['id' => $request->companies_id])->with('success', 'Successfully created contact');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateContactRequest $request)
    {
        $contact = CompaniesContacts::findOrFail($request->id);
        $contact->update($request->validated()); // Use mass assignment with validation
        $contact->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated contact');
    }
}
