<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserAutoEmailReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class UserProfileApiController extends Controller
{
    public function updateAccount(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        User::find(Auth::id())->fill([
            'name'  => $request->name,
            'email' => $request->email,
        ])->save();

        return response()->json(['success' => true]);
    }

    public function updateInformation(Request $request)
    {
        User::find(Auth::id())->fill($request->only([
            'personnal_phone_number', 'born_date', 'desc', 'nationality',
            'gender', 'marital_status', 'ssn_num', 'nic_num',
            'driving_license', 'driving_license_exp_date',
            'address1', 'address2', 'city', 'country',
            'province', 'postal_code', 'home_phone', 'mobile_phone',
            'private_email', 'custom1', 'custom2', 'custom3', 'custom4',
        ]))->save();

        return response()->json(['success' => true]);
    }

    public function readNotification(string $id)
    {
        DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->firstOrFail()
            ->markAsRead();

        return response()->json(['success' => true]);
    }

    public function readAllNotifications()
    {
        User::find(Auth::id())->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function getNotificationHistory(Request $request): JsonResponse
    {
        $perPage = 20;
        $paginator = DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $notifications = $paginator->getCollection()->map(fn ($n) => $this->formatNotification($n))->values();

        return response()->json([
            'notifications' => $notifications,
            'current_page'  => $paginator->currentPage(),
            'last_page'     => $paginator->lastPage(),
            'has_more'      => $paginator->hasMorePages(),
        ]);
    }

    private function formatNotification(DatabaseNotification $n): array
    {
        $id    = $n->data['id'] ?? null;
        $icon  = 'fas fa-bell';
        $route = url('/');

        if ($n->type === 'App\Notifications\QuoteNotification')         { $icon = 'fas fa-calculator';    $route = $id ? route('quotes.show',   ['id' => $id]) : url('/'); }
        if ($n->type === 'App\Notifications\OrderNotification')         { $icon = 'fas fa-shopping-cart'; $route = $id ? route('orders.show',   ['id' => $id]) : url('/'); }
        if ($n->type === 'App\Notifications\CompanieNotification')      { $icon = 'far fa-building';      $route = $id ? route('companies.show', ['id' => $id]) : url('/'); }
        if ($n->type === 'App\Notifications\NonConformityNotification') { $icon = 'fas fa-exclamation';   $route = route('quality.nonConformitie'); }
        if ($n->type === 'App\Notifications\ReturnNotification')        { $icon = 'fas fa-undo';          $route = $id ? route('returns.show',  ['id' => $id]) : url('/'); }
        if ($n->type === 'App\Notifications\PreOrderNotification')      { $icon = 'fas fa-file-import';   $route = url('/pre-orders'); }
        if ($n->type === 'App\Notifications\LeadNotification')          { $icon = 'fas fa-user-tag';      $route = url('/'); }

        $originUser = $n->data['user_id'] ? User::find($n->data['user_id']) : null;

        return [
            'id'      => $n->id,
            'icon'    => $icon,
            'text'    => ($n->data['code'] ?? '') . ' — ' . ($originUser?->name ?? 'Système'),
            'route'   => $route,
            'time'    => $n->created_at->format('d M Y H:i'),
            'read_at' => $n->read_at?->toISOString(),
        ];
    }

    public function saveAutoEmailReports(Request $request)
    {
        $request->validate([
            'reports'                  => 'required|array',
            'reports.*.send_time'      => 'required|date_format:H:i',
            'reports.*.enabled'        => 'required|boolean',
        ]);

        foreach ($request->reports as $type => $data) {
            if (!in_array($type, UserAutoEmailReport::reportTypes())) {
                continue;
            }
            UserAutoEmailReport::updateOrCreate(
                ['user_id' => Auth::id(), 'report_type' => $type],
                ['send_time' => $data['send_time'], 'enabled' => (bool) $data['enabled']]
            );
        }

        return response()->json(['success' => true]);
    }
}
