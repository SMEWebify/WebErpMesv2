<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="step {{ $QuoteStatu == 1 ? 'current' : '' }} {{ $QuoteStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('general_content.open_trans_key') }}</a></span> </div>
            <div class="step {{ $QuoteStatu == 2 ? 'current' : '' }} {{ $QuoteStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('general_content.send_trans_key') }}</a></span> </div>
            <div class="step {{ $QuoteStatu == 3 ? 'current' : '' }} {{ $QuoteStatu <= 3 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.win_trans_key') }}</a></span> </div>
            <div class="step {{ $QuoteStatu == 4 ? 'current' : '' }} {{ $QuoteStatu <= 4 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.lost_trans_key') }}</a></span> </div>
            <div class="step {{ $QuoteStatu == 5 ? 'current' : '' }} {{ $QuoteStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('general_content.closed_trans_key') }}</a></span> </div>
            <div class="step {{ $QuoteStatu == 6 ? 'current' : '' }} {{ $QuoteStatu <= 6 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(6)">{{ __('general_content.obsolete_trans_key') }}</a></span> </div>
        </div>
    </div>
</div>