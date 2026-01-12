<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OrderLineDetails;
use App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest;

class OrderLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/orders-lines-index');
    }

    /**
     * @param \App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest $request
     * @param int $idOrder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idOrder, UpdateOrderLineDetailsRequest $request)
    {
        $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
        $validated = $request->validated();
        $validated['custom_requirements'] = $this->sanitizeCustomRequirements($request->input('custom_requirements', []));

        $OrderLineDetails->update($validated);

        return redirect()->route('orders.show', ['id' => $idOrder])->with('success', 'Successfully updated order detail line');
    }

    private function sanitizeCustomRequirements(array $requirements): array
    {
        return collect($requirements)
            ->map(function ($requirement) {
                return [
                    'label' => isset($requirement['label']) ? trim($requirement['label']) : '',
                    'value' => isset($requirement['value']) ? trim($requirement['value']) : '',
                ];
            })
            ->filter(function ($requirement) {
                return $requirement['label'] !== '' || $requirement['value'] !== '';
            })
            ->values()
            ->all();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage($idOrder,Request $request)
    {
        
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/order-lines'), $filename);
            $OrderLineDetails->update(['picture' => $filename]);
            $OrderLineDetails->save();
            return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    /**
     * Imports order lines from a CSV file.
     *
     * @param int $idOrder The ID of the order to import lines into.
     * @param \Illuminate\Http\Request $request The HTTP request instance containing the CSV file.
     * @param \App\Services\ImportCsvService $importCsvService The service responsible for importing CSV data.
     * @return \Illuminate\Http\RedirectResponse Redirects back to the previous page after import.
     */
    public function import($idOrder, Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importOrderLines($idOrder, $request);
        return redirect()->back();
    }
}
