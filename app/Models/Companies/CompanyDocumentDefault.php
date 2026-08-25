<?php

namespace App\Models\Companies;

use Illuminate\Database\Eloquent\Model;

class CompanyDocumentDefault extends Model
{
    protected $table = 'companies_document_defaults';

    public $timestamps = false;

    protected $fillable = [
        'companies_id',
        'document_type',
        'companies_contacts_id',
        'companies_addresses_id',
    ];

    public const TYPES = ['quote', 'order', 'delivery', 'invoice'];

    /**
     * Returns a keyed array of defaults for a company:
     * ['quote' => ['contact_id' => 5, 'address_id' => 3], ...]
     */
    public static function forCompany(int $companiesId): array
    {
        $rows = self::where('companies_id', $companiesId)->get()->keyBy('document_type');

        $result = [];
        foreach (self::TYPES as $type) {
            $row = $rows->get($type);
            $result[$type] = [
                'contact_id' => $row?->companies_contacts_id,
                'address_id' => $row?->companies_addresses_id,
            ];
        }

        return $result;
    }

    /**
     * Resolves the recipient (address, contact) for a document type on a company.
     * Returns the preferred pair if set, otherwise the provided fallback IDs.
     *
     * @return array{address_id: ?int, contact_id: ?int}
     */
    public static function resolveFor(int $companiesId, string $type, ?int $fallbackAddressId = null, ?int $fallbackContactId = null): array
    {
        $defaults = self::forCompany($companiesId)[$type] ?? ['address_id' => null, 'contact_id' => null];

        return [
            'address_id' => $defaults['address_id'] ?? $fallbackAddressId,
            'contact_id' => $defaults['contact_id'] ?? $fallbackContactId,
        ];
    }

    public function company()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    public function address()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }
}
