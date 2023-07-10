<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateSettingNotificationRequest;

class UsersController extends Controller
{
    /**
     * @return View
     */
    public function List()
    {
        $Users = User::orderBy('id')->paginate(10);
        return view('users', [
            'Users' => $Users
        ]);
    }

    /**
     * @return View
     */
    public function profile()
    {
        $UserProfil = User::find(Auth::user()->id);
        $TimesAbsences = TimesAbsence::where('user_id', Auth::user()->id)->get();
        return view('profile', [
            'UserProfil' => $UserProfil,
            'TimesAbsences' => $TimesAbsences
        ]);
    }

    public function settingNotification(UpdateSettingNotificationRequest $request)
    {
        $settingNotification = User::find(Auth::user()->id);
        if($request->companies_notification) $settingNotification->companies_notification=1;
        else $settingNotification->companies_notification = 0;

        if($request->users_notification) $settingNotification->users_notification = 1;
        else $settingNotification->users_notification = 0;

        if($request->quotes_notification) $settingNotification->quotes_notification=1;
        else $settingNotification->quotes_notification = 0;

        if($request->orders_notification) $settingNotification->orders_notification=1;
        else $settingNotification->orders_notification = 0;

        $settingNotification->save();

        return redirect()->route('user.profile', ['id' => Auth::user()->id])->with('success', 'Successfully update notification settings.');
    }
}
