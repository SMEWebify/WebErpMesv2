<div class="col-md-9">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Unread List</h3>
        </div>
        <div class="card-body">
            @forelse ($Notificationlist as $key => $not)
                <a href='{{ $not['route'] }}' class='dropdown-item'>
                    <i class='mr-2 {{ $not['icon'] }}'></i>{{ $not['text']}}
                    <span class='float-right text-muted text-sm'>{{ $not['time']}}</span>
                </a>
                <a class="btn btn-info btn-sm" wire:click="Read('{{ $not['id'] }}')"  href="#">
                    <i class="fas fa-folder"></i>
                    Read
                </a>
                <hr>
            @empty
                <x-EmptyDataLine col="12" text="No notification  ..."  />
            @endforelse
            <a class="btn btn-info btn-sm" wire:click="allRead()"  href="#">
                <i class="fas fa-folder"></i>
                All Read
            </a>
        </div>
    </div>
</div>
