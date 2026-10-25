<?php

namespace App\Services\Inspection;

use App\Models\Inspection\InspectionPlan;
use App\Models\Products\Products;
use App\Services\Files\FileRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Articles qui attendent un plan de contrôle : onglet « À bullé ».
 *
 * Une ligne article d'une commande client ouverte, pas encore livrée, dont
 * l'article exige un plan (son réglage s'il est forcé, sinon celui du client),
 * et pour laquelle aucun plan de contrôle — brouillon ou validé — n'existe à
 * cette révision. La révision est l'indice de la ligne de commande (ce qui a
 * été commandé), à défaut celui de l'article.
 *
 * Deux requêtes quel que soit le volume : les lignes, puis les plans principaux
 * des articles retenus dans la GED.
 */
class ControlPlanNeeds
{
    /** Statuts de commande ignorés : arrêtée, annulée. */
    private const EXCLUDED_ORDER_STATUSES = [5, 6];

    /**
     * @return Collection<int, array<string, mixed>> une entrée par (article, révision),
     *         triée par date de besoin la plus proche
     */
    public function all(?string $search = null): Collection
    {
        $rows = $this->lines($search)->get();

        $needs = $rows
            ->groupBy(fn ($row) => $row->product_id . '|' . ($row->revision ?? ''))
            ->map(function (Collection $lines) {
                $first = $lines->first();

                return [
                    'product_id'    => (int) $first->product_id,
                    'product_code'  => $first->product_code,
                    'product_label' => $first->product_label,
                    'revision'      => $first->revision,
                    'needed_at'     => $first->delivery_date,
                    'company'       => $first->company_label,
                    'order_id'      => (int) $first->order_id,
                    'order_code'    => $first->order_code,
                    'orders_count'  => $lines->pluck('order_id')->unique()->count(),
                ];
            })
            ->values();

        return $this->withPlanFiles($needs);
    }

    public function count(): int
    {
        return $this->lines()->get()
            ->map(fn ($row) => $row->product_id . '|' . ($row->revision ?? ''))
            ->unique()
            ->count();
    }

    private function lines(?string $search = null)
    {
        $revision = "COALESCE(NULLIF(ol.ind, ''), NULLIF(p.ind, ''))";

        return DB::table('order_lines as ol')
            ->join('orders as o', 'o.id', '=', 'ol.orders_id')
            ->join('products as p', 'p.id', '=', 'ol.product_id')
            ->join('companies as c', 'c.id', '=', 'o.companies_id')
            ->where('o.type', 1)
            ->whereNotIn('o.statu', self::EXCLUDED_ORDER_STATUSES)
            ->where('ol.delivery_status', '<', 3)
            ->where(fn ($q) => $q->where('ol.line_type', 'article')->orWhereNull('ol.line_type'))
            ->where(fn ($q) => $q->where('p.control_plan_mode', 'always')
                ->orWhere(fn ($q) => $q->whereNull('p.control_plan_mode')->where('c.requires_control_plan', true)))
            ->whereNotExists(fn ($q) => $q->from('inspection_plans as ip')
                ->whereColumn('ip.products_id', 'p.id')
                ->whereIn('ip.status', [InspectionPlan::STATUS_DRAFT, InspectionPlan::STATUS_APPROVED])
                ->whereRaw("COALESCE(ip.revision, '') = COALESCE({$revision}, '')"))
            ->when(filled($search), fn ($q) => $q->where(fn ($q) => $q
                ->where('p.code', 'like', "%{$search}%")
                ->orWhere('p.label', 'like', "%{$search}%")
                ->orWhere('c.label', 'like', "%{$search}%")
                ->orWhere('o.code', 'like', "%{$search}%")))
            ->orderByRaw('ol.delivery_date IS NULL')
            ->orderBy('ol.delivery_date')
            ->orderBy('ol.id')
            ->select([
                'p.id as product_id', 'p.code as product_code', 'p.label as product_label',
                DB::raw("{$revision} as revision"),
                'ol.delivery_date', 'o.id as order_id', 'o.code as order_code', 'c.label as company_label',
            ]);
    }

    /**
     * Ajoute le plan principal de chaque article (rôle « plan » de la GED), avec
     * sa miniature si un navigateur l'a déjà rendue.
     */
    private function withPlanFiles(Collection $needs): Collection
    {
        if ($needs->isEmpty()) {
            return $needs;
        }

        $files = DB::table('fileables as fa')
            ->join('files as f', 'f.id', '=', 'fa.file_id')
            ->where('fa.fileable_type', (new Products())->getMorphClass())
            ->whereIn('fa.fileable_id', $needs->pluck('product_id')->unique())
            ->where('fa.role', FileRole::PLAN)
            ->orderByDesc('fa.is_primary')
            ->orderByDesc('f.id')
            ->get(['fa.fileable_id', 'f.id', 'f.kind', 'f.thumbnail_path', 'f.original_file_name'])
            ->unique('fileable_id')
            ->keyBy('fileable_id');

        return $needs->map(function (array $need) use ($files) {
            $file = $files->get($need['product_id']);
            $need['file'] = $file ? InspectionPlanPresenter::fileSummary($file) : null;

            return $need;
        });
    }
}
