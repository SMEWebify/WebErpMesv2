<?php

namespace App\Services;

use App\Models\Admin\Factory;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Self-service RGPD : export (Art. 15/20) + soumission d'une demande
 * d'effacement (Art. 17) qui sera validée manuellement par un admin via
 * la commande `rgpd:erase-contact` (B2B) ou anonymisation utilisateur.
 *
 * L'effacement n'est PAS appliqué automatiquement : il peut nécessiter
 * une anonymisation si des pièces comptables sont liées (obligation
 * fiscale FR — conservation 10 ans des factures).
 */
class RgpdSelfServiceService
{
    public function exportUser(User $user): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'rgpd_basis'  => 'Articles 15 et 20 RGPD — Droit d\'accès et portabilité',
            'subject'     => 'user',
            'identity'    => [
                'id'                     => $user->id,
                'name'                   => $user->name,
                'email'                  => $user->email,
                'job_title'              => $user->job_title ?? null,
                'employment_status'      => $user->employment_status ?? null,
                'personnal_phone_number' => $user->personnal_phone_number,
                'born_date'              => $user->born_date,
                'address1'               => $user->address1,
                'address2'               => $user->address2,
                'city'                   => $user->city,
                'postal_code'            => $user->postal_code,
                'country'                => $user->country,
                'home_phone'             => $user->home_phone,
                'mobile_phone'           => $user->mobile_phone,
                'private_email'          => $user->private_email,
                'nationality'            => $user->nationality ?? null,
                'gender'                 => $user->gender ?? null,
                'marital_status'         => $user->marital_status ?? null,
            ],
            'account'     => [
                'created_at'        => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'roles'             => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [],
            ],
        ];
    }

    public function exportContact(CompaniesContacts $contact): array
    {
        $customer = \App\Models\Customer\Customer::with(['orders', 'invoices', 'deliveries'])
            ->find($contact->id);

        return [
            'export_date' => now()->toIso8601String(),
            'rgpd_basis'  => 'Articles 15 et 20 RGPD — Droit d\'accès et portabilité',
            'subject'     => 'contact',
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
                'label' => $contact->companie->label,
                'siren' => $contact->companie->siren ?? null,
            ] : null,
            'orders'      => $customer?->orders->map(fn ($o) => [
                'reference' => $o->ref ?? $o->id,
                'date'      => $o->created_at,
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
    }

    /**
     * Trace une demande d'effacement et notifie l'admin par mail.
     * L'effacement effectif est laissé à un opérateur via les commandes
     * artisan `rgpd:erase-contact` / anonymisation user.
     */
    public function submitEraseRequest(string $subjectType, int $subjectId, string $email, ?string $reason = null): void
    {
        $context = [
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'email'        => $email,
            'reason'       => $reason,
            'submitted_at' => now()->toIso8601String(),
        ];

        activity('rgpd')
            ->withProperties($context)
            ->log('Demande RGPD droit à l\'effacement soumise par l\'utilisateur');

        Log::notice('RGPD erase request', $context);

        $recipient = optional(Factory::query()->first())->mail;
        if (! $recipient) {
            return;
        }

        $subjectLabel = $subjectType === 'user' ? 'Utilisateur interne' : 'Contact B2B';

        Mail::raw(
            "Nouvelle demande RGPD droit à l'effacement.\n\n".
            "Type   : {$subjectLabel}\n".
            "ID     : {$subjectId}\n".
            "Email  : {$email}\n".
            "Motif  : ".($reason ?: '(non précisé)')."\n".
            "Date   : ".$context['submitted_at']."\n\n".
            "Action attendue (à valider manuellement) :\n".
            "  - Pour un contact B2B : php artisan rgpd:erase-contact {$subjectId}\n".
            "  - Pour un utilisateur interne : voir RgpdAnonymizationService::anonymizeUser()\n\n".
            "Délai légal de réponse : 1 mois (art. 12 RGPD).",
            function ($message) use ($recipient, $subjectLabel) {
                $message->to($recipient)
                    ->subject('[RGPD] Demande d\'effacement — '.$subjectLabel);
            }
        );
    }
}
