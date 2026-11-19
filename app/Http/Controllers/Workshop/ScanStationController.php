<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Production\ScanCode;
use App\Services\Production\ScanStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Écran de scan atelier (docs/pointage-scan.md) : page plein écran et action
 * d'un scan. Même accès que l'écran tâche de l'atelier (groupe workshop).
 */
class ScanStationController extends Controller
{
    public function index()
    {
        return view('workshop/workshop-scan', [
            'mode' => ScanStation::mode(),
            'trans' => __('scan_station.screen'),
            'modeLabel' => __('scan_station.modes.' . ScanStation::mode()),
            'badgeRequired' => ScanStation::isKiosk(Auth::user()),
            'badgeIdleMinutes' => ScanStation::BADGE_IDLE_MINUTES,
        ]);
    }

    public function scan(Request $request, ScanStation $station): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:200',
            'badge' => 'nullable|string|max:80',
            'good_qty' => 'nullable|integer|min:0|max:1000000',
            'bad_qty' => 'nullable|integer|min:0|max:1000000',
        ]);
        $code = trim($validated['code']);

        // Badge scanné : il désigne l'opérateur des scans suivants, rien n'est écrit.
        $scannedBadge = ScanCode::parseBadge($code);
        if ($scannedBadge !== null) {
            $operator = $this->badgeUser($scannedBadge);

            return $operator
                ? $this->reply(ScanStation::BADGE, $code, operator: $operator)
                : $this->reply(ScanStation::BADGE_UNKNOWN, $code, status: 404);
        }

        // Badge actif du poste : renvoyé par l'écran avec chaque scan, relu ici.
        $operator = null;
        if (filled($validated['badge'] ?? null)) {
            $operator = $this->badgeUser(ScanCode::normalizeBadge($validated['badge']));
            if (! $operator) {
                return $this->reply(ScanStation::BADGE_UNKNOWN, $code, status: 404);
            }
        } elseif (ScanStation::isKiosk(Auth::user())) {
            // Compte de poste partagé : sans badge, le temps irait au compte du poste.
            return $this->reply(ScanStation::BADGE_REQUIRED, $code);
        }

        $quantities = $request->has('good_qty') || $request->has('bad_qty')
            ? ['good' => (int) ($validated['good_qty'] ?? 0), 'bad' => (int) ($validated['bad_qty'] ?? 0)]
            : null;

        $outcome = $station->scan($code, $operator?->id ?? Auth::id(), $quantities);

        return $this->reply(
            $outcome['result'],
            $code,
            task: $outcome['task'],
            message: $outcome['reason'] ?? null,
            operator: $operator,
            status: $outcome['result'] === ScanStation::NOT_FOUND ? 404 : 200,
        );
    }

    private function badgeUser(string $badge): ?User
    {
        return $badge === '' ? null : User::query()->where('badge_code', $badge)->first();
    }

    private function reply(string $result, string $code, $task = null, ?string $message = null, ?User $operator = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'result' => $result,
            'message' => $message ?? __('scan_station.results.' . $result, ['name' => $operator?->name]),
            'code' => $code,
            'task' => $task ? ScanStation::summary($task) : null,
            'operator' => $operator ? ['name' => $operator->name, 'badge' => $operator->badge_code] : null,
            'url' => $task ? route('workshop.task.statu.id', ['id' => $task->getKey()]) : null,
        ], $status);
    }
}
