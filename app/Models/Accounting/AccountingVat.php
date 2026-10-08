<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingAllocation;
use App\Models\Purchases\PurchaseLines;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Class AccountingVat
 *
 * This class represents the VAT (Value Added Tax) accounting model.
 * It extends the base Model class provided by the framework.
 */
class AccountingVat extends Model
{
    use HasFactory; use HasDefaultTrait;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',  'label',  'rate',  'default',
                          'en16931_category', 'exemption_reason_code', 'exemption_reason_text', 'legal_mention'];

    /**
     * Catégorie EN 16931 (UNCL5305) du code de TVA, avec repli rétrocompatible :
     * tant qu'aucune catégorie n'est renseignée, on reconduit l'ancienne règle
     * (S si taux > 0, Z sinon). Source unique utilisée par le Factur-X.
     */
    public function resolvedEn16931Category(): string
    {
        return $this->en16931_category ?: ((float) $this->rate > 0 ? 'S' : 'Z');
    }


    /**
     * Define a one-to-many relationship with the AccountingAllocation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function VAT()
    {
        return $this->hasMany(AccountingAllocation::class);
    }

    /**
     * Get the quote lines associated with the accounting VAT.
     *
     * This function defines a one-to-many relationship between the AccountingVat model
     * and the QuoteLines model. It indicates that each instance of AccountingVat can have
     * multiple associated QuoteLines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    /**
     * Get the order lines associated with the accounting VAT.
     *
     * This function defines a one-to-many relationship between the AccountingVat model
     * and the OrderLines model. It indicates that each instance of AccountingVat can have
     * multiple associated OrderLines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function OrderLines()
    {
        return $this->hasMany(OrderLines::class);
    }

    /**
     * Get the purchase lines associated with the accounting VAT.
     *
     * This function defines a one-to-many relationship between the AccountingVat model
     * and the PurchaseLines model. It indicates that each AccountingVat instance can have
     * multiple associated PurchaseLines instances.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function PurchaseLines()
    {
        return $this->hasMany(PurchaseLines::class);
    }
}
