<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Methods\MethodsUnits;
use Symfony\Component\HttpFoundation\Response;

class CheckFactory
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !Factory::exists()
            || !AccountingVat::exists()
            || !AccountingPaymentConditions::exists()
            || !AccountingPaymentMethod::exists()
            || !AccountingDelivery::exists()
            || !MethodsUnits::exists()
        ) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
