<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Models\Accounting\VatRegime;
use App\Models\Accounting\VatNature;

/**
 * CRUD des tables de référence de la matrice de TVA : régimes (fiche client) et
 * natures (fiche article). Administrable librement ; la suppression d'une valeur
 * encore référencée par une règle est refusée par la contrainte d'intégrité.
 */
class VatReferenceController extends Controller
{
    public function regimeStore(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:255|unique:vat_regimes,code',
            'label' => 'required|string|max:255',
        ]);
        VatRegime::create($data);

        return redirect()->route('accounting.vatReferences')->with('success', __('vat.regime_created'));
    }

    public function regimeUpdate(Request $request, int $id)
    {
        $regime = VatRegime::findOrFail($id);
        $data = $request->validate([
            'code'  => 'required|string|max:255|unique:vat_regimes,code,' . $regime->id,
            'label' => 'required|string|max:255',
        ]);
        $regime->update($data);

        return redirect()->route('accounting.vatReferences')->with('success', __('vat.regime_updated'));
    }

    public function regimeDestroy(int $id)
    {
        return $this->destroyGuarded(VatRegime::findOrFail($id), __('vat.regime_deleted'));
    }

    public function natureStore(Request $request)
    {
        $data = $request->validate([
            'code'  => 'required|string|max:255|unique:vat_natures,code',
            'label' => 'required|string|max:255',
        ]);
        VatNature::create($data);

        return redirect()->route('accounting.vatReferences')->with('success', __('vat.nature_created'));
    }

    public function natureUpdate(Request $request, int $id)
    {
        $nature = VatNature::findOrFail($id);
        $data = $request->validate([
            'code'  => 'required|string|max:255|unique:vat_natures,code,' . $nature->id,
            'label' => 'required|string|max:255',
        ]);
        $nature->update($data);

        return redirect()->route('accounting.vatReferences')->with('success', __('vat.nature_updated'));
    }

    public function natureDestroy(int $id)
    {
        return $this->destroyGuarded(VatNature::findOrFail($id), __('vat.nature_deleted'));
    }

    /**
     * Supprime un modèle en interceptant l'échec d'intégrité (valeur encore
     * utilisée par une règle de matrice) pour le présenter en clair.
     */
    private function destroyGuarded($model, string $okMessage)
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            return redirect()->route('accounting.vatReferences')
                ->with('error', __('vat.reference_in_use'));
        }

        return redirect()->route('accounting.vatReferences')->with('success', $okMessage);
    }
}
