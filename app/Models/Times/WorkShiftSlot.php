<?php

namespace App\Models\Times;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Une plage horaire d'un jour de la semaine dans un modèle d'équipes.
 * Trois plages sur un même jour = 3×8.
 */
class WorkShiftSlot extends Model
{
    use HasFactory;

    protected $fillable = ['work_shift_pattern_id', 'weekday', 'start_time', 'end_time', 'label'];

    protected $casts = ['weekday' => 'integer'];

    public function pattern()
    {
        return $this->belongsTo(WorkShiftPattern::class, 'work_shift_pattern_id');
    }

    /** Poste de nuit : la plage se termine le lendemain (22h00 → 06h00). */
    public function crossesMidnight(): bool
    {
        return $this->endSeconds() <= $this->startSeconds();
    }

    public function durationHours(): float
    {
        $duration = $this->endSeconds() - $this->startSeconds();

        if ($duration <= 0) {
            $duration += 24 * 3600;
        }

        return round($duration / 3600, 3);
    }

    /**
     * La plage, rattachée au jour $day, couvre-t-elle cet instant ?
     * Pour un poste de nuit, la fin appartient au lendemain.
     */
    public function coversInstant(Carbon $day, Carbon $moment): bool
    {
        $start = $day->copy()->startOfDay()->addSeconds($this->startSeconds());
        $end   = $start->copy()->addSeconds((int) round($this->durationHours() * 3600));

        return $moment->greaterThanOrEqualTo($start) && $moment->lessThan($end);
    }

    private function startSeconds(): int
    {
        return $this->toSeconds($this->start_time);
    }

    private function endSeconds(): int
    {
        return $this->toSeconds($this->end_time);
    }

    private function toSeconds($time): int
    {
        [$hours, $minutes, $seconds] = array_pad(explode(':', (string) $time), 3, '0');

        return ((int) $hours) * 3600 + ((int) $minutes) * 60 + (int) $seconds;
    }
}
