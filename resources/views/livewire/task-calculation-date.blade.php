<div>
    <!-- Modal -->
    <x-adminlte-modal wire:ignore.self  id="taskCalculationDate" title="Task Calculation Date" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
        <div class="card-body">
            @if($toBeCalculate)
            <x-adminlte-button class="btn-flat" wire:click="calculate()" type="submit" label="Calculate Task(s)" theme="success" icon="fas fa-lg fa-save" /> 
            @else
            <button onclick="location.reload();"  class="btn btn-primary btn-block">Refresh Page</button>
            @endif
        </div>
        <div class="card-body">
            <x-adminlte-progress theme="success" value="{{ $progress }}"/> {{ $progress }} % ({{ $countTaskCalculate }} tasks calculated)
        </div>
    </x-adminlte-modal>
</div>
