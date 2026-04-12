<?php

namespace App\Http\Controllers;

use App\Models\User;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\UserAutoEmailReport;
use App\Models\UserExpenseReport;
use App\Models\Times\TimesAbsence;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateSettingNotificationRequest;

class UsersController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function List()
    {
        $Users = User::orderBy('id')->paginate(10);
        return view('users', [
            'Users' => $Users
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function profile()
    {
        $userId    = Auth::user()->id;
        $UserProfil = User::find($userId);
        $TimesAbsences = TimesAbsence::where('user_id', $userId)->get();
        $ExpenseReports = UserExpenseReport::where('user_id', $userId)->get();
        $agent = new Agent();
        $data = [
            'ipAddress'      => request()->ip(),
            'browser'        => $agent->browser(),
            'browserVersion' => $agent->version($agent->browser()),
            'platform'       => $agent->platform(),
            'platformVersion'=> $agent->version($agent->platform()),
            'device'         => $agent->device(),
            'isDesktop'      => $agent->isDesktop(),
            'isPhone'        => $agent->isPhone(),
            'language'       => request()->getPreferredLanguage(),
        ];

        // --- React: UserProfilePage initial data ---
        $profileFields = [
            'name', 'email',
            'personnal_phone_number', 'born_date', 'desc', 'nationality',
            'gender', 'marital_status', 'ssn_num', 'nic_num',
            'driving_license', 'driving_license_exp_date',
            'address1', 'address2', 'city', 'country',
            'province', 'postal_code', 'home_phone', 'mobile_phone',
            'private_email', 'custom1', 'custom2', 'custom3', 'custom4',
        ];
        $profileInitial = $UserProfil->only($profileFields);

        $profileEndpoints = [
            'updateAccount'     => route('profile.json.account.update'),
            'updateInformation' => route('profile.json.information.update'),
        ];

        // --- React: NotificationLinePage initial data ---
        $notifications = [];
        foreach ($UserProfil->unreadNotifications as $n) {
            $id    = $n->data['id'];
            $icon  = 'fas fa-bell';
            $route = route('quotes.show', ['id' => $id]);

            if ($n->type === 'App\Notifications\QuoteNotification')         { $icon = 'fas fa-calculator';   $route = route('quotes.show',  ['id' => $id]); }
            if ($n->type === 'App\Notifications\OrderNotification')         { $icon = 'fas fa-shopping-cart'; $route = route('orders.show',  ['id' => $id]); }
            if ($n->type === 'App\Notifications\CompanieNotification')      { $icon = 'far fa-building';      $route = route('companies.show', ['id' => $id]); }
            if ($n->type === 'App\Notifications\NonConformityNotification')              { $icon = 'fas fa-exclamation';   $route = route('quality.nonConformitie'); }
            if ($n->type === 'App\Notifications\ReturnNotification')                    { $icon = 'fas fa-undo';          $route = route('returns.show', ['id' => $id]); }
            if ($n->type === 'App\Notifications\InspectionDocumentApprovalNotification') { $icon = 'fas fa-file-alt';     $route = route('quality.inspection.projects'); }

            $originUser = User::find($n->data['user_id'] ?? null);
            $notifications[] = [
                'id'    => $n->id,
                'icon'  => $icon,
                'text'  => ($n->data['code'] ?? '') . ' created by ' . ($originUser?->name ?? 'System'),
                'route' => $route,
                'time'  => $n->created_at->diffForHumans(),
            ];
        }

        $notificationEndpoints = [
            'readOne' => route('profile.json.notification.read', ['id' => '__ID__']),
            'readAll' => route('profile.json.notification.read-all'),
            'history' => route('profile.json.notification.history'),
        ];

        // --- React: UserAutoEmailReportsPage initial data ---
        $reportTypes = [
            UserAutoEmailReport::REPORT_OVERDUE_ORDERS  => __('general_content.overdue_orders_report_trans_key'),
            UserAutoEmailReport::REPORT_TOMORROW_ORDERS => __('general_content.tomorrow_orders_report_trans_key'),
            UserAutoEmailReport::REPORT_LOW_STOCK       => __('general_content.low_stock_report_trans_key'),
        ];

        $reportsData = [];
        foreach (array_keys($reportTypes) as $type) {
            $reportsData[$type] = ['enabled' => false, 'send_time' => '08:00'];
        }
        $existingReports = UserAutoEmailReport::where('user_id', $userId)->get()->keyBy('report_type');
        foreach ($reportsData as $type => $default) {
            if ($existingReports->has($type)) {
                $r = $existingReports->get($type);
                $reportsData[$type] = ['enabled' => (bool) $r->enabled, 'send_time' => $r->send_time];
            }
        }

        $autoEmailEndpoints = [
            'save' => route('profile.json.auto-email-reports.save'),
        ];

        return view('profile', [
            'UserProfil'             => $UserProfil,
            'TimesAbsences'          => $TimesAbsences,
            'ExpenseReports'         => $ExpenseReports,
            'data'                   => $data,
            'profileInitial'         => $profileInitial,
            'profileEndpoints'       => $profileEndpoints,
            'notificationsInitial'   => $notifications,
            'notificationEndpoints'  => $notificationEndpoints,
            'reportsInitial'         => $reportsData,
            'reportTypes'            => $reportTypes,
            'autoEmailEndpoints'     => $autoEmailEndpoints,
        ]);
    }

    public function settingNotification(UpdateSettingNotificationRequest $request)
    {
        $settingNotification = User::find(Auth::user()->id);
        $columns = [
            'companies_notification', 'companies_email_notification',
            'users_notification',     'users_email_notification',
            'quotes_notification',    'quotes_email_notification',
            'orders_notification',    'orders_email_notification',
            'non_conformity_notification', 'non_conformity_email_notification',
            'return_notification',    'return_email_notification',
            'pre_order_notification', 'pre_order_email_notification',
        ];

        foreach ($columns as $col) {
            $settingNotification->$col = $request->boolean($col) ? 1 : 0;
        }

        $settingNotification->save();

        return redirect()->to(route('user.profile', ['id' => Auth::user()->id]) . '#History')->with('success', 'Successfully update notification settings.');
    }
}
