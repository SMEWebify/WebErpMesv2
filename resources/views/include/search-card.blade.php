<div>
    <div class="input-group {{ ($compact ?? false) ? 'mb-2' : 'mb-3' }}">
        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
        <input type="text" class="form-control" wire:model.live="search" placeholder="Search line">
    </div>
</div>
