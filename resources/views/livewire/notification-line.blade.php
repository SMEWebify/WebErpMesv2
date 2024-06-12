<div class="col-md-9">
    <x-adminlte-card title="{{ __('general_content.unread_list_trans_key') }}" theme="primary" maximizable>
        @forelse ($Notificationlist as $key => $not)
            <a href='{{ $not['route'] }}' class='dropdown-item'>
                <i class='mr-2 {{ $not['icon'] }}'></i>{{ $not['text']}}
                <span class='float-right text-muted text-sm'>{{ $not['time']}}</span>
            </a>
            <a class="btn btn-info btn-sm" wire:click="Read('{{ $not['id'] }}')"  href="#">
                <i class="fas fa-folder"></i>
                {{ __('general_content.read_trans_key') }}
            </a>
            <hr>
        @empty
            <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
        @endforelse
        <x-slot name="footerSlot">
            <a class="btn btn-info btn-sm" wire:click="allRead()"  href="#">
                <i class="fas fa-folder"></i>
                {{ __('general_content.all_read_trans_key') }}
            </a>
        </x-slot>
    </x-adminlte-card>
</div>
