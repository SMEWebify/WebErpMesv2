<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Accounting\AccountingEntry;
use App\Models\Admin\Factory;
use App\Exports\AccountingEntryLinesExport;

class FecExportLines extends Component
{
    public $FecExportLineslist;
    public $data = [];

    //export
    public Collection $selectedFecLine;

    // Filtres
    public $journal_code_filters = ['ACHAT', 'VENT'];  // Par défaut, on affiche ACHAT et VENT
    public $start_date;
    public $end_date;

    public function mount()
    {
        $this->selectedFecLine = collect();

        $factory = Factory::first();
        if ($factory) {
            $fiscal = $factory->getCurrentFiscalYear();
            $this->start_date = $fiscal['start']->format('Y-m-d');
            $this->end_date   = $fiscal['end']->format('Y-m-d');
        }
    }

    public function render()
    {
        // Filtrer les lignes d'entrée comptable
        $query = AccountingEntry::where('exported', false);

        // Appliquer le filtre sur journal_code
        if (!empty($this->journal_code_filters)) {
            $query->whereIn('journal_code', $this->journal_code_filters);
        }

        // Appliquer le filtre sur la plage de dates
        if ($this->start_date) {
            $query->where('accounting_date', '>=', $this->start_date);
        }
        if ($this->end_date) {
            $query->where('accounting_date', '<=', $this->end_date);
        }

        $this->FecExportLineslist= $query->get();
        
        return view('livewire.fec-export-lines', [
            'FecExportLineslist' => $this->FecExportLineslist,
        ]);
    }

    private function getSelectedFecLine()
    {
        return $this->selectedFecLine->filter(fn($p) => $p)->keys();
    }

    public function export($ext)
    {
        if(!in_array($ext, ['csv', 'xlsx', 'pdf'])){
            abort(Response::HTTP_NOT_FOUND);
        }
    
        // Utiliser les ids sélectionnés
        $selectedFecLines = $this->selectedFecLine->filter(fn($p) => $p);
    
        // Marquer les lignes comme exportées
        AccountingEntry::whereIn('id', $selectedFecLines)->update(['exported' => true]);
    
        
        // Vider la collection après l'export
        $this->selectedFecLine = collect();
        
        return Excel::download(new AccountingEntryLinesExport($selectedFecLines), 'FecLines.'. $ext);

    }

    // Mise à jour du filtre journal_code
    public function toggleJournalCodeFilter($code)
    {
        if (in_array($code, $this->journal_code_filters)) {
            $this->journal_code_filters = array_diff($this->journal_code_filters, [$code]);
        } else {
            $this->journal_code_filters[] = $code;
        }
    }

    public function applyFilters()
    {
        // Rafraîchir la composante pour appliquer les filtres
        $this->render();
    }

    public function toggleSelected($id)
    {
        // Ajouter ou retirer de la collection
        if ($this->selectedFecLine->contains($id)) {
            $this->selectedFecLine = $this->selectedFecLine->filter(fn($line) => $line != $id);
        } else {
            $this->selectedFecLine->push($id);
        }
    }
}
