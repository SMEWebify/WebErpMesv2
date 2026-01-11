<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'label' => $this->label,
            'siren' => $this->siren,
            'naf_code' => $this->naf_code,
            'intra_community_vat' => $this->intra_community_vat,
            'website' => $this->website,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'created_at' => $this->created_at->format('d/m/Y'),
            'contacts' => $this->Contacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'first_name' => $contact->first_name,
                    'name' => $contact->name,
                    'mail' => $contact->mail,
                    'number' => $contact->number,
                ];
            }),
            'addresses' => $this->Addresses->map(function ($address) {
                return [
                    'id' => $address->id,
                    'adress' => $address->adress,
                    'zipcode' => $address->zipcode,
                    'city' => $address->city,
                    'province' => $address->province,
                    'country' => $address->country,
                ];
            }),
        ];
    }
}
