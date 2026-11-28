<?php

namespace App\Services\Crm;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompanyExternalRef;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Notifications\LeadNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Société reçue d'un logiciel tiers (chiffrage, formulaire du site, salon, CRM) :
 * société, contact et adresse, plus une piste si l'appel porte une demande.
 *
 * - Rapprochement : référence chez le logiciel (`system` + `company.external_id`),
 *   puis SIREN, contact par e-mail, société du domaine de l'e-mail (hors
 *   messageries publiques) ; à défaut, société créée.
 * - Une fiche déjà connue est seulement **complétée** : un champ vide dans l'ERP
 *   est rempli, une valeur saisie n'est jamais écrasée. Seule exception voulue :
 *   `company.status = customer` fait passer un prospect ou un inactif en client.
 * - Une demande rejouée (même `external_id`, sinon même e-mail + objet + message)
 *   rend la piste existante.
 */
class InboundProspectService
{
    /** statu_customer : 1 = Inactif, 2 = Actif (client), 3 = Prospect. */
    public const CUSTOMER = 2;
    public const PROSPECT = 3;

    /** Champs de la société complétés s'ils sont vides dans l'ERP. */
    private const COMPANY_FILLABLE = ['siren' => 20, 'naf_code' => 10, 'intra_community_vat' => 20, 'website' => 255];

    /** Champs du contact complétés s'ils sont vides (clé de l'API => colonne, longueur). */
    private const CONTACT_FILLABLE = [
        'civility'   => ['civility', 50],
        'first_name' => ['first_name', 255],
        'name'       => ['name', 255],
        'function'   => ['function', 255],
        'phone'      => ['number', 50],
        'mobile'     => ['mobile', 50],
    ];

    public function __construct(private readonly InboundLeadService $leads) {}

    /**
     * @param  array<string, mixed>  $data  champs validés par le contrôleur
     * @return array{company: Companies, company_created: bool, company_updated: list<string>, matched_by: ?string, contact: CompaniesContacts, contact_created: bool, contact_updated: list<string>, lead: ?Leads, lead_created: bool}
     */
    public function receive(array $data, ?int $ownerId): array
    {
        $company = $data['company'] ?? [];
        $company['siren'] = self::siren($company['siren'] ?? null);
        $contact = $data['contact'] ?? [];
        $email = isset($contact['email']) ? mb_strtolower(trim($contact['email'])) : null;
        $address = $data['address'] ?? null;
        $system = $data['system'] ?? null;
        $externalId = InboundLeadService::clean($company['external_id'] ?? null, 191);

        $leadData = $data['lead'] ?? null;
        $messageId = $leadData !== null ? self::leadKey($leadData, $email) : null;

        if ($messageId !== null && ($existing = Leads::withTrashed()->where('message_id', $messageId)->first())) {
            return $this->replayed($existing);
        }

        try {
            $result = DB::transaction(function () use ($company, $contact, $email, $address, $system, $externalId, $leadData, $messageId, $ownerId) {
                [$party, $matchedBy] = $this->findCompany($system, $externalId, $company['siren'], $email);

                if ($party === null) {
                    $party = $this->createCompany($company, $contact, $email, $ownerId, $address);
                    $companyUpdated = [];
                } else {
                    $companyUpdated = $this->completeCompany($party, $company, $address);
                }

                if ($system !== null && $externalId !== null) {
                    CompanyExternalRef::updateOrCreate(
                        ['system' => $system, 'external_id' => $externalId],
                        ['companies_id' => $party->id],
                    );
                }

                [$person, $contactCreated, $contactUpdated] = $this->findOrCreateContact($party, $contact, $email);

                $lead = $leadData === null ? null : Leads::create([
                    'companies_id'           => $party->id,
                    'companies_contacts_id'  => $person->id,
                    'companies_addresses_id' => $this->leads->addressOf($party)->id,
                    'user_id'                => $ownerId,
                    'statu'                  => 1,
                    'source'                 => InboundLeadService::clean($leadData['source'] ?? null, 255)
                                                ?? ($system !== null ? ucfirst($system) : 'API'),
                    'campaign'               => InboundLeadService::clean($leadData['campaign'] ?? null, 255),
                    'priority'               => 3,
                    'subject'                => InboundLeadService::clean($leadData['subject'] ?? null, 255),
                    'received_at'            => self::date($leadData['received_at'] ?? null),
                    'sender_email'           => $email,
                    'message_id'             => $messageId,
                    'comment'                => InboundLeadService::bodyAsHtml($leadData['message'] ?? null),
                ]);

                return [
                    'company'         => $party,
                    'company_created' => $matchedBy === null,
                    'company_updated' => $companyUpdated,
                    'matched_by'      => $matchedBy,
                    'contact'         => $person,
                    'contact_created' => $contactCreated,
                    'contact_updated' => $contactUpdated,
                    'lead'            => $lead,
                    'lead_created'    => $lead !== null,
                ];
            });
        } catch (UniqueConstraintViolationException $e) {
            // La même demande reçue deux fois en même temps : la première gagne.
            $existing = $messageId !== null ? Leads::withTrashed()->where('message_id', $messageId)->first() : null;
            if ($existing === null) {
                throw $e;
            }

            return $this->replayed($existing);
        }

        return $result;
    }

    /** @return array{0: ?Companies, 1: ?string} */
    private function findCompany(?string $system, ?string $externalId, ?string $siren, ?string $email): array
    {
        if ($system !== null && $externalId !== null) {
            $ref = CompanyExternalRef::where('system', $system)->where('external_id', $externalId)->first();
            if ($ref && ($byRef = Companies::find($ref->companies_id))) {
                return [$byRef, 'external_id'];
            }
        }

        if ($siren !== null) {
            $bySiren = Companies::whereRaw("REPLACE(siren, ' ', '') = ?", [$siren])->orderBy('id')->first();
            if ($bySiren) {
                return [$bySiren, 'siren'];
            }
        }

        if ($email !== null) {
            $known = CompaniesContacts::whereRaw('LOWER(mail) = ?', [$email])
                ->whereIn('companies_id', Companies::query()->select('id'))
                ->orderByDesc('id')
                ->first();
            if ($known) {
                return [Companies::find($known->companies_id), 'contact'];
            }

            $domain = InboundLeadService::domain($email);
            if ($domain && ($byDomain = $this->leads->companyOfDomain($domain))) {
                return [$byDomain, 'domain'];
            }
        }

        return [null, null];
    }

    private function createCompany(array $company, array $contact, ?string $email, ?int $ownerId, ?array $address): Companies
    {
        $label = InboundLeadService::clean($company['label'] ?? null, 255)
            ?? InboundLeadService::clean(trim(($contact['first_name'] ?? '') . ' ' . ($contact['name'] ?? '')), 255)
            ?? $email;

        $party = $this->leads->createProspect($label, $ownerId, __('inbound_leads.prospect_created_comment'));

        $values = [];
        foreach (self::COMPANY_FILLABLE as $field => $max) {
            $values[$field] = InboundLeadService::clean($company[$field] ?? null, $max);
        }
        if (($company['status'] ?? null) === 'customer') {
            $values['statu_customer'] = self::CUSTOMER;
        }
        $party->forceFill(array_filter($values, fn ($value) => $value !== null))->save();

        if (self::hasAddress($address)) {
            CompaniesAddresses::create(['companies_id' => $party->id, 'ordre' => 1, 'default' => 1] + self::addressValues($address));
        }

        return $party;
    }

    /**
     * Complète une société connue : champs vides, adresse absente ou « à compléter »,
     * passage en client. Rien de ce qui est saisi n'est écrasé.
     *
     * @return list<string> champs complétés
     */
    private function completeCompany(Companies $party, array $company, ?array $address): array
    {
        $updated = [];

        foreach (self::COMPANY_FILLABLE as $field => $max) {
            $value = InboundLeadService::clean($company[$field] ?? null, $max);
            if ($value !== null && blank($party->{$field})) {
                $party->{$field} = $value;
                $updated[] = $field;
            }
        }

        if (($company['status'] ?? null) === 'customer' && (int) $party->statu_customer !== self::CUSTOMER) {
            $party->statu_customer = self::CUSTOMER;
            $updated[] = 'status';
        }

        if ($updated !== []) {
            $party->save();
        }

        if (self::hasAddress($address)) {
            $current = CompaniesAddresses::where('companies_id', $party->id)->orderByDesc('default')->orderBy('ordre')->first();

            if ($current === null) {
                CompaniesAddresses::create(['companies_id' => $party->id, 'ordre' => 1, 'default' => 1] + self::addressValues($address));
                $updated[] = 'address';
            } elseif (blank($current->adress) && blank($current->zipcode) && blank($current->city)) {
                // Adresse « à compléter » posée par une demande entrante : on la remplit.
                $current->fill(self::addressValues($address))->save();
                $updated[] = 'address';
            }
        }

        return $updated;
    }

    /** @return array{0: CompaniesContacts, 1: bool, 2: list<string>} contact, créé, champs complétés */
    private function findOrCreateContact(Companies $party, array $contact, ?string $email): array
    {
        if ($email !== null) {
            $known = CompaniesContacts::where('companies_id', $party->id)->whereRaw('LOWER(mail) = ?', [$email])->first();
            if ($known) {
                $updated = [];
                foreach (self::CONTACT_FILLABLE as $key => [$column, $max]) {
                    $value = InboundLeadService::clean($contact[$key] ?? null, $max);
                    if ($value !== null && blank($known->{$column})) {
                        $known->{$column} = $value;
                        $updated[] = $key;
                    }
                }
                if ($updated !== []) {
                    $known->save();
                }

                return [$known, false, $updated];
            }
        }

        [$firstName, $lastName] = InboundLeadService::clean($contact['name'] ?? null, 255) !== null
            ? [InboundLeadService::clean($contact['first_name'] ?? null, 255), InboundLeadService::clean($contact['name'], 255)]
            : InboundLeadService::splitName($contact['first_name'] ?? null, (string) $email);

        $created = CompaniesContacts::create([
            'companies_id' => $party->id,
            'ordre'        => (int) CompaniesContacts::where('companies_id', $party->id)->max('ordre') + 1,
            'civility'     => InboundLeadService::clean($contact['civility'] ?? null, 50),
            'first_name'   => $firstName,
            'name'         => $lastName,
            'function'     => InboundLeadService::clean($contact['function'] ?? null, 255),
            'number'       => InboundLeadService::clean($contact['phone'] ?? null, 50),
            'mobile'       => InboundLeadService::clean($contact['mobile'] ?? null, 50),
            'mail'         => $email,
            'default'      => CompaniesContacts::where('companies_id', $party->id)->exists() ? 0 : 1,
        ]);

        return [$created, true, []];
    }

    /** SIREN sur 9 chiffres (un SIRET donne son SIREN) ; null si absent ou mal formé. */
    public static function siren(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        return in_array(strlen($digits), [9, 14], true) ? substr($digits, 0, 9) : null;
    }

    private static function hasAddress(?array $address): bool
    {
        return filled($address['adress'] ?? null) || filled($address['zipcode'] ?? null) || filled($address['city'] ?? null);
    }

    private static function addressValues(array $address): array
    {
        return [
            'label'   => InboundLeadService::clean($address['label'] ?? null, 255) ?? __('company_registry.address_label'),
            'adress'  => InboundLeadService::clean($address['adress'] ?? null, 255),
            'zipcode' => InboundLeadService::clean($address['zipcode'] ?? null, 20),
            'city'    => InboundLeadService::clean($address['city'] ?? null, 255),
            'country' => InboundLeadService::clean($address['country'] ?? null, 255) ?? 'France',
        ];
    }

    /** Clé d'unicité de la piste, rangée dans `leads.message_id`. */
    private static function leadKey(array $lead, ?string $email): string
    {
        $external = trim((string) ($lead['external_id'] ?? ''));
        if ($external !== '') {
            return mb_substr('api:' . $external, 0, 255);
        }

        return 'sha1:' . sha1('prospect|' . $email . '|' . trim((string) ($lead['subject'] ?? '')) . '|' . trim((string) ($lead['message'] ?? '')));
    }

    private static function date(mixed $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : now();
        } catch (\Throwable) {
            return now();
        }
    }

    private function replayed(Leads $lead): array
    {
        return [
            'company'         => Companies::withTrashed()->findOrFail($lead->companies_id),
            'company_created' => false,
            'company_updated' => [],
            'matched_by'      => null,
            'contact'         => CompaniesContacts::findOrFail($lead->companies_contacts_id),
            'contact_created' => false,
            'contact_updated' => [],
            'lead'            => $lead,
            'lead_created'    => false,
        ];
    }

    /** Prévient le responsable d'une piste créée (jamais d'une piste rejouée). */
    public static function notifyOwner(?int $ownerId, Leads $lead): void
    {
        if ($ownerId && ($owner = User::find($ownerId))) {
            $owner->notify(new LeadNotification(['id' => $lead->id, 'code' => $lead->subject ?? ('#' . $lead->id)]));
        }
    }
}
