<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="col-2 step {{ $PurchaseStatu == 1 ? 'current' : '' }} {{ $PurchaseStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('general_content.in_progress_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $PurchaseStatu == 2 ? 'current' : '' }} {{ $PurchaseStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('general_content.ordered_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $PurchaseStatu == 3 ? 'current' : '' }} {{ $PurchaseStatu <= 3 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.partly_received_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $PurchaseStatu == 4 ? 'current' : '' }} {{ $PurchaseStatu <= 4 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.rceived_trans_key') }}</a></span> </div>
            <!--<div class="step {{ $PurchaseStatu == 5 ? 'current' : '' }} {{ $PurchaseStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('general_content.canceled_trans_key') }}</a></span> </div>-->
        </div>
    </div>
</div>
