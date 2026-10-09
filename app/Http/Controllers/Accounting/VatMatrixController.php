<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVatSalesRule;
use App\Models\Accounting\AccountingVatPurchaseRule;

/**
 * CRUD des deux matrices de TVA (ventes / achats). Chaque ligne associe un
 * couple (régime, nature) à un code de TVA et à ses comptes. Le couple est
 * unique par matrice.
 */
class VatMatrixController extends Controller
{
    public function salesStore(Request $request)
    {
        $data = $this->validateSales($request);
        if ($this->pairExists(AccountingVatSalesRule::class, $data)) {
            return back()->with('error', __('vat.pair_exists_sales'));
        }
        AccountingVatSalesRule::create($data);

        return redirect()->route('accounting.vatMatrixSales')->with('success', __('vat.sales_rule_created'));
    }

    public function salesUpdate(Request $request, int $id)
    {
        $rule = AccountingVatSalesRule::findOrFail($id);
        $data = $this->validateSales($request);
        if ($this->pairExists(AccountingVatSalesRule::class, $data, $rule->id)) {
            return back()->with('error', __('vat.pair_exists_sales'));
        }
        $rule->update($data);

        return redirect()->route('accounting.vatMatrixSales')->with('success', __('vat.sales_rule_updated'));
    }

    public function salesDestroy(int $id)
    {
        AccountingVatSalesRule::findOrFail($id)->delete();

        return redirect()->route('accounting.vatMatrixSales')->with('success', __('vat.sales_rule_deleted'));
    }

    public function purchaseStore(Request $request)
    {
        $data = $this->validatePurchase($request);
        if ($this->pairExists(AccountingVatPurchaseRule::class, $data)) {
            return back()->with('error', __('vat.pair_exists_purchase'));
        }
        AccountingVatPurchaseRule::create($data);

        return redirect()->route('accounting.vatMatrixPurchase')->with('success', __('vat.purchase_rule_created'));
    }

    public function purchaseUpdate(Request $request, int $id)
    {
        $rule = AccountingVatPurchaseRule::findOrFail($id);
        $data = $this->validatePurchase($request);
        if ($this->pairExists(AccountingVatPurchaseRule::class, $data, $rule->id)) {
            return back()->with('error', __('vat.pair_exists_purchase'));
        }
        $rule->update($data);

        return redirect()->route('accounting.vatMatrixPurchase')->with('success', __('vat.purchase_rule_updated'));
    }

    public function purchaseDestroy(int $id)
    {
        AccountingVatPurchaseRule::findOrFail($id)->delete();

        return redirect()->route('accounting.vatMatrixPurchase')->with('success', __('vat.purchase_rule_deleted'));
    }

    private function validateSales(Request $request): array
    {
        return $request->validate([
            'vat_regime_id'      => 'required|exists:vat_regimes,id',
            'vat_nature_id'      => 'required|exists:vat_natures,id',
            'accounting_vats_id' => 'required|exists:accounting_vats,id',
            'sales_account'      => 'nullable|string|max:255',
            'vat_account'        => 'nullable|string|max:255',
        ]);
    }

    private function validatePurchase(Request $request): array
    {
        $data = $request->validate([
            'vat_regime_id'       => 'required|exists:vat_regimes,id',
            'vat_nature_id'       => 'required|exists:vat_natures,id',
            'accounting_vats_id'  => 'required|exists:accounting_vats,id',
            'purchase_account'    => 'nullable|string|max:255',
            'vat_account'         => 'nullable|string|max:255',
            'vat_account_autoliq' => 'nullable|string|max:255',
            'manual_vat'          => 'nullable|boolean',
        ]);

        // Case à cocher : absente du POST quand décochée.
        $data['manual_vat'] = $request->boolean('manual_vat');

        return $data;
    }

    /**
     * Le couple (régime, nature) existe-t-il déjà dans la matrice ?
     */
    private function pairExists(string $model, array $data, ?int $exceptId = null): bool
    {
        return $model::where('vat_regime_id', $data['vat_regime_id'])
            ->where('vat_nature_id', $data['vat_nature_id'])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
}
