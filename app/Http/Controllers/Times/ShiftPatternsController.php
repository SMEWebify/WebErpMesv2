<?php

namespace App\Http\Controllers\Times;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Times\WorkShiftPattern;
use App\Models\Times\WorkShiftSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Régimes horaires (1×8, 2×8, 3×8...) : les plages travaillées de chaque jour.
 * Ils remplacent la fenêtre 8h-18h codée en dur dans App\Support\WorkingTime.
 */
class ShiftPatternsController extends Controller
{
    public function index()
    {
        return view('times/times-shifts', [
            'ShiftPatterns' => WorkShiftPattern::with('slots')->orderBy('code')->get(),
            'Weekdays' => $this->weekdays(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:work_shift_patterns,code',
            'label' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'comment' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $pattern = WorkShiftPattern::create($validated + ['is_default' => $request->boolean('is_default')]);

        $this->enforceSingleDefault($pattern);

        return redirect()->route('times.shift')->with('success', __('general_content.shift_pattern_created_success_trans_key'));
    }

    public function update(Request $request, int $id)
    {
        $pattern = WorkShiftPattern::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'comment' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $pattern->update($validated + ['is_default' => $request->boolean('is_default')]);

        $this->enforceSingleDefault($pattern);

        return redirect()->route('times.shift')->with('success', __('general_content.shift_pattern_updated_success_trans_key'));
    }

    /**
     * Ajoute une plage à un jour. Une plage dont l'heure de fin est antérieure
     * ou égale à l'heure de début franchit minuit : c'est le poste de nuit.
     */
    public function storeSlot(Request $request, int $id)
    {
        $pattern = WorkShiftPattern::findOrFail($id);

        $validated = $request->validate([
            'weekday' => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'label' => 'nullable|string|max:255',
            'apply_to_week' => 'nullable|boolean',
        ]);

        // Saisir une équipe jour par jour serait pénible : par défaut on l'ajoute
        // sur toute la semaine ouvrée d'un coup.
        $weekdays = $request->boolean('apply_to_week') ? [1, 2, 3, 4, 5] : [$validated['weekday']];

        foreach ($weekdays as $weekday) {
            $pattern->slots()->create([
                'weekday' => $weekday,
                'start_time' => $validated['start_time'] . ':00',
                'end_time' => $validated['end_time'] . ':00',
                'label' => $validated['label'] ?? null,
            ]);
        }

        WorkShiftPattern::forgetDefaultCache();

        return redirect()->route('times.shift')->with('success', __('general_content.shift_slot_created_success_trans_key'));
    }

    public function destroySlot(int $id, int $slotId)
    {
        WorkShiftSlot::where('work_shift_pattern_id', $id)->where('id', $slotId)->delete();

        WorkShiftPattern::forgetDefaultCache();

        return redirect()->route('times.shift')->with('success', __('general_content.shift_slot_deleted_success_trans_key'));
    }

    /** Un seul régime par défaut : celui qui vient d'être coché démet les autres. */
    private function enforceSingleDefault(WorkShiftPattern $pattern): void
    {
        DB::transaction(function () use ($pattern) {
            if ($pattern->is_default) {
                WorkShiftPattern::where('id', '!=', $pattern->id)->update(['is_default' => false]);
            }
        });

        WorkShiftPattern::forgetDefaultCache();
    }

    /**
     * Jours de la semaine localisés (ISO : 1 = lundi ... 7 = dimanche), via
     * Carbon plutôt que de nouvelles clés de traduction — la convention du
     * projet pour les dates affichées.
     *
     * @return array<int, string>
     */
    private function weekdays(): array
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return collect(range(0, 6))
            ->mapWithKeys(fn (int $offset) => [
                $offset + 1 => $monday->copy()->addDays($offset)->locale(app()->getLocale())->isoFormat('dddd'),
            ])
            ->all();
    }
}
