<div class="row ">
    <div class="col-12">
        <div class="arrow-steps clearfix">
            <div class="col-2 step {{ $LeadStatu == 1 ? 'current' : '' }} {{ $LeadStatu <= 1 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(1)">{{ __('general_content.new_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $LeadStatu == 2 ? 'current' : '' }} {{ $LeadStatu <= 2 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(2)">{{ __('general_content.assigned_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $LeadStatu == 3 ? 'current' : '' }} {{ $LeadStatu <= 3 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(3)">{{ __('general_content.in_progress_trans_key') }}</a></span> </div>
            <div class="col-2 step {{ $LeadStatu == 4 ? 'current' : '' }} {{ $LeadStatu <= 4 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(4)">{{ __('general_content.converted_trans_key') }}</a><span> </div>
            <div class="col-2 step {{ $LeadStatu == 5 ? 'current' : '' }} {{ $LeadStatu <= 5 ? ' ' : 'done' }}"> <span><a href="#" wire:click="changeStatu(5)">{{ __('general_content.lost_trans_key') }}</a><span> </div>
        </div>
    </div>
</div>


