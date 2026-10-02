<?php

namespace App\Services\Stock;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Products\Products;
use App\Models\Products\StockReservation;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Services\PurchaseOrderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Réapprovisionnement : propose les quantités à acheter pour les articles achetés,
 * puis crée une commande d'achat BROUILLON (statu 1) par fournisseur. Rien n'est envoyé :
 * l'acheteur relit et passe la commande lui-même.
 *
 * Besoin = Σ seuils mini des emplacements + besoins réservés par les tâches (actifs)
 *        − stock physique − déjà en commande (lignes non reçues des achats en cours).
 * Quantité proposée = max(besoin, quantité éco mini de l'article), arrondie à l'unité.
 */
class ReorderService
{
    /** Statuts d'achat dont les lignes non reçues comptent comme « en commande ». */
    private const OPEN_PURCHASE_STATUSES = [1, 2, 3];

    public function __construct(private PurchaseOrderService $purchaseOrderService)
    {
    }

    /**
     * @param int[]|null $productIds restreint aux articles donnés (proposés même sans manque)
     * @param bool       $onlyShort  ne garder que les articles à recommander
     */
    public function suggestions(?array $productIds = null, bool $onlyShort = true): Collection
    {
        $query = Products::with(['Stock_location_product.StockLocation', 'preferredSuppliers:id,code,label', 'QuantityPrice'])
            ->where('purchased', 1);
        if ($productIds !== null) {
            $query->whereIn('id', $productIds);
        } else {
            $query->where(function ($q) {
                $q->whereHas('Stock_location_product', fn ($s) => $s->where('mini_qty', '>', 0))
                  ->orWhereIn('id', StockReservation::where('status', StockReservation::STATUS_ACTIVE)->select('products_id'));
            });
        }
        $products = $query->orderBy('code')->get();
        if ($products->isEmpty()) {
            return collect();
        }
        $ids = $products->pluck('id');

        $reserved = StockReservation::where('status', StockReservation::STATUS_ACTIVE)
            ->whereIn('products_id', $ids)
            ->groupBy('products_id')
            ->selectRaw('products_id, SUM(qty_requested) as qty')
            ->pluck('qty', 'products_id');

        // purchase_lines.product_id est une colonne texte : on compare sur la chaîne
        $onOrder = PurchaseLines::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchases_id')
            ->whereIn('purchases.statu', self::OPEN_PURCHASE_STATUSES)
            ->whereIn('purchase_lines.product_id', $ids->map(fn ($id) => (string) $id))
            ->whereColumn('purchase_lines.receipt_qty', '<', 'purchase_lines.qty')
            ->groupBy('purchase_lines.product_id')
            ->selectRaw('purchase_lines.product_id, SUM(purchase_lines.qty - purchase_lines.receipt_qty) as qty')
            ->pluck('qty', 'product_id');

        return $products->map(function (Products $p) use ($reserved, $onOrder, $productIds) {
            $stock    = (float) $p->getTotalStockMove();
            $mini     = (float) $p->Stock_location_product->sum('mini_qty');
            $needs    = (float) ($reserved[$p->id] ?? 0);
            $ordered  = (float) ($onOrder[(string) $p->id] ?? 0);
            $shortfall = $mini + $needs - $stock - $ordered;
            $suggested = $shortfall > 0 ? (int) ceil(max($shortfall, (float) $p->qty_eco_min)) : 0;

            $supplier = $p->preferredSuppliers->first();
            // l'emplacement le plus en manque recevra la marchandise
            $location = $p->Stock_location_product
                ->sortBy(fn ($l) => $l->getCurrentStockMove() - $l->mini_qty)
                ->first();

            return [
                'product_id'      => $p->id,
                'code'            => $p->code,
                'label'           => $p->label,
                'product_url'     => route('products.show', ['id' => $p->id]),
                'methods_units_id'=> $p->methods_units_id,
                'stock'           => round($stock, 3),
                'mini'            => round($mini, 3),
                'needs'           => round($needs, 3),
                'on_order'        => round($ordered, 3),
                'shortfall'       => round(max(0, $shortfall), 3),
                'suggested_qty'   => $suggested ?: ($productIds !== null ? max(1, (int) ceil((float) $p->qty_eco_min)) : 0),
                'supplier_id'     => $supplier?->id,
                'preferred_suppliers' => $p->preferredSuppliers->map(fn ($c) => ['id' => $c->id, 'label' => trim($c->code . ' - ' . $c->label)])->values(),
                'purchased_price' => (float) $p->purchased_price,
                'price_breaks'    => $p->QuantityPrice->map(fn ($qp) => [
                    'companies_id' => $qp->companies_id,
                    'min_qty'      => (float) $qp->min_qty,
                    'max_qty'      => (float) $qp->max_qty,
                    'price'        => (float) $qp->price,
                ])->values(),
                'stock_locations_id' => $location?->stock_locations_id,
            ];
        })
            ->when($onlyShort, fn ($rows) => $rows->filter(fn ($r) => $r['suggested_qty'] > 0))
            ->values();
    }

    /**
     * Crée une commande d'achat brouillon par fournisseur.
     *
     * @param array<int, array{product_id:int, companies_id:int, qty:float, price:float, stock_locations_id?:int|null}> $lines
     * @return Collection<int, Purchases>
     * @throws RuntimeException si un fournisseur n'a ni contact ni adresse par défaut
     */
    public function createDraftPurchases(array $lines): Collection
    {
        $vat = $this->purchaseOrderService->getAccountingVat();
        if (!$vat) {
            throw new RuntimeException('Aucun taux de TVA par défaut : renseignez-le dans Comptabilité › TVA.');
        }

        $bySupplier = collect($lines)->groupBy('companies_id');

        // contrôle avant écriture : un fournisseur incomplet ne doit pas laisser des commandes à moitié créées
        $defaults = [];
        foreach ($bySupplier->keys() as $companyId) {
            $contact = CompaniesContacts::getDefault(['companies_id' => $companyId]);
            $address = CompaniesAddresses::getDefault(['companies_id' => $companyId]);
            if (!$contact || !$address) {
                $label = Companies::find($companyId)?->label ?? "#{$companyId}";
                throw new RuntimeException("Le fournisseur « {$label} » n'a pas de contact ou d'adresse par défaut.");
            }
            $defaults[$companyId] = [$contact->id, $address->id];
        }

        $products = Products::whereIn('id', collect($lines)->pluck('product_id'))->get()->keyBy('id');

        return DB::transaction(function () use ($bySupplier, $defaults, $products, $vat) {
            $created = collect();
            foreach ($bySupplier as $companyId => $supplierLines) {
                [$contactId, $addressId] = $defaults[$companyId];
                $code = $this->purchaseOrderService->generatePurchaseCode();
                $purchase = $this->purchaseOrderService->createPurchaseOrder($companyId, $code, $code, $contactId, $addressId);
                $purchase->update(['comment' => 'Réapprovisionnement généré le ' . now()->format('d/m/Y')]);

                $ordre = 10;
                foreach ($supplierLines as $line) {
                    $product = $products[$line['product_id']];
                    $qty = (int) ceil($line['qty']);
                    $price = (float) $line['price'];
                    PurchaseLines::create([
                        'purchases_id'              => $purchase->id,
                        'tasks_id'                  => 0,
                        'ordre'                     => $ordre,
                        'code'                      => $product->code,
                        'product_id'                => $product->id,
                        'label'                     => $product->label,
                        'qty'                       => $qty,
                        'selling_price'             => $price,
                        'discount'                  => 0,
                        'unit_price_after_discount' => $price,
                        'total_selling_price'       => $price * $qty,
                        'methods_units_id'          => $product->methods_units_id,
                        'accounting_vats_id'        => $vat->id,
                        'stock_locations_id'        => $line['stock_locations_id'] ?? null,
                    ]);
                    $ordre += 10;
                }
                $created->push($purchase);
            }

            return $created;
        });
    }
}
