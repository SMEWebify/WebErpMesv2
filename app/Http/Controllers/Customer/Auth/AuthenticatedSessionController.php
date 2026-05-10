<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerLoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function store(CustomerLoginRequest $request)
    {
        $request->authenticate();

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
