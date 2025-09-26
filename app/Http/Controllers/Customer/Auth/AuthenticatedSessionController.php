<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the customer login form.
     */
    public function create()
    {
        return view('customer.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $authenticated = Auth::guard('customer')->attempt([
            'mail' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $customer->forceFill(['last_login_at' => now()])->save();
        }

        return redirect()->intended(RouteServiceProvider::CUSTOMER_HOME);
    }

    /**
     * Log the customer out of the application.
     */
    public function destroy(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
