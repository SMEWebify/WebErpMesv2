<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Returns as ReturnModel;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Quality\QualityNonConformity;
use App\Services\ReturnService;
use App\Services\DocumentCodeGenerator;

class ReturnsIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = '';
    public $sortField = 'created_at';
    public $sortAsc = false;

    public $showCreateForm = false;

    public $code;
    public $label;
    public $deliverys_id;
    public $quality_non_conformity_id;
    public $customer_report;
    public $lines = [];

    public $selectedReturnId;
    public $diagnosisNotes = '';
    public $diagnosisCustomerReport = '';
    public $closingReturnId;
    public $closureNotes = '';

    public $deliveryLinesOptions = [];

    protected ReturnService $returnService;
    protected DocumentCodeGenerator $documentCodeGenerator;

    public function boot(ReturnService $returnService, DocumentCodeGenerator $documentCodeGenerator): void
    {
        $this->returnService = $returnService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    public function mount(): void
    {
        $this->initializeForm();
    }

    public function render()
    {
        $query = ReturnModel::with(['delivery', 'qualityNonConformity'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('label', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('statu', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc');

        $returns = $query->paginate(15);

        $deliveries = Deliverys::select('id', 'code')->orderBy('code')->get();
        $nonConformities = QualityNonConformity::select('id', 'code')->orderBy('code')->get();

        return view('livewire.returns-index', [
            'returns' => $returns,
            'deliveries' => $deliveries,
            'nonConformities' => $nonConformities,
            'deliveryLineChoices' => $this->deliveryLinesOptions,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;

        if ($this->showCreateForm) {
            $this->initializeForm();
        }
    }

    public function addLine(): void
    {
        $this->lines[] = $this->emptyLine();
    }

    public function removeLine(int $index): void
    {
        if (isset($this->lines[$index])) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    public function updatedDeliverysId($value): void
    {
        $this->deliveryLinesOptions = DeliveryLines::with('OrderLine')
            ->where('deliverys_id', $value)
            ->orderBy('ordre')
            ->get(['id', 'ordre', 'order_line_id'])
            ->map(function ($line) {
                $orderLine = $line->OrderLine;

                return [
                    'id' => $line->id,
                    'ordre' => $line->ordre,
                    'label' => $orderLine?->label ?? '',
                ];
            })
            ->toArray();
    }

    public function createReturn(): void
    {
        $this->validate($this->rules());

        if (! $this->hasLineData()) {
            $this->addError('lines', __('returns.messages.no_line'));
            return;
        }

        $payload = [
            'code' => $this->code,
            'label' => $this->label,
            'deliverys_id' => $this->deliverys_id,
            'quality_non_conformity_id' => $this->quality_non_conformity_id,
            'customer_report' => $this->customer_report,
            'created_by' => Auth::id(),
        ];

        $this->returnService->registerReturn($payload, $this->lines);

        session()->flash('success', __('returns.messages.created'));

        $this->showCreateForm = false;
        $this->initializeForm();
        $this->resetPage();
    }

    public function startDiagnosis(int $returnId): void
    {
        $return = ReturnModel::findOrFail($returnId);
        $this->selectedReturnId = $return->id;
        $this->diagnosisNotes = $return->diagnosis ?? '';
        $this->diagnosisCustomerReport = $return->customer_report ?? '';
    }

    public function submitDiagnosis(): void
    {
        if (! $this->selectedReturnId) {
            return;
        }

        $this->validate([
            'diagnosisNotes' => 'required|string',
            'diagnosisCustomerReport' => 'nullable|string',
        ]);

        $return = ReturnModel::findOrFail($this->selectedReturnId);

        $this->returnService->diagnose($return, [
            'diagnosis' => $this->diagnosisNotes,
            'customer_report' => $this->diagnosisCustomerReport,
            'diagnosed_by' => Auth::id(),
        ]);

        session()->flash('success', __('returns.messages.diagnosed'));

        $this->selectedReturnId = null;
        $this->diagnosisNotes = '';
        $this->diagnosisCustomerReport = '';
    }

    public function reopenTasks(int $returnId): void
    {
        $return = ReturnModel::with('lines')->findOrFail($returnId);
        $createdTasks = $this->returnService->createReworkTasks($return);

        if (empty($createdTasks)) {
            session()->flash('warning', __('returns.messages.no_task'));
        } else {
            session()->flash('success', __('returns.messages.tasks_reopened'));
        }
    }

    public function prepareClosure(int $returnId): void
    {
        $return = ReturnModel::findOrFail($returnId);
        $this->closingReturnId = $return->id;
        $this->closureNotes = $return->resolution_notes ?? '';
    }

    public function closeReturn(): void
    {
        if (! $this->closingReturnId) {
            return;
        }

        $this->validate([
            'closureNotes' => 'nullable|string',
        ]);

        $return = ReturnModel::findOrFail($this->closingReturnId);

        $this->returnService->close($return, [
            'resolution_notes' => $this->closureNotes,
        ]);

        session()->flash('success', __('returns.messages.closed'));

        $this->closingReturnId = null;
        $this->closureNotes = '';
    }

    protected function initializeForm(): void
    {
        $lastReturn = ReturnModel::latest('id')->first();
        $this->code = $this->documentCodeGenerator->generateDocumentCode('return', $lastReturn?->id);
        $this->label = $this->code;
        $this->deliverys_id = null;
        $this->quality_non_conformity_id = null;
        $this->customer_report = null;
        $this->lines = [$this->emptyLine()];
        $this->deliveryLinesOptions = [];
    }

    protected function emptyLine(): array
    {
        return [
            'delivery_line_id' => null,
            'original_task_id' => null,
            'qty' => null,
            'issue_description' => null,
            'rework_instructions' => null,
        ];
    }

    protected function rules(): array
    {
        $rules = [
            'label' => 'required|string|max:255',
            'deliverys_id' => 'nullable|exists:deliverys,id',
            'quality_non_conformity_id' => 'nullable|exists:quality_non_conformities,id',
            'customer_report' => 'nullable|string',
            'lines' => 'array|min:1',
        ];

        foreach ($this->lines as $index => $line) {
            $rules["lines.$index.delivery_line_id"] = 'nullable|exists:delivery_lines,id';
            $rules["lines.$index.original_task_id"] = 'nullable|exists:tasks,id';
            $rules["lines.$index.qty"] = 'nullable|integer|min:1';
            $rules["lines.$index.issue_description"] = 'nullable|string';
            $rules["lines.$index.rework_instructions"] = 'nullable|string';
        }

        return $rules;
    }

    protected function hasLineData(): bool
    {
        foreach ($this->lines as $line) {
            if (! empty($line['delivery_line_id']) || ! empty($line['original_task_id']) || ! empty($line['issue_description']) || ! empty($line['rework_instructions'])) {
                return true;
            }
        }

        return false;
    }
}
