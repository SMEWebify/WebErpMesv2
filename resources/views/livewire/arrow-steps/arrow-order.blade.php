<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="step {{ $OrderStatu == 1 ? 'current' : '' }} {{ $OrderStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('Open') }}</a></span> </div>
            <div class="step {{ $OrderStatu == 2 ? 'current' : '' }} {{ $OrderStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('In progress') }}</a></span> </div>
            <div class="step {{ $OrderStatu == 3 ? 'current' : '' }} {{ $OrderStatu <= 3 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('Delivered') }}</a></span> </div>
            <div class="step {{ $OrderStatu == 4 ? 'current' : '' }} {{ $OrderStatu <= 4 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('Partly delivered') }}</a><span> </div>
            <!--<div class="step {{ $OrderStatu == 5 ? 'current' : '' }} {{ $OrderStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('Stopped') }}</a><span> </div>-->
            <!--<div class="step {{ $OrderStatu == 6 ? 'current' : '' }} {{ $OrderStatu <= 6 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(6)">{{ __('Canceled') }}</a><span> </div>-->
        </div>
    </div>
</div>