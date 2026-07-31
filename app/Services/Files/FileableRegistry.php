<?php

namespace App\Services\Files;

use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\Quality\QualityNonConformity;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Model;

/**
 * Whitelist of the entities a file can be attached to.
 *
 * The front-end sends a short alias rather than a class name, so a crafted
 * request cannot make the controller resolve an arbitrary model.
 */
class FileableRegistry
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        'company' => Companies::class,
        'opportunity' => Opportunities::class,
        'quote' => Quotes::class,
        'order' => Orders::class,
        'delivery' => Deliverys::class,
        'invoice' => Invoices::class,
        'credit-note' => CreditNotes::class,
        'product' => Products::class,
        'purchase' => Purchases::class,
        'purchase-receipt' => PurchaseReceipt::class,
        'stock-move' => StockMove::class,
        'non-conformity' => QualityNonConformity::class,
    ];

    /**
     * @return array<int, string>
     */
    public static function aliases(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * Resolve an alias to its model class.
     *
     * @return class-string<Model>|null
     */
    public static function classFor(string $alias): ?string
    {
        return self::MAP[$alias] ?? null;
    }

    /**
     * Reverse lookup, used when rendering a Blade mount from a model instance.
     */
    public static function aliasFor(Model|string $model): ?string
    {
        $class = $model instanceof Model ? $model::class : $model;

        $alias = array_search($class, self::MAP, true);

        return $alias === false ? null : $alias;
    }

    /**
     * Find the entity behind an alias/id pair.
     */
    public static function find(string $alias, int|string $id): ?Model
    {
        $class = self::classFor($alias);

        if ($class === null) {
            return null;
        }

        return $class::find($id);
    }
}
