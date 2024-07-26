<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $notifications = [];
        $user = User::find(Auth::id());
        foreach ($user->unreadNotifications   as $notification) {
            if($notification->type == 'App\Notifications\QuoteNotification') $type = 'fas fa-calculator';
            if($notification->type == 'App\Notifications\OrderNotification') $type = 'fas fa-shopping-cart';
            if($notification->type == 'App\Notifications\CompanieNotification') $type = 'far fa-building';
            if($notification->type == 'App\Notifications\NonConformityNotification') $type = 'fas fa-exclamation';
            
            $NotificaitonId = $notification->id;
            $id = $notification->data['id'];
            $code = $notification->data['code'];
            $notificationOriginUser = User::find($notification->data['user_id']);
            $text = ''.$code .' created by '. $notificationOriginUser['name'] .'';
            $route = route('quotes.show', ['id' => $id]);

            array_push($notifications,[
                            'id' => $NotificaitonId,
                            'icon' => $type,
                            'text' => $text,
                            'route' => $route,
                            'time' => $notification->created_at->diffForHumans(),
                        ]);
        }

        return view('livewire.notification-line', [
            'Notificationlist' => $notifications,
            'user'=> $user
        ]);
    }
    public function Read($id){
        // Update notification
        $Notification = DatabaseNotification::find($id);
        $Notification->read_at = now();
        $Notification->save();
        session()->flash('success','Line Updated Successfully');
    }


    public function allRead(){
        // Update all notification to read
        $user = User::find(Auth::id());
        $user->unreadNotifications->markAsRead();
        session()->flash('success','Lines Updated Successfully');
    }
}
