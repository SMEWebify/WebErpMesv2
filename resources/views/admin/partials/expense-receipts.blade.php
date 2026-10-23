{{-- Justificatifs d'une note de frais : GED confidentielle (salarié concerné + RH) --}}
<x-adminlte-card title="{{ __('general_content.expense_receipts_trans_key') }}" theme="primary" icon="fas fa-receipt" maximizable>
    <p class="text-muted">
        <i class="fas fa-lock"></i> {{ __('general_content.expense_receipts_hint_trans_key') }}
    </p>
    @include('include.file-manager-mount', [
        'fileableType' => 'expense-report',
        'fileableId' => $UserExpenseReports->id,
        'fileRoles' => \App\Services\Files\FileRole::forExpenses(),
        'fileManagerCanEdit' => $canEdit,
    ])
</x-adminlte-card>
