<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderRating;
use Illuminate\Support\Facades\DB;

class OrdersRatingController extends Controller
{
    /**
     * Note laissée par le client depuis le lien public ou le portail : route sans
     * authentification, la commande n'est donc reconnue que par son UUID (jamais par
     * un id qu'on pourrait énumérer) et la société est celle de la commande.
     * Une seule note par commande.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'required|string|max:64',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:5000',
        ]);

        $order = Orders::where('uuid', $validated['uuid'])->first();
        abort_if($order === null, 404);

        $created = DB::transaction(function () use ($order, $validated) {
            Orders::whereKey($order->id)->lockForUpdate()->first();

            if (OrderRating::where('orders_id', $order->id)->exists()) {
                return false;
            }

            OrderRating::create([
                'orders_id' => $order->id,
                'companies_id' => $order->companies_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            return true;
        });

        if (! $created) {
            return redirect()->back()->with('error', 'This order has already been rated');
        }

        return redirect()->back()->with('success', 'Rate saved successfully');
    }
}
