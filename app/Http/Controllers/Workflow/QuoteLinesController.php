<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Http\Controllers\Controller;
use App\Models\Admin\CustomField;
use App\Models\Admin\CustomFieldValue;
use App\Models\Workflow\QuoteLineDetails;
use App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest;

class QuoteLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/quotes-lines-index');
    }

    /**
     * @param \App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest $request
     * @param int $idQuote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idQuote, UpdateQuoteLineDetailsRequest $request)
    {
        $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
        $validated = $request->validated();
        $validated['custom_requirements'] = $this->sanitizeCustomRequirements($request->input('custom_requirements', []));
        unset($validated['product_custom_fields']);

        $QuoteLineDetails->update($validated);
        $this->syncProductCustomFields(
            $QuoteLineDetails->quote_lines_id,
            $request->input('product_custom_fields', [])
        );

        return redirect()->route('quotes.show', ['id' => $idQuote])->with('success', 'Successfully updated quote detail line');
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

    private function syncProductCustomFields(int $quoteLineId, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $validIds = CustomField::where('related_type', 'product')->pluck('id')->all();

        foreach ($fields as $fieldId => $fieldValue) {
            if (!in_array((int) $fieldId, $validIds, true)) {
                continue;
            }

            $existingValue = CustomFieldValue::where('custom_field_id', $fieldId)
                ->where('entity_id', $quoteLineId)
                ->where('entity_type', 'quote_line')
                ->first();

            if ($fieldValue === null || $fieldValue === '') {
                if ($existingValue) {
                    $existingValue->delete();
                }
                continue;
            }

            if ($existingValue) {
                $existingValue->update(['value' => $fieldValue]);
            } else {
                CustomFieldValue::create([
                    'custom_field_id' => $fieldId,
                    'entity_id' => $quoteLineId,
                    'entity_type' => 'quote_line',
                    'value' => $fieldValue,
                ]);
            }
        }
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage($idQuote,Request $request)
    {
        
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/quote-lines'), $filename);
            $QuoteLineDetails->update(['picture' => $filename]);
            $QuoteLineDetails->save();
            return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    /**
     * Imports quote lines from a CSV file.
     *
     * @param int $idQuote The ID of the quote to import lines into.
     * @param \Illuminate\Http\Request $request The HTTP request object containing the CSV file.
     * @param \App\Services\ImportCsvService $importCsvService The service used to import quote lines from the CSV file.
     * @return \Illuminate\Http\RedirectResponse A redirect response back to the previous page.
     */
    public function import($idQuote, Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importQuoteLines($idQuote, $request);
        return redirect()->back();
    }
}
