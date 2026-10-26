<?php

namespace App\Notifications;

use App\Models\Times\TimesAbsence;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Demande d'absence : « requested » vers les valideurs (RH, responsable), « approved » /
 * « refused » vers le salarié. Cloche seulement, comme les alertes d'étalonnage.
 */
class AbsenceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const REQUESTED = 'requested';
    public const APPROVED = 'approved';
    public const REFUSED = 'refused';

    public function __construct(private TimesAbsence $absence, private string $event, private ?int $actorId = null)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $absence = $this->absence->loadMissing('User');

        // 'id', 'code' et 'user_id' : clés lues par la cloche et la liste du profil.
        return [
            'id' => $absence->id,
            'code' => __("general_content.absence_notification_{$this->event}_trans_key", [
                'name' => $absence->User?->name ?? '',
                'start' => $this->date($absence->start_date),
                'end' => $this->date($absence->end_date),
            ]),
            'user_id' => $this->actorId,
            'event' => $this->event,
            'employee_id' => $absence->user_id,
        ];
    }

    /**
     * Icône, lien et texte dans la cloche et la liste du profil : une demande mène à
     * l'écran Temps (onglet Absence du valideur), une décision au profil du salarié.
     *
     * @return array{icon: string, route: string, text: string}
     */
    public static function present(array $data): array
    {
        $route = ($data['event'] ?? null) === self::REQUESTED
            ? route('times')
            : route('user.profile', ['id' => $data['employee_id'] ?? 0]);

        return ['icon' => 'fas fa-plane-departure', 'route' => $route, 'text' => $data['code'] ?? ''];
    }

    private function date($value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }
}
