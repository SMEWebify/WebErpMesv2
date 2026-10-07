<?php

namespace App\Services\Files;

use App\Models\Admin\UserEmploymentContracts;
use App\Models\Companies\Companies;
use App\Models\Products\Inventory;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
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
        'quote-line' => QuoteLines::class,
        'order' => Orders::class,
        'order-line' => OrderLines::class,
        'delivery' => Deliverys::class,
        'invoice' => Invoices::class,
        'credit-note' => CreditNotes::class,
        'product' => Products::class,
        'purchase' => Purchases::class,
        'purchase-receipt' => PurchaseReceipt::class,
        'stock-move' => StockMove::class,
        'non-conformity' => QualityNonConformity::class,
        // Counting XLSX archived at inventory validation.
        'inventory' => Inventory::class,
        // Employee folder: confidential, see FilePolicy / self::CONFIDENTIAL.
        'user' => User::class,
        'employment-contract' => UserEmploymentContracts::class,
    ];

    /**
     * Aliases whose documents hold personal data. They are never readable by
     * the whole factory: FilePolicy narrows them down to the employee
     * concerned and to HR.
     *
     * @var array<int, string>
     */
    private const CONFIDENTIAL = [
        'user',
        'employment-contract',
    ];

    /**
     * Permission required to list or attach documents on an alias, when the
     * entity screen itself is permission-gated. Without it the GED was a side
     * door: a role kept off the catalogue could still upload to any product
     * through /files/json/store (GHSA-rqxq-q992-xj2h).
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'product' => 'products-menu',
    ];

    public static function permissionFor(string $alias): ?string
    {
        return self::PERMISSIONS[$alias] ?? null;
    }

    /**
     * Does this alias carry personal data?
     */
    public static function isConfidential(string $alias): bool
    {
        return in_array($alias, self::CONFIDENTIAL, true);
    }

    /**
     * Model classes holding personal data.
     *
     * @return array<int, class-string<Model>>
     */
    public static function confidentialClasses(): array
    {
        return array_values(array_map(
            static fn (string $alias) => self::MAP[$alias],
            self::CONFIDENTIAL
        ));
    }

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

        // Les documents d'une trame de devis restent accessibles depuis la trame.
        if (method_exists($class, 'withTemplates')) {
            return $class::withTemplates()->find($id);
        }

        return $class::find($id);
    }
}
