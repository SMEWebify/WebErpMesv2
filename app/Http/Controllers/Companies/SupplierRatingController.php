<?php

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Companies\SupplierRating;

class SupplierRatingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'purchases_id' => 'required|exists:purchases,id',
            'companies_id' => 'required|exists:companies,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'approved_at' => 'nullable|date',
            'next_review_at' => 'nullable|date',
            'evaluation_status' => 'nullable|string|max:191',
            'evaluation_score_quality' => 'nullable|integer|min:0|max:100',
            'evaluation_score_logistics' => 'nullable|integer|min:0|max:100',
            'evaluation_score_service' => 'nullable|integer|min:0|max:100',
            'action_plan' => 'nullable|string',
        ]);

        $data['evaluation_status'] = $data['evaluation_status'] ?? 'pending';

        if ($data['evaluation_status'] === 'approved' && empty($data['approved_at'])) {
            $data['approved_at'] = now();
        }

        SupplierRating::create($data);

        $message = trans('general_content.supplier_evaluation_saved_trans_key');

        if ($message === 'general_content.supplier_evaluation_saved_trans_key') {
            $message = 'Rate saved successfully';
        }

        return redirect()->back()->with('success', $message);
    }
}
