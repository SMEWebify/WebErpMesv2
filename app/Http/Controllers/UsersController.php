<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function List()
    {

        $Users = User::orderBy('id')->paginate(10);

        return view('Users', [
            'Users' => $Users
        ]);
    }
}
