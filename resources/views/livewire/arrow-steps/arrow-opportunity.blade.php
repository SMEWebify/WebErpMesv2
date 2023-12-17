<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="step {{ $OpportunityStatu == 1 ? 'current' : '' }} {{ $OpportunityStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('general_content.new_trans_key') }}</a></span> </div>
            <div class="step {{ $OpportunityStatu == 2 ? 'current' : '' }} {{ $OpportunityStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('general_content.quote_made_trans_key') }}</a></span> </div>
            <div class="step {{ $OpportunityStatu == 3 ? 'current' : '' }} {{ $OpportunityStatu <= 3 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.negotiation_trans_key') }}</a></span> </div>
            <div class="step {{ $OpportunityStatu == 4 ? 'current' : '' }} {{ $OpportunityStatu <= 4 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.closed_won_trans_key') }}</a><span> </div>
            <div class="step {{ $OpportunityStatu == 5 ? 'current' : '' }} {{ $OpportunityStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('general_content.closed_lost_trans_key') }}</a><span> </div>
            <div class="step {{ $OpportunityStatu == 6 ? 'current' : '' }} {{ $OpportunityStatu <= 6 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(6)">{{ __('general_content.informational_trans_key') }}</a><span> </div>
        </div>
    </div>
</div>