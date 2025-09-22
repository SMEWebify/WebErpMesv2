<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Returns as ReturnModel;
use App\Services\ReturnService;

class ReturnShow extends Component
{
    public int $returnId;
    public ReturnModel $return;
    public string $diagnosisNotes = '';
    public string $diagnosisCustomerReport = '';
    public string $closureNotes = '';

    protected ReturnService $returnService;

    public function boot(ReturnService $returnService): void
    {
        $this->returnService = $returnService;
    }

    public function mount(int $returnId): void
    {
        $this->returnId = $returnId;
        $this->loadReturn();
    }

    public function render()
    {
        return view('livewire.return-show');
    }

    public function submitDiagnosis(): void
    {
        $this->validate([
            'diagnosisNotes' => 'required|string',
            'diagnosisCustomerReport' => 'nullable|string',
        ]);

        $this->returnService->diagnose($this->return, [
            'diagnosis' => $this->diagnosisNotes,
            'customer_report' => $this->diagnosisCustomerReport,
            'diagnosed_by' => Auth::id(),
        ]);

        session()->flash('success', __('returns.messages.diagnosed'));
        $this->loadReturn();
    }

    public function reopenTasks(): void
    {
        $createdTasks = $this->returnService->createReworkTasks($this->return);

        if (empty($createdTasks)) {
            session()->flash('warning', __('returns.messages.no_task'));
        } else {
            session()->flash('success', __('returns.messages.tasks_reopened'));
        }

        $this->loadReturn();
    }

    public function closeReturn(): void
    {
        $this->validate([
            'closureNotes' => 'nullable|string',
        ]);

        $this->returnService->close($this->return, [
            'resolution_notes' => $this->closureNotes,
        ]);

        session()->flash('success', __('returns.messages.closed'));
        $this->loadReturn();
    }

    protected function loadReturn(): void
    {
        $this->return = ReturnModel::with([
            'delivery',
            'qualityNonConformity',
            'lines.deliveryLine',
            'lines.originalTask',
            'lines.reworkTask',
        ])->findOrFail($this->returnId);

        $this->diagnosisNotes = $this->return->diagnosis ?? '';
        $this->diagnosisCustomerReport = $this->return->customer_report ?? '';
        $this->closureNotes = $this->return->resolution_notes ?? '';
    }
}
