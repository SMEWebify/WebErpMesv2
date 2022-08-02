<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdresseResource extends JsonResource
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
            'adress' => $this->adress,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
            'country' => $this->country,
            'number' => $this->number,
            'mail' => $this->mail,
        ];
    }
}
