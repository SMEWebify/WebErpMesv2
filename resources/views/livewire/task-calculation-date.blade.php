<div>
    <!-- Modal -->
    <x-adminlte-modal wire:ignore.self  id="taskCalculationDate" title="{{ __('general_content.calculate_date_task_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
        <div class="card-body">
            @if($toBeCalculate)
            <x-adminlte-button class="btn-flat" wire:click="calculate()" type="submit" label="{{ __('general_content.calculate_task_trans_key') }}" theme="success" icon="fas fa-lg fa-save" /> 
            @else
            <button onclick="location.reload();"  class="btn btn-primary btn-block">{{ __('general_content.refresh_trans_key') }}</button>
            @endif
        </div>
        <div class="card-body">
            <x-adminlte-progress theme="success" value="{{ $progress }}"/> {{ $progress }} % ({{ $countTaskCalculate }})
        </div>
    </x-adminlte-modal>
</div>
