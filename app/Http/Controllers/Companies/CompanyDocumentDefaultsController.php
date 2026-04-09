<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompanyDocumentDefault;

class CompanyDocumentDefaultsController extends Controller
{
    /**
     * Set which document types a contact is default for.
     * Clears other contacts from those same types if needed.
     *
     * Body: { document_types: ['quote', 'delivery'] }
     */
    public function syncContact(Request $request, Companies $company, CompaniesContacts $contact)
    {
        $request->validate([
            'document_types'   => 'present|array',
            'document_types.*' => 'in:quote,order,delivery,invoice',
        ]);

        $selected = $request->input('document_types', []);

        foreach (CompanyDocumentDefault::TYPES as $type) {
            $row = CompanyDocumentDefault::firstOrNew([
                'companies_id'  => $company->id,
                'document_type' => $type,
            ]);

            if (in_array($type, $selected)) {
                $row->companies_contacts_id = $contact->id;
            } elseif ($row->companies_contacts_id == $contact->id) {
                $row->companies_contacts_id = null;
            }

            $row->save();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Set which document types an address is default for.
     *
     * Body: { document_types: ['quote', 'order'] }
     */
    public function syncAddress(Request $request, Companies $company, CompaniesAddresses $address)
    {
        $request->validate([
            'document_types'   => 'present|array',
            'document_types.*' => 'in:quote,order,delivery,invoice',
        ]);

        $selected = $request->input('document_types', []);

        foreach (CompanyDocumentDefault::TYPES as $type) {
            $row = CompanyDocumentDefault::firstOrNew([
                'companies_id'  => $company->id,
                'document_type' => $type,
            ]);

            if (in_array($type, $selected)) {
                $row->companies_addresses_id = $address->id;
            } elseif ($row->companies_addresses_id == $address->id) {
                $row->companies_addresses_id = null;
            }

            $row->save();
        }

        return response()->json(['ok' => true]);
    }
}
