<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Exports\InvoiceLinesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Workflow\InvoiceLines;

class InvoiceExportLines extends Component
{
    public $InvoiceExportLineslist;
    public $data = [];

    //export
    public array $selectedInvoiceLine = [];

    public function mount() 
    {
        $this->selectedInvoiceLine = [];
    }

    public function render()
    {
        $InvoiceExportLineslist = $this->InvoiceExportLineslist = InvoiceLines::where('exported', false) // Filter only non-exported lines
                                                                            ->get();

        return view('livewire.invoice-export-lines', [
            'InvoiceExportLineslist' => $InvoiceExportLineslist,
        ]);
    }

    private function getSelectedInvoiceLine()
    {
        return collect($this->selectedInvoiceLine)
            ->filter(fn($p) => $p)
            ->keys();
    }

    public function export($ext)
    {

        if(!in_array($ext, ['csv', 'xlsx', 'pdf'])){
            abort(Response::HTTP_NOT_FOUND);
        }

        $selectedInvoiceLines = $this->getSelectedInvoiceLine();

        // Marquer les lignes comme exportées
        InvoiceLines::whereIn('id', $selectedInvoiceLines)->update(['exported' => true]);

        return Excel::download(new InvoiceLinesExport($this-> getSelectedInvoiceLine()), 'invoiceLines.'. $ext);
    }
}
