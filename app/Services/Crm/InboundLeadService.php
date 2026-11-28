<?php

namespace App\Services\Crm;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Notifications\LeadNotification;
use App\Services\DocumentCodeGenerator;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demande entrante : un e-mail devient une piste, quel que soit son chemin
 * (dépôt d'un .eml / .msg dans l'ERP, ou appel de l'API par n8n qui relève la
 * boîte). L'ERP ne lit aucune boîte mail lui-même.
 *
 * - Un même e-mail (Message-ID, sinon empreinte expéditeur + date + objet) ne
 *   crée qu'une piste : un appel rejoué rend la piste existante.
 * - L'expéditeur est rapproché d'un contact par son adresse, sinon d'une société
 *   par le domaine (hors messageries publiques) ; à défaut, la société est créée
 *   en prospect avec son contact et une adresse à compléter.
 * - L'e-mail d'origine et ses pièces jointes vont dans la GED de la piste.
 */
class InboundLeadService
{
    public function __construct(
        private readonly FileStorageService $files,
        private readonly DocumentCodeGenerator $codes,
    ) {}

    /**
     * @param  array{subject?: ?string, from_email: string, from_name?: ?string, received_at?: mixed, body?: ?string, message_id?: ?string}  $message
     * @param  array<int, array{name: string, mime?: ?string, data: string}>  $attachments
     * @param  array{name: string, mime?: ?string, data: string}|null  $original  e-mail d'origine (.eml / .msg)
     * @return array{0: Leads, 1: bool}  la piste, et vrai si elle vient d'être créée
     */
    public function receive(array $message, array $attachments = [], ?array $original = null, ?int $ownerId = null): array
    {
        $email = mb_strtolower(trim($message['from_email']));
        $subject = self::clean($message['subject'] ?? null, 255);
        $receivedAt = self::date($message['received_at'] ?? null);
        $messageId = self::messageId($message['message_id'] ?? null)
            ?? 'sha1:' . sha1($email . '|' . $receivedAt->toIso8601String() . '|' . $subject);

        $existing = Leads::withTrashed()->where('message_id', $messageId)->first();
        if ($existing) {
            return [$existing, false];
        }

        try {
            $lead = $this->createLead($message, $email, $subject, $receivedAt, $messageId, $ownerId);
        } catch (UniqueConstraintViolationException) {
            // Le même e-mail reçu deux fois en même temps : la première piste gagne.
            return [Leads::withTrashed()->where('message_id', $messageId)->firstOrFail(), false];
        }

        if ($original !== null) {
            $this->attach($lead, $original, true);
        }
        foreach ($attachments as $attachment) {
            $this->attach($lead, $attachment, false);
        }

        if ($ownerId && ($owner = User::find($ownerId))) {
            $owner->notify(new LeadNotification(['id' => $lead->id, 'code' => $subject ?? ('#' . $lead->id)]));
        }

        return [$lead, true];
    }

    private function createLead(array $message, string $email, ?string $subject, Carbon $receivedAt, string $messageId, ?int $ownerId): Leads
    {
        return DB::transaction(function () use ($message, $email, $subject, $receivedAt, $messageId, $ownerId) {
            [$company, $contact, $address] = $this->resolveParty($email, self::clean($message['from_name'] ?? null, 255), $ownerId);

            return Leads::create([
                'companies_id'           => $company->id,
                'companies_contacts_id'  => $contact->id,
                'companies_addresses_id' => $address->id,
                'user_id'                => $ownerId,
                'statu'                  => 1,
                'source'                 => config('leads.inbound.source', 'E-mail'),
                'priority'               => 3,
                'subject'                => $subject,
                'received_at'            => $receivedAt,
                'sender_email'           => $email,
                'message_id'             => $messageId,
                'comment'                => self::bodyAsHtml($message['body'] ?? null),
            ]);
        });
    }

    /**
     * Contact, sinon société du domaine, sinon prospect créé.
     *
     * @return array{0: Companies, 1: CompaniesContacts, 2: CompaniesAddresses}
     */
    private function resolveParty(string $email, ?string $name, ?int $ownerId): array
    {
        $contact = CompaniesContacts::whereRaw('LOWER(mail) = ?', [$email])
            ->whereIn('companies_id', Companies::query()->select('id'))
            ->orderByDesc('id')
            ->first();

        if ($contact) {
            $company = Companies::findOrFail($contact->companies_id);

            return [$company, $contact, $this->addressOf($company)];
        }

        $domain = self::domain($email);
        $company = $domain ? $this->companyOfDomain($domain) : null;

        if (! $company) {
            $company = $this->createProspect(
                $name ?: ($domain && ! self::isPublic($domain) ? $domain : $email),
                $ownerId,
                __('inbound_leads.created_comment', ['email' => $email]),
            );
        }

        [$firstName, $lastName] = self::splitName($name, $email);
        $contact = CompaniesContacts::create([
            'companies_id' => $company->id,
            'ordre'        => (int) CompaniesContacts::where('companies_id', $company->id)->max('ordre') + 1,
            'first_name'   => $firstName,
            'name'         => $lastName,
            'mail'         => $email,
            'default'      => CompaniesContacts::where('companies_id', $company->id)->exists() ? 0 : 1,
        ]);

        return [$company, $contact, $this->addressOf($company)];
    }

    /** Société prospect créée pour une demande (e-mail reçu, dictée). */
    public function createProspect(string $label, ?int $ownerId, ?string $comment): Companies
    {
        return Companies::create([
            'uuid'    => Str::uuid(),
            'code'    => $this->codes->generateDocumentCode('company', (int) Companies::withTrashed()->max('id')),
            'label'   => $label,
            // statu_customer : 1 = Inactif, 2 = Actif, 3 = Prospect (la valeur par défaut 1 la masquait des sélecteurs).
            'statu_customer' => 3,
            'user_id' => $ownerId ?? 0,
            'comment' => $comment,
        ]);
    }

    public function companyOfDomain(string $domain): ?Companies
    {
        if (self::isPublic($domain)) {
            return null;
        }

        $viaContact = CompaniesContacts::whereRaw('LOWER(mail) LIKE ?', ['%@' . $domain])
            ->whereIn('companies_id', Companies::query()->select('id'))
            ->orderByDesc('id')
            ->first();

        if ($viaContact) {
            return Companies::find($viaContact->companies_id);
        }

        return Companies::where('website', 'like', '%' . $domain . '%')->orderByDesc('id')->first();
    }

    /** Adresse par défaut de la société, sinon la première, sinon une adresse à compléter. */
    public function addressOf(Companies $company): CompaniesAddresses
    {
        return CompaniesAddresses::where('companies_id', $company->id)->orderByDesc('default')->orderBy('ordre')->first()
            ?? CompaniesAddresses::create([
                'companies_id' => $company->id,
                'ordre'        => 1,
                'label'        => __('inbound_leads.address_to_complete'),
                'default'      => 1,
            ]);
    }

    /** @param array{name: string, mime?: ?string, data: string} $part */
    private function attach(Leads $lead, array $part, bool $primary): void
    {
        $name = trim($part['name'] ?? '') !== '' ? $part['name'] : 'piece-jointe';
        $file = $this->files->storeContents($part['data'], $name, $part['mime'] ?? 'application/octet-stream', [
            'hashtags' => ['demande'],
        ]);
        $this->files->attach($file, $lead, FileRole::OTHER, $primary);
    }

    public static function domain(string $email): ?string
    {
        $at = strrpos($email, '@');

        return $at === false ? null : mb_strtolower(substr($email, $at + 1));
    }

    public static function isPublic(string $domain): bool
    {
        return in_array($domain, config('leads.inbound.public_domains', []), true);
    }

    /** Message-ID sans chevrons ni espaces ; null s'il est vide. */
    public static function messageId(?string $raw): ?string
    {
        $id = trim((string) $raw, " \t\r\n<>");

        return $id === '' ? null : mb_substr($id, 0, 255);
    }

    /** @return array{0: ?string, 1: string} prénom, nom */
    public static function splitName(?string $name, string $email): array
    {
        $name = trim((string) $name, " \t\"'");
        if ($name === '') {
            return [null, Str::before($email, '@')];
        }

        $parts = preg_split('/\s+/', $name);
        if (count($parts) === 1) {
            return [null, $parts[0]];
        }

        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }

    private static function date(mixed $value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        try {
            return $value ? Carbon::parse($value) : now();
        } catch (\Throwable) {
            return now();
        }
    }

    public static function clean(?string $value, int $max): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /** Texte du message, échappé, pour le commentaire (éditeur riche) de la piste. */
    public static function bodyAsHtml(?string $body): ?string
    {
        $body = trim((string) $body);
        if ($body === '') {
            return null;
        }

        $body = mb_substr($body, 0, (int) config('leads.inbound.max_body_chars', 20000));

        return nl2br(e($body), false);
    }
}
