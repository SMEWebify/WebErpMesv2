<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Crm\InboundProspectService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Société reçue d'un logiciel tiers (chiffrage, formulaire du site, salon, CRM) :
 * société, contact et adresse rapprochés ou créés, plus une piste si l'appel porte
 * une demande (`lead`). Une fiche déjà connue est seulement complétée (champs
 * vides), sauf `company.status = customer` qui la fait passer client.
 *
 * Jeton Sanctum de l'utilisateur d'intégration ; un jeton restreint doit porter
 * la capacité « prospects:write ». 201 si quelque chose a été créé, 200 sinon
 * (fiche connue, complétée ou non ; demande rejouée).
 */
class InboundProspectController extends Controller
{
    public function store(Request $request, InboundProspectService $prospects)
    {
        abort_unless($request->user()?->tokenCan('prospects:write'), 403);

        $validated = $request->validate([
            'system'                      => ['nullable', 'required_with:company.external_id', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'company'                     => 'nullable|array',
            'company.external_id'         => 'nullable|string|max:191',
            'company.status'              => 'nullable|in:prospect,customer',
            'company.label'               => 'nullable|string|max:255',
            'company.siren'               => 'nullable|string|max:20',
            'company.naf_code'            => 'nullable|string|max:10',
            'company.intra_community_vat' => 'nullable|string|max:20',
            'company.website'             => 'nullable|string|max:255',
            'contact'                     => 'required|array',
            'contact.civility'            => 'nullable|string|max:50',
            'contact.first_name'          => 'nullable|string|max:255',
            'contact.name'                => 'nullable|string|max:255',
            'contact.email'               => 'nullable|email|max:255',
            'contact.phone'               => 'nullable|string|max:50',
            'contact.mobile'              => 'nullable|string|max:50',
            'contact.function'            => 'nullable|string|max:255',
            'address'                     => 'nullable|array',
            'address.label'               => 'nullable|string|max:255',
            'address.adress'              => 'nullable|string|max:255',
            'address.zipcode'             => 'nullable|string|max:20',
            'address.city'                => 'nullable|string|max:255',
            'address.country'             => 'nullable|string|max:255',
            'lead'                        => 'nullable|array',
            'lead.subject'                => 'nullable|string|max:1000',
            'lead.message'                => 'nullable|string',
            'lead.source'                 => 'nullable|string|max:255',
            'lead.campaign'               => 'nullable|string|max:255',
            'lead.external_id'            => 'nullable|string|max:200',
            'lead.received_at'            => 'nullable|date',
            'owner_email'                 => 'nullable|email',
        ]);

        $this->assertIdentifiable($validated);

        $ownerEmail = $validated['owner_email'] ?? config('leads.inbound.default_owner_email');
        $ownerId = $ownerEmail ? User::where('email', $ownerEmail)->value('id') : null;

        $result = $prospects->receive($validated, $ownerId);

        if ($result['lead_created']) {
            InboundProspectService::notifyOwner($ownerId, $result['lead']);
        }

        $created = $result['company_created'] || $result['contact_created'] || $result['lead_created'];
        $company = $result['company'];
        $lead = $result['lead'];

        return response()->json([
            'company' => [
                'id'         => $company->id,
                'code'       => $company->code,
                'label'      => $company->label,
                'created'    => $result['company_created'],
                'matched_by' => $result['matched_by'],
                'updated'    => $result['company_updated'],
                'url'        => route('companies.show', ['id' => $company->id]),
            ],
            'contact' => [
                'id'      => $result['contact']->id,
                'created' => $result['contact_created'],
                'updated' => $result['contact_updated'],
            ],
            'lead' => $lead === null ? null : [
                'id'      => $lead->id,
                'created' => $result['lead_created'],
                'url'     => route('leads.show', ['id' => $lead->id]),
            ],
        ], $created ? 201 : 200);
    }

    /** Une personne à rappeler et de quoi la retrouver, sinon le prospect ne sert à rien. */
    private function assertIdentifiable(array $data): void
    {
        $contact = $data['contact'] ?? [];
        $errors = [];

        if (blank($contact['email'] ?? null) && blank($contact['name'] ?? null) && blank($contact['first_name'] ?? null)) {
            $errors['contact.email'] = __('inbound_leads.prospect_contact_required');
        }
        if (blank($contact['email'] ?? null) && blank($contact['phone'] ?? null) && blank($contact['mobile'] ?? null)) {
            $errors['contact.phone'] = __('inbound_leads.prospect_reachable_required');
        }

        $siren = $data['company']['siren'] ?? null;
        if (filled($siren) && InboundProspectService::siren($siren) === null) {
            $errors['company.siren'] = __('inbound_leads.prospect_siren_invalid');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
