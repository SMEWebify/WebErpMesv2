<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunitiesActivitiesLogs;

/**
 * Calendrier commercial : activités (appels, emails, actions) et événements (RDV, visites)
 * des opportunités. Le responsable affiché est celui de la ligne, à défaut le commercial
 * de l'opportunité — les lignes saisies avant l'ajout du champ restent ainsi rattachées.
 */
class OpportunityCalendarController extends Controller
{
    private const ACTIVITY_TYPES = [
        1 => ['activity_maketing_trans_key', '#17a2b8'],
        2 => ['email_send_trans_key', '#ffc107'],
        3 => ['pre_sakes_aactivity_trans_key', '#007bff'],
        4 => ['sales_activity_trans_key', '#28a745'],
        5 => ['sales_telephone_call_trans_key', '#dc3545'],
    ];

    private const EVENT_TYPES = [
        1 => ['activity_maketing_trans_key', '#6f42c1'],
        2 => ['internal_meeting_trans_key', '#6c757d'],
        3 => ['onsite_visite_trans_key', '#fd7e14'],
        4 => ['sales_meeting_trans_key', '#20c997'],
    ];

    private const ACTIVITY_CLOSED = 3;

    private const RELATIONS = [
        'user:id,name',
        'opportunity:id,label,user_id,companies_id',
        'opportunity.companie:id,label',
        'opportunity.UserManagement:id,name',
    ];

    public function index()
    {
        return view('workflow/opportunities-calendar', [
            'users'          => User::select('id', 'name')->orderBy('name')->get(),
            'activityTypes'  => $this->legend(self::ACTIVITY_TYPES),
            'eventTypes'     => $this->legend(self::EVENT_TYPES),
        ]);
    }

    public function events(Request $request)
    {
        $validated = $request->validate([
            'start'   => 'nullable|date',
            'end'     => 'nullable|date',
            'user_id' => 'nullable|integer',
            'kind'    => 'nullable|in:all,activities,events',
        ]);

        $start  = isset($validated['start']) ? Carbon::parse($validated['start'])->toDateString() : null;
        $end    = isset($validated['end']) ? Carbon::parse($validated['end'])->toDateString() : null;
        $userId = $validated['user_id'] ?? null;
        $kind   = $validated['kind'] ?? 'all';

        $items = collect();

        if ($kind !== 'events') {
            $items = $items->concat($this->activities($start, $end, $userId));
        }
        if ($kind !== 'activities') {
            $items = $items->concat($this->eventsLogs($start, $end, $userId));
        }

        return response()->json($items->values());
    }

    private function activities(?string $start, ?string $end, ?int $userId)
    {
        $query = OpportunitiesActivitiesLogs::query()
            ->with(self::RELATIONS)
            ->whereNotNull('due_date')
            ->when($start, fn ($q) => $q->where('due_date', '>=', $start))
            ->when($end, fn ($q) => $q->where('due_date', '<', $end));

        $this->filterByUser($query, $userId);

        return $query->get()->map(function ($activity) {
            [$typeKey, $color] = self::ACTIVITY_TYPES[$activity->type] ?? self::ACTIVITY_TYPES[4];
            $closed = (int) $activity->statu === self::ACTIVITY_CLOSED;

            return [
                'id'              => 'activity-' . $activity->id,
                'title'           => $this->title($activity),
                'start'           => Carbon::parse($activity->due_date)->toDateString(),
                'allDay'          => true,
                'url'             => route('opportunities.show', $activity->opportunities_id) . '#Activities',
                'backgroundColor' => $closed ? '#adb5bd' : $color,
                'borderColor'     => $color,
                'classNames'      => $closed ? ['fc-event-closed'] : [],
                'extendedProps'   => [
                    'kind'    => 'activity',
                    'type'    => __('general_content.' . $typeKey),
                    'user'    => $this->responsible($activity)?->name,
                    'company' => $activity->opportunity?->companie?->label,
                    'comment' => $activity->comment,
                ],
            ];
        });
    }

    private function eventsLogs(?string $start, ?string $end, ?int $userId)
    {
        $query = OpportunitiesEventsLogs::query()
            ->with(self::RELATIONS)
            ->whereNotNull('start_date')
            // chevauchement de la plage affichée : début avant la fin de vue, fin après son début
            ->when($end, fn ($q) => $q->where('start_date', '<', $end))
            ->when($start, fn ($q) => $q->whereRaw('COALESCE(end_date, start_date) >= ?', [$start]));

        $this->filterByUser($query, $userId);

        return $query->get()->map(function ($event) {
            [$typeKey, $color] = self::EVENT_TYPES[$event->type] ?? self::EVENT_TYPES[4];

            return [
                'id'              => 'event-' . $event->id,
                'title'           => $this->title($event),
                'start'           => Carbon::parse($event->start_date)->toDateString(),
                // FullCalendar traite la fin d'un événement "toute la journée" comme exclusive
                'end'             => $event->end_date
                    ? Carbon::parse($event->end_date)->addDay()->toDateString()
                    : null,
                'allDay'          => true,
                'url'             => route('opportunities.show', $event->opportunities_id) . '#Events',
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'kind'    => 'event',
                    'type'    => __('general_content.' . $typeKey),
                    'user'    => $this->responsible($event)?->name,
                    'company' => $event->opportunity?->companie?->label,
                    'comment' => $event->comment,
                ],
            ];
        });
    }

    private function filterByUser($query, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere(function ($q) use ($userId) {
                  $q->whereNull('user_id')
                    ->whereHas('opportunity', fn ($o) => $o->where('user_id', $userId));
              });
        });
    }

    private function responsible($line): ?User
    {
        return $line->user ?? $line->opportunity?->UserManagement;
    }

    private function title($line): string
    {
        $company = $line->opportunity?->companie?->label;

        return $company ? $line->label . ' — ' . $company : $line->label;
    }

    private function legend(array $types): array
    {
        return collect($types)
            ->map(fn ($t) => ['label' => __('general_content.' . $t[0]), 'color' => $t[1]])
            ->values()
            ->all();
    }
}
