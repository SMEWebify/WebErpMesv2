<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnexionController extends Controller
{
    public function LoginForm(){
        return view('login');
    }

    public function LoginConfirm(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user_data = array(
            'email' => request('email'), 
            'password' => request('password')
        );

        if(Auth::attempt($user_data)){
            return redirect('/SuccessLogin');
        }
        else{
            return redirect('/login');
        }
    }

    public function SuccessLogin()
    {
        return view('successlogin');
    }

    public function LogOut()
    {
        Auth::logout();
        return view('logout');
    }
}
