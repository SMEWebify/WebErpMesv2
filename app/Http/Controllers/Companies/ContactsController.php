<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\CompaniesContacts;
use App\Http\Requests\Companies\StoreContactRequest;
use App\Http\Requests\Companies\UpdateContactRequest;

class ContactsController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreContactRequest $request)
    {
        $contact = CompaniesContacts::create($request->validated());
        return redirect()->route('companies.show', ['id' => $request->companies_id])->with('success', 'Successfully created contact');
    }

    public function storeJson(StoreContactRequest $request)
    {
        $contact = CompaniesContacts::create($request->validated());
        $contact->default = $request->boolean('default');
        $contact->save();

        if ($contact->default) {
            CompaniesContacts::where('companies_id', $contact->companies_id)
                ->where('id', '!=', $contact->id)
                ->update(['default' => 0]);
        }

        return response()->json($this->formatContact($contact), 201);
    }

    public function updateJson(UpdateContactRequest $request, CompaniesContacts $contact)
    {
        $contact->update($request->validated());
        $contact->default = $request->boolean('default');
        $contact->save();

        if ($contact->default) {
            CompaniesContacts::where('companies_id', $contact->companies_id)
                ->where('id', '!=', $contact->id)
                ->update(['default' => 0]);
        }

        return response()->json($this->formatContact($contact));
    }

    private function formatContact(CompaniesContacts $c): array
    {
        return [
            'id'           => $c->id,
            'companies_id' => $c->companies_id,
            'ordre'        => $c->ordre,
            'civility'     => $c->civility,
            'first_name'   => $c->first_name,
            'name'         => $c->name,
            'function'     => $c->function,
            'number'       => $c->number,
            'mobile'       => $c->mobile,
            'mail'         => $c->mail,
            'default'      => (bool) $c->default,
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateContactRequest $request)
    {
        $contact = CompaniesContacts::findOrFail($request->id);
        $contact->update($request->validated()); // Use mass assignment with validation
        if($request->defaultContact_update) $contact->default=1;
        else $contact->default = 0;
        $contact->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated contact');
    }
}
