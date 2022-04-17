<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        return view('profile', [
            'UserProfil' => $UserProfil
        ]);
    }
}
