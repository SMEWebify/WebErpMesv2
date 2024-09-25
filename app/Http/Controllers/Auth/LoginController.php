<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        // Determine redirection based on $request->modeView
        switch ($request->modeView) {
            case 'workshop':
                $this->redirectTo = RouteServiceProvider::WORKSHOP;
                break;
            default:
                $this->redirectTo = RouteServiceProvider::HOME;
        }
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Vérifie si le driver est LDAP
        if (env('AUTH_DRIVER') === 'ldap') {
            // Authentification via LDAP
            $username = $request->input('username');
            $password = $request->input('password');

            // Tentative de connexion via LDAP
            if (Auth::guard('ldap')->attempt(['username' => $username, 'password' => $password])) {
                return $this->sendLoginResponse($request);
            }

            // Si l'authentification échoue
            return $this->sendFailedLoginResponse($request);
        } else {
            // Authentification via email
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials, $request->filled('remember'))) {
                return $this->sendLoginResponse($request);
            }

            // Si l'authentification échoue
            return $this->sendFailedLoginResponse($request);
        }
    }

    protected function validateLogin(Request $request)
    {
        // Valider les champs selon le driver utilisé
        if (env('AUTH_DRIVER') === 'ldap') {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
        } else {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
        }
    }
}
