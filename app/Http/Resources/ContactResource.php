<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'civility' => $this->civility,
            'first_name' => $this->first_name,
            'name' => $this->name,
            'number' => $this->number,
            'mobile' => $this->mobile,
            'mail' => $this->mail,
        ];
    }
}
