<?php

namespace App\Console\Commands;

use App\Models\Companies\CompaniesContacts;
use App\Services\RgpdAnonymizationService;
use Illuminate\Console\Command;

class RgpdEraseContact extends Command
{
    protected $signature   = 'rgpd:erase-contact {id : ID du contact dans companies_contacts}';
    protected $description = 'Traite une demande RGPD droit à l\'effacement pour un contact B2B';

    public function handle(RgpdAnonymizationService $service): int
    {
        $contact = CompaniesContacts::find($this->argument('id'));

        if (! $contact) {
            $this->error("Contact ID {$this->argument('id')} introuvable.");
            return self::FAILURE;
        }

        $this->line("Contact : {$contact->first_name} {$contact->name} ({$contact->mail})");
        $this->line("Entreprise liée : " . ($contact->companie?->label ?? 'aucune'));

        if ($service->canHardDeleteContact($contact)) {
            if (! $this->confirm('Aucune pièce comptable liée — suppression définitive ?')) {
                $this->info('Opération annulée.');
                return self::SUCCESS;
            }
            $contact->delete(); // soft delete
            $this->info('Contact supprimé (soft delete, aucune pièce comptable liée).');
        } else {
            $this->warn('Des pièces comptables existent (commandes / factures / livraisons).');
            $this->warn('Suppression impossible — anonymisation appliquée à la place.');

            if (! $this->confirm('Confirmer l\'anonymisation ?')) {
                $this->info('Opération annulée.');
                return self::SUCCESS;
            }

            $service->anonymizeContact($contact);
            $this->info('Contact anonymisé avec succès. Les pièces comptables sont préservées.');
        }

        return self::SUCCESS;
    }
}
