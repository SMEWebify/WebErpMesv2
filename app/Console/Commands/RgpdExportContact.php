<?php

namespace App\Console\Commands;

use App\Models\Companies\CompaniesContacts;
use Illuminate\Console\Command;

class RgpdExportContact extends Command
{
    protected $signature   = 'rgpd:export-contact {id : ID du contact dans companies_contacts}';
    protected $description = 'Exporte toutes les données d\'un contact au format JSON (Art. 20 RGPD — portabilité)';

    public function handle(): int
    {
        $contact = CompaniesContacts::with([
            'companie',
            'companie.Addresses',
        ])->find($this->argument('id'));

        if (! $contact) {
            $this->error("Contact ID {$this->argument('id')} introuvable.");
            return self::FAILURE;
        }

        // Charger les données comptables via le modèle Customer (même table)
        $customer = \App\Models\Customer\Customer::with([
            'orders',
            'invoices',
            'deliveries',
        ])->find($contact->id);

        $export = [
            'export_date' => now()->toIso8601String(),
            'rgpd_basis'  => 'Article 20 RGPD — Droit à la portabilité des données',
            'identity'    => [
                'civility'   => $contact->civility,
                'first_name' => $contact->first_name,
                'name'       => $contact->name,
                'function'   => $contact->function,
                'email'      => $contact->mail,
                'phone'      => $contact->number,
                'mobile'     => $contact->mobile,
            ],
            'company'     => $contact->companie ? [
                'label'   => $contact->companie->label,
                'siren'   => $contact->companie->siren ?? null,
                'address' => $contact->companie->Addresses->first()?->only([
                    'adress', 'zipcode', 'city', 'province', 'country',
                ]),
            ] : null,
            'orders'      => $customer?->orders->map(fn ($o) => [
                'reference' => $o->ref ?? $o->id,
                'date'      => $o->created_at,
                'status'    => $o->status ?? null,
            ])->toArray() ?? [],
            'invoices'    => $customer?->invoices->map(fn ($i) => [
                'reference' => $i->ref ?? $i->id,
                'date'      => $i->created_at,
            ])->toArray() ?? [],
            'deliveries'  => $customer?->deliveries->map(fn ($d) => [
                'reference' => $d->ref ?? $d->id,
                'date'      => $d->created_at,
            ])->toArray() ?? [],
            'portal'      => [
                'has_password' => ! is_null($contact->password),
                'last_login'   => $contact->last_login_at ?? null,
            ],
        ];

        $dir = storage_path('app/rgpd');
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $filename = "rgpd_contact_{$contact->id}_" . now()->format('Ymd_His') . '.json';
        $path     = $dir . '/' . $filename;

        file_put_contents($path, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Export généré : storage/app/rgpd/{$filename}");
        $this->line(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
