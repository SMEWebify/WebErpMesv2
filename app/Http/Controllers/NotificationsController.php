<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    /**
     * Get the new notification data for the navbar notification.
     *
     * @param \Illuminate\Http\Request $request
     * @return Array
     */
    public function getNotificationsData(Request $request)
    {
        $notifications = [];
        $user = User::find(Auth::id());
        foreach ($user->unreadNotifications  as $notification) {
            $id = $notification->data['id'];

            if($notification->type == 'App\Notifications\QuoteNotification') {$type = 'fas fa-calculator'; $route = route('quotes.show', ['id' => $id]);}
            if($notification->type == 'App\Notifications\OrderNotification') {$type = 'fas fa-shopping-cart'; $route = route('orders.show', ['id' => $id]);}
            if($notification->type == 'App\Notifications\CompanieNotification') {$type = 'far fa-building'; $route = route('companies.show', ['id' => $id]);}
            if($notification->type == 'App\Notifications\NonConformityNotification') {$type = 'fas fa-exclamation'; $route = route('quality.nonConformitie');}
            

            $code = $notification->data['code'];
            $notificationOriginUser = User::find($notification->data['user_id']);
            $text = ''.$code .' created by '. $notificationOriginUser['name'] .'';

            array_push($notifications,[
                            'icon' => $type,
                            'text' => $text,
                            'route' => $route,
                            'time' => $notification->created_at->diffForHumans(),
                        ]);
        }
    
        // Now, we create the notification dropdown main content.
        $dropdownHtml = '';
        foreach ($notifications as $key => $not) {
            $icon = "<i class='mr-2 {$not['icon']}'></i>";
            $time = "<span class='float-right text-muted text-sm'>{$not['time']}</span>";
            $dropdownHtml .= "<a href='{$not['route']}' class='dropdown-item'>{$icon}{$not['text']}{$time}</a>";
    
            if ($key < count($notifications) - 1) {
                $dropdownHtml .= "<div class='dropdown-divider'></div>";
            }
        }
    
        // Return the new notification data.
        return [
            'label'       => count($notifications),
            'label_color' => 'danger',
            'icon_color'  => 'dark',
            'dropdown'    => $dropdownHtml,
        ];
    }
}
