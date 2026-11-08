<?php

namespace App\Services;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RgpdAnonymizationService
{
    /**
     * Anonymise un contact B2B.
     *
     * Utilisé quand le contact a des pièces comptables liées (commandes, factures,
     * livraisons) : on ne peut pas supprimer, on efface les données personnelles
     * tout en conservant l'intégrité référentielle.
     */
    public function anonymizeContact(CompaniesContacts $contact): void
    {
        DB::transaction(function () use ($contact) {
            // Sans entrée « modifié » : elle recopierait les anciennes valeurs (nom,
            // e-mail) dans un journal désormais conservé sans limite.
            $contact->disableLogging();
            $contact->update([
                'civility'   => null,
                'first_name' => 'Contact',
                'name'       => 'Anonymisé',
                'function'   => null,
                'number'     => null,
                'mobile'     => null,
                'mail'       => 'anonyme-' . $contact->id . '@supprime.invalid',
                'password'   => null,
            ]);
            $contact->enableLogging();

            activity()
                ->performedOn($contact)
                ->withProperties(['rgpd' => 'anonymisation', 'date' => now()->toIso8601String()])
                ->log("Contact anonymisé suite à demande RGPD droit à l'effacement");
        });
    }

    /**
     * Anonymise un utilisateur interne dont le compte est clôturé.
     *
     * Préserve les liens vers les devis/commandes/tâches créés par cet utilisateur.
     * L'email devient non fonctionnel pour bloquer toute reconnexion. Le nom devient
     * un pseudonyme stable (« Utilisateur #12 ») : les visas et le journal d'activité
     * restent attribuables à une même personne sans la nommer.
     */
    public function anonymizeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Sans entrée « modifié » : elle recopierait l'ancien nom et l'ancien e-mail
            // dans un journal désormais conservé sans limite.
            $user->disableLogging();
            $user->update([
                'name'                   => self::userPseudonym($user->id),
                'email'                  => 'deleted-' . $user->id . '@supprime.invalid',
                'personnal_phone_number' => null,
                'born_date'              => null,
                'ssn_num'                => null,
                'nic_num'                => null,
                'driving_license'        => null,
                'address1'               => null,
                'address2'               => null,
                'city'                   => null,
                'country'                => null,
                'province'               => null,
                'postal_code'            => null,
                'home_phone'             => null,
                'mobile_phone'           => null,
                'private_email'          => null,
                'image_url'              => null,
                'desc'                   => null,
                'nationality'            => null,
                'gender'                 => null,
                'marital_status'         => null,
            ]);
            $user->enableLogging();

            // Révoquer tous les tokens d'API et invalider les sessions
            $user->tokens()->delete();

            activity()
                ->performedOn($user)
                ->withProperties(['rgpd' => 'anonymisation', 'date' => now()->toIso8601String()])
                ->log("Utilisateur anonymisé suite à demande RGPD droit à l'effacement");
        });
    }

    public static function userPseudonym(int $id): string
    {
        return 'Utilisateur #' . $id;
    }

    /** Déjà anonymisé par anonymizeUser() (rgpd:purge repasse chaque semaine). */
    public function isAnonymizedUser(User $user): bool
    {
        return $user->email === 'deleted-' . $user->id . '@supprime.invalid';
    }

    /** Déjà anonymisé par anonymizeContact(). */
    public function isAnonymizedContact(CompaniesContacts $contact): bool
    {
        return $contact->mail === 'anonyme-' . $contact->id . '@supprime.invalid';
    }

    /**
     * Vérifie si un contact peut être supprimé définitivement
     * (aucune pièce comptable liée).
     *
     * Si des liens existent, utiliser anonymizeContact() à la place.
     */
    public function canHardDeleteContact(CompaniesContacts $contact): bool
    {
        // Vérification via le modèle Customer qui mappe la même table
        // avec les relations orders/invoices/deliveries
        return \App\Models\Customer\Customer::where('id', $contact->id)
            ->whereDoesntHave('orders')
            ->whereDoesntHave('invoices')
            ->whereDoesntHave('deliveries')
            ->exists();
    }

    /**
     * Suppression complète d'une entreprise sans aucune donnée liée.
     * Cascade sur contacts et adresses (soft delete).
     *
     * Si des devis/commandes/factures existent, ne fait rien et retourne false.
     */
    public function softDeleteCompanyIfNoAccountingData(Companies $company): bool
    {
        $hasAccountingData = $company->Quotes()->exists()
            || $company->Orders()->exists()
            || $company->Invoices()->exists()
            || $company->Purchases()->exists();

        if ($hasAccountingData) {
            return false;
        }

        DB::transaction(function () use ($company) {
            // Soft delete des contacts et adresses liés
            $company->Contacts()->each(fn ($c) => $c->delete());
            $company->Addresses()->each(fn ($a) => $a->delete());
            $company->delete();

            activity()
                ->performedOn($company)
                ->withProperties(['rgpd' => 'suppression', 'date' => now()->toIso8601String()])
                ->log("Entreprise supprimée (soft delete) suite à demande RGPD");
        });

        return true;
    }
}
