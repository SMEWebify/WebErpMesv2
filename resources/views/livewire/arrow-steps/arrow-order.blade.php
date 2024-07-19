<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="step {{ $OrderStatu == 1 ? 'current' : '' }} {{ $OrderStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('general_content.open_trans_key') }}</a></span> </div>
            <div class="step {{ $OrderStatu == 2 ? 'current' : '' }} {{ $OrderStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('general_content.in_progress_trans_key') }}</a></span> </div>
            @if($OrderType == 1)
            <div class="step {{ $OrderStatu == 4 ? 'current' : '' }} {{ $OrderStatu == 3  ? ' done' : '' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.partly_delivered_trans_key') }}</a><span> </div>
            <div class="step {{ $OrderStatu == 3 && $OrderStatu != 4 ? 'current' : '' }} {{ $OrderStatu == 3 && $OrderStatu != 4 ? 'done' : '' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.delivered_trans_key') }}</a></span> </div>
            @else
            <div class="step {{ $OrderStatu == 4 ? 'current' : '' }} {{ $OrderStatu == 3  ? ' done' : '' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.partly_stored_trans_key') }}</a><span> </div>
            <div class="step {{ $OrderStatu == 3 && $OrderStatu != 4 ? 'current' : '' }} {{ $OrderStatu == 3 && $OrderStatu != 4 ? 'done' : '' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.stock_trans_key') }}</a></span> </div>
            @endif <!--<div class="step {{ $OrderStatu == 5 ? 'current' : '' }} {{ $OrderStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('Stopped') }}</a><span> </div>-->
            <!--<div class="step {{ $OrderStatu == 6 ? 'current' : '' }} {{ $OrderStatu <= 6 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(6)">{{ __('general_content.canceled_trans_key') }}</a><span> </div>-->
        </div>
    </div>
</div>