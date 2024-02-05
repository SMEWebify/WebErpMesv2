
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')
            <form wire:submit.prevent="storeKanbanStatuLine">
                <div class="row">
                    <div class="col-4">
                        <label for="title">{{ __('general_content.title_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control @error('title') is-invalid @enderror" name="title" id="title"  wire:model.live="title">
                                <option value="" selected>{{ __('general_content.select_type_trans_key') }}</option>
                                <option value="Open" >{{ __('general_content.open_trans_key') }}</option>
                                <option value="Started" >{{ __('general_content.started_trans_key') }}</option>
                                <option value="In progress" >{{ __('general_content.in_progress_trans_key') }}</option>
                                <option value="Finished" >{{ __('general_content.finished_trans_key') }}</option>
                                <option value="Suspended" >{{ __('general_content.suspended_trans_key') }}</option>
                                <option value="To RFQ" >{{ __('general_content.to_rfq_trans_key') }}</option>
                                <option value="RFQ in progress" >{{ __('general_content.rfq_in_progress_trans_key') }}</option>
                                <option value="Outsourced" >{{ __('general_content.outsourced_trans_key') }}</option>
                                <option value="Supplied" >{{ __('general_content.supplied_trans_key') }}</option>
                            </select>
                        </div>
                        @error('title') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-4">
                        <label for="label">{{ __('general_content.order_trans_key') }} :</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" placeholder="{{ __('general_content.sort_trans_key') }}" wire:model.live="order">
                        </div>
                        @error('order') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <br/>
                        <button type="submit" class="btn btn-success btn-block">{{ __('general_content.add_trans_key') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($KanbanSettingViewLines as $KanbanSettingViewLine)
                        <tr>
                            <td>{{ $KanbanSettingViewLine->title }}</td>
                            <td>{{ $KanbanSettingViewLine->order }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="upKanban({{ $KanbanSettingViewLine->id }})" class="btn btn-secondary"><i class="fa fa-lg fa-fw  fa-sort-amount-down"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="downKanban({{ $KanbanSettingViewLine->id }})" class="btn btn-primary"><i class="fa fa-lg fa-fw  fa-sort-amount-up-alt"></i></a>
                                </div>
                                @if ($KanbanSettingViewLine->tasks->isEmpty())
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="destroyKanban({{ $KanbanSettingViewLine->id }})" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
