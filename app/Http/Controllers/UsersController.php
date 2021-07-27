<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function List()
    {

        $Users = User::All();

        return view('Users', [
            'Users' => $Users
        ]);
    }
}
