<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AccessLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Journal des consultations (activity_log, log_name `access`) : écran filtrable et
 * export CSV. Routes sous la permission audit-access-log (rôle Admin).
 */
class AccessLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index()
    {
        return view('admin.access-log', [
            'enabled' => AccessLogger::enabled(),
            'users'   => User::query()->orderBy('name')->get(['id', 'name']),
            'types'   => collect(array_keys(AccessLogger::SUBJECTS))
                ->mapWithKeys(fn ($type) => [$type => __('audit.subject_' . str_replace('-', '_', $type))]),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $page = $this->query($this->filters($request))
            ->with('causer:id,name')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $labels = $this->subjectLabels($page->getCollection());

        return response()->json([
            'data' => $page->getCollection()->map(fn (Activity $a) => $this->row($a, $labels))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'journal-consultations-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                __('audit.date'), __('audit.user'), __('audit.subject_type'), __('audit.subject_id'),
                __('audit.subject'), __('audit.route'), __('audit.ip'),
            ], ';', escape: '\\');

            $this->query($filters)
                ->with('causer:id,name')
                ->chunkById(1000, function (Collection $chunk) use ($handle) {
                    $labels = $this->subjectLabels($chunk);
                    foreach ($chunk as $activity) {
                        $row = $this->row($activity, $labels);
                        fputcsv($handle, [
                            $row['created_at'], $row['user'], $row['type_label'], $row['subject_id'],
                            $row['subject_label'], $row['route'], $row['ip'],
                        ], ';', escape: '\\');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{user_id: ?int, type: ?string, subject_id: ?int, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'user_id'    => 'nullable|integer',
            'type'       => 'nullable|string|in:' . implode(',', array_keys(AccessLogger::SUBJECTS)),
            'subject_id' => 'nullable|integer',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
        ]);

        return [
            'user_id'    => $validated['user_id'] ?? null,
            'type'       => $validated['type'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'from'       => $validated['from'] ?? null,
            'to'         => $validated['to'] ?? null,
        ];
    }

    private function query(array $filters): Builder
    {
        $query = Activity::query()->where('log_name', AccessLogger::logName());

        if ($filters['user_id']) {
            $query->where('causer_type', (new User())->getMorphClass())->where('causer_id', $filters['user_id']);
        }
        if ($filters['type']) {
            $class = AccessLogger::SUBJECTS[$filters['type']]['model'];
            $query->where('subject_type', (new $class())->getMorphClass());
        }
        if ($filters['subject_id']) {
            $query->where('subject_id', $filters['subject_id']);
        }
        // Bornes en datetime plutôt que whereDate() : l'index sur created_at reste utilisable.
        if ($filters['from']) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if ($filters['to']) {
            $query->where('created_at', '<', Carbon::parse($filters['to'])->addDay()->startOfDay());
        }

        return $query;
    }

    /**
     * Libellé et lien des sujets d'une page : une requête par type présent, jamais par ligne.
     * Les portées globales sont levées (trames de devis, éléments supprimés) : un accès
     * reste lisible même si le document a disparu depuis.
     *
     * @return array<string, array<int, array{label: ?string, url: ?string}>>
     */
    private function subjectLabels(Collection $activities): array
    {
        $labels = [];

        foreach ($activities->groupBy('subject_type') as $subjectType => $group) {
            $type = AccessLogger::typeOf($subjectType);
            if ($type === null) {
                continue;
            }

            $config = AccessLogger::SUBJECTS[$type];
            $columns = array_values(array_unique(array_filter(['id', $config['label'], $type === 'order-line' ? 'orders_id' : null])));

            $config['model']::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $group->pluck('subject_id')->unique()->values())
                ->get($columns)
                ->each(function ($model) use (&$labels, $subjectType, $type, $config) {
                    $labels[$subjectType][$model->id] = [
                        'label' => $model->getAttribute($config['label']),
                        'url'   => $this->subjectUrl($type, $model),
                    ];
                });
        }

        return $labels;
    }

    private function subjectUrl(string $type, $model): ?string
    {
        return match ($type) {
            'opportunity' => route('opportunities.show', ['id' => $model->id]),
            'quote'       => route('quotes.show', ['id' => $model->id]),
            'order'       => route('orders.show', ['id' => $model->id]),
            'order-line'  => $model->orders_id ? route('orders.show', ['id' => $model->orders_id]) : null,
            'file'        => route('files.download', ['file' => $model->id]),
            default       => null,
        };
    }

    private function row(Activity $activity, array $labels): array
    {
        $type = AccessLogger::typeOf($activity->subject_type);
        $subject = $labels[$activity->subject_type][$activity->subject_id] ?? null;

        return [
            'id'            => $activity->id,
            'created_at'    => $activity->created_at?->format('d/m/Y H:i:s'),
            'user_id'       => $activity->causer_id,
            'user'          => $activity->causer?->name ?? ('#' . $activity->causer_id),
            'type'          => $type,
            'type_label'    => $type ? __('audit.subject_' . str_replace('-', '_', $type)) : class_basename((string) $activity->subject_type),
            'subject_id'    => $activity->subject_id,
            'subject_label' => $subject['label'] ?? __('audit.subject_missing'),
            'subject_url'   => $subject['url'] ?? null,
            'route'         => $activity->getExtraProperty('route'),
            'ip'            => $activity->getExtraProperty('ip'),
        ];
    }
}
