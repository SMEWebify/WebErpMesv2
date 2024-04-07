<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\AdresseResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\CompanieResource;
use App\Http\Resources\QuoteLinesResource;
use App\Http\Resources\PaymentMethodResource;
use App\Http\Resources\DeleveryMethodResource;
use App\Http\Resources\PaymentConditionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,     
            'code' => $this->code,
            'label' => $this->label,
            'customer_reference' => $this->customer_reference,
            'companies_id' => new CompanieResource($this->companie),
            'companies_contacts_id' => new ContactResource($this->contact),
            'companies_addresses_id' => new AdresseResource($this->adresse),
            'accounting_payment_conditions_id' => new PaymentConditionResource($this->payment_condition),
            'accounting_payment_methods_id' => new PaymentMethodResource($this->payment_method),
            'accounting_deliveries_id' => new DeleveryMethodResource($this->delevery_method),
            'validity_date' => $this->validity_date,
            'statu' => $this->statu,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'quote_lines' => QuoteLinesResource::collection($this->QuoteLines),
        ];

        //return parent::toArray($request);
    }
}
