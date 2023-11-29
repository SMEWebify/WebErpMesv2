<div>
    <!-- Modal -->
    <x-adminlte-modal wire:ignore.self  id="taskCalculationRessource" title="{{ __('general_content.calculate_ressource_task_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
        <div class="card-body">
            @if($toBeCalculateRessource)
            <x-adminlte-button class="btn-flat" wire:click="calculateRessource()" type="submit" label="test" theme="success" icon="fas fa-lg fa-save" /> 
            @else
            <button onclick="location.reload();"  class="btn btn-primary btn-block">{{ __('general_content.refresh_trans_key') }}</button>
            @endif
        </div>
        <div class="card-body">
            <x-adminlte-progress theme="success" value="{{ $progressRessource }}"/> {{ $progressRessource }} % ({{ $countTaskCalculateRessource  }})
            <ul>
                {!! $progressRessourceLog!!}
            </ul>
        </div>
    </x-adminlte-modal>

    <!-- Modal -->
    <x-adminlte-modal wire:ignore.self  id="taskCalculationDate" title="{{ __('general_content.calculate_date_task_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
        <div class="card-body">
            @if($toBeCalculateDate)
            <x-adminlte-button class="btn-flat" wire:click="calculateDate()" type="submit" label="{{ __('general_content.calculate_task_trans_key') }}" theme="success" icon="fas fa-lg fa-save" /> 
            @else
            <button onclick="location.reload();"  class="btn btn-primary btn-block">{{ __('general_content.refresh_trans_key') }}</button>
            @endif
        </div>
        <div class="card-body">
            <x-adminlte-progress theme="success" value="{{ $progressDate }}"/> {{ $progressDate }} % ({{ $countTaskCalculateDate }})
        </div>
        <ul>
            {!! $progressDateLog !!}
        </ul>
        
    </x-adminlte-modal>
</div>