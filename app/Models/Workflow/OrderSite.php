<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderSite extends Model
{
    use HasFactory;

    public function implantations()
    {
        return $this->hasMany(OrderSiteImplantation::class);
    }
}
