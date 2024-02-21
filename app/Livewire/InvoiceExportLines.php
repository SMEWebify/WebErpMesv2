<?php

namespace App\Livewire;

use Livewire\Component;
use App\Exports\InvoiceLinesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use App\Models\Workflow\InvoiceLines;

class InvoiceExportLines extends Component
{

    public $search = 'enter the invoice code to export';
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $InvoiceExportLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $data = [];

    //export
    public Collection $selectedInvoiceLine;

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }

    public function mount() 
    {
        $this->selectedInvoiceLine = collect();
    }


    public function render()
    {
        //Select lines list to export
        $value =$this->search;
        $InvoiceExportLineslist = $this->InvoiceExportLineslist = InvoiceLines::whereHas('invoice', function($q) use( $value) {
                                                                                    $q->where('invoices.code','like', '%'.$value.'%'); 
                                                                                })
                                                                                ->get();

        return view('livewire.invoice-export-lines', [
            'InvoiceExportLineslist' => $InvoiceExportLineslist,
        ]);
    }

    private function getSelectedInvoiceLine()
    {
        return $this->selectedInvoiceLine->filter(fn($p) => $p)->keys();
    }

    public function export($ext)
    {

        if(!in_array($ext, ['csv', 'xlsx', 'pdf'])){
            code:Response::HTTP_NOT_FOUND;
        }

        return Excel::download(new InvoiceLinesExport($this-> getSelectedInvoiceLine()), 'invoiceLines.'. $ext);
    }
}
