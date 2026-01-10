<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderSite;
use App\Models\Workflow\OrderSiteImplantation;

class OrderSiteController extends Controller
{
    /**
     * Store a newly created construction site for the order.
     */
    public function store(Request $request, $id)
    {
        $order = Orders::findOrFail($id);
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'characteristics' => 'nullable|string',
            'contact_info' => 'nullable|string',
        ]);

        $data['label'] = $data['name'] ?? null;

        $order->OrderSite()->create($data);

        return back()->with('success', 'Site created');
    }

    /**
     * Update the specified construction site.
     */
    public function update(Request $request, $order, OrderSite $site)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'characteristics' => 'nullable|string',
            'contact_info' => 'nullable|string',
        ]);

        $data['label'] = $data['name'] ?? null;

        $site->update($data);

        return back()->with('success', 'Site updated');
    }

    /**
     * Remove the specified construction site.
     */
    public function destroy($order, OrderSite $site)
    {
        $site->delete();

        return back()->with('success', 'Site deleted');
    }

    /**
     * Store a newly created implantation for the site.
     */
    public function storeImplantation(Request $request, $order, OrderSite $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['order_sites_id'] = $site->id;

        $site->OrderSiteImplantations()->create($data);

        return back()->with('success', 'Implantation created');
    }

    /**
     * Update the specified implantation.
     */
    public function updateImplantation(Request $request, $order, OrderSite $site, OrderSiteImplantation $implantation)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $implantation->update($data);

        return back()->with('success', 'Implantation updated');
    }

    /**
     * Remove the specified implantation.
     */
    public function destroyImplantation($order, OrderSite $site, OrderSiteImplantation $implantation)
    {
        $implantation->delete();

        return back()->with('success', 'Implantation deleted');
    }
}
