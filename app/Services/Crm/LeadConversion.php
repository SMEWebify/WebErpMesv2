<?php

namespace App\Services\Crm;

use App\Events\QuoteCreated;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Companies\Companies;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Quotes;
use App\Notifications\QuoteNotification;
use App\Services\NotificationService;
use Illuminate\Support\Str;

/**
 * Piste → opportunité → devis. Un devis passe toujours par une opportunité : c'est
 * elle qui porte le dossier d'affaire (synthèse, chronologie des documents liés).
 */
class LeadConversion
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function toOpportunity(Leads $lead, ?int $userId): Opportunities
    {
        $lead->update(['statu' => 4]);

        return Opportunities::create([
            'uuid'                   => Str::uuid(),
            'companies_id'           => $lead->companies_id,
            'companies_contacts_id'  => $lead->companies_contacts_id,
            'companies_addresses_id' => $lead->companies_addresses_id,
            'user_id'                => $userId,
            // Une demande entrante est nommée par son objet ; sinon le libellé d'avant.
            'label'                  => $lead->subject ?: '#LEAD' . $lead->id . '#' . $lead->source . '#' . $lead->campaign,
            'leads_id'               => $lead->id,
            'budget'                 => 0,
            'probality'              => 50,
        ]);
    }

    /**
     * @throws \DomainException  paramètre comptable par défaut manquant (message de l'écran)
     */
    public function toQuote(Opportunities $opportunity, ?int $userId): Quotes
    {
        // Conditions et mode de règlement du client d'abord, sinon ceux de l'instance.
        $customer = Companies::find($opportunity->companies_id);
        $defaults = [
            'payment_conditions' => ($customer?->accounting_payment_conditions_id ? AccountingPaymentConditions::find($customer->accounting_payment_conditions_id) : null)
                ?? AccountingPaymentConditions::getDefault(),
            'payment_methods'    => ($customer?->accounting_payment_methods_id ? AccountingPaymentMethod::find($customer->accounting_payment_methods_id) : null)
                ?? AccountingPaymentMethod::getDefault(),
            'deliveries'         => AccountingDelivery::getDefault(),
        ];

        foreach ($defaults as $key => $setting) {
            if (is_null($setting)) {
                throw new \DomainException('No default settings for ' . str_replace('_', ' ', $key));
            }
        }

        $lastQuote = Quotes::latest('id')->first();
        $quoteId = $lastQuote ? $lastQuote->id + 1 : 0;

        $quote = Quotes::create([
            'uuid'                             => Str::uuid(),
            'code'                             => "QT-$quoteId",
            'label'                            => "QT-$quoteId",
            'companies_id'                     => $opportunity->companies_id,
            'companies_contacts_id'            => $opportunity->companies_contacts_id,
            'companies_addresses_id'           => $opportunity->companies_addresses_id,
            'user_id'                          => $userId,
            'opportunities_id'                 => $opportunity->id,
            'accounting_payment_conditions_id' => $defaults['payment_conditions']->id,
            'accounting_payment_methods_id'    => $defaults['payment_methods']->id,
            'accounting_deliveries_id'         => $defaults['deliveries']->id,
        ]);

        $this->notifications->sendNotification(QuoteNotification::class, $quote, 'quotes_notification');

        $opportunity->update(['statu' => 2]);

        event(new QuoteCreated($quote));

        return $quote;
    }
}
