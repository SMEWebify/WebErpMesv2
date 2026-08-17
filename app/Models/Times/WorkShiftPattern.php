<?php

namespace App\Models\Times;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

/**
 * Modèle d'équipes : les plages travaillées de chaque jour de la semaine.
 *
 * 1×8  = une plage par jour ouvré (06h00-14h00)
 * 2×8  = deux plages (06h00-14h00 puis 14h00-22h00)
 * 3×8  = trois plages, la dernière franchissant minuit (22h00-06h00)
 */
class WorkShiftPattern extends Model
{
    use HasFactory, SoftDeletes;

    public const CACHE_KEY_DEFAULT = 'work_shift_default_pattern';

    protected $fillable = ['code', 'label', 'is_default', 'color', 'comment'];

    protected $casts = ['is_default' => 'boolean'];

    /**
     * Seul l'id du régime par défaut est mis en cache : les plages sont relues à
     * chaque appel. On invalide sur toute écriture, pas seulement depuis le
     * contrôleur, pour qu'un seeder ou un import ne laisse pas un cache périmé.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::forgetDefaultCache());
        static::deleted(fn () => self::forgetDefaultCache());
    }

    public function slots()
    {
        return $this->hasMany(WorkShiftSlot::class, 'work_shift_pattern_id')
                    ->orderBy('weekday')
                    ->orderBy('start_time');
    }

    /**
     * Régime appliqué par défaut. Null tant qu'aucun n'est déclaré : les
     * horaires historiques (WorkingTime::WORK_START/WORK_END) restent en vigueur.
     */
    public static function defaultPattern(): ?self
    {
        $id = Cache::rememberForever(self::CACHE_KEY_DEFAULT, fn () => self::where('is_default', true)->value('id') ?? 0);

        return $id ? self::with('slots')->find($id) : null;
    }

    public static function forgetDefaultCache(): void
    {
        Cache::forget(self::CACHE_KEY_DEFAULT);
    }

    /** Plages rattachées à un jour ISO-8601 (1 = lundi ... 7 = dimanche). */
    public function slotsForWeekday(int $weekday)
    {
        return $this->slots->where('weekday', $weekday);
    }

    /** Heures travaillées ce jour-là — 0 si le jour n'est pas ouvré. */
    public function hoursForDate(Carbon $date): float
    {
        return round(
            $this->slotsForWeekday($date->dayOfWeekIso)->sum(fn (WorkShiftSlot $slot) => $slot->durationHours()),
            3
        );
    }

    public function weeklyHours(): float
    {
        return round($this->slots->sum(fn (WorkShiftSlot $slot) => $slot->durationHours()), 3);
    }

    /**
     * L'instant tombe-t-il dans une plage travaillée ?
     *
     * On teste aussi les plages de la veille : un poste de nuit rattaché au
     * lundi couvre le mardi jusqu'à 06h00.
     */
    public function coversInstant(Carbon $moment): bool
    {
        $today = $moment->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        foreach ($this->slotsForWeekday($today->dayOfWeekIso) as $slot) {
            if ($slot->coversInstant($today, $moment)) {
                return true;
            }
        }

        foreach ($this->slotsForWeekday($yesterday->dayOfWeekIso) as $slot) {
            if ($slot->crossesMidnight() && $slot->coversInstant($yesterday, $moment)) {
                return true;
            }
        }

        return false;
    }
}
