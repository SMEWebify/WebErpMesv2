
    <div>
        @include('include.alert-result')
        <div class="card">
            <div class="row">
                <div class="card-body">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                        </div>
                        <input type="text" class="form-control" wire:model.live="search" placeholder="{{ __('general_content.search_task_trans_key') }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="searchIdService" id="searchIdService" wire:model.live="searchIdService">
                                    <option value="">{{ __('general_content.select_service_trans_key') }}</option>
                                    @forelse ($ServicesSelect as $item)
                                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                                    @empty
                                    <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="searchIdService">{{ __('general_content.status_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus">
                                    <option value="" selected>{{ __('general_content.select_statu_trans_key') }}</option>
                                    @forelse ($StatusSelect as $item)
                                    <option value="{{ $item->id }}">{{ $item->title }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="ShowGenericTask">{{ __('general_content.show_generic_task_trans_key') }}</label>
                            <input type="checkbox" id="ShowGenericTask" wire:model.live="ShowGenericTask" style=" display:flex; align-items:center;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('methods_units_id')" role="button" href="#">{{__('general_content.service_trans_key') }} @include('include.sort-icon', ['field' => 'methods_units_id'])</a>
                            </th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.cost_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.total_time_trans_key') }}</th>
                            <th>{{ __('general_content.progress_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Tasklist as $Task)
                        <tr > 
                            <td>
                                @if($Task->OrderLines ?? null)
                                    <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }}"  />
                                @else
                                {{__('general_content.generic_trans_key') }} 
                                @endif
                            </td>
                            <td><a href="{{ route('production.task.statu.id', ['id' => $Task->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a> #{{ $Task->id }} - {{ $Task->label }}</td>
                            <td>@if($Task->component_id ) {{ $Task->Component['label'] }}@endif</td>
                            <td @if($Task->methods_services_id ) style="color: {{ $Task->service['color'] }};" @endif >@if($Task->methods_services_id ) {{ $Task->service['label'] }}@endif</td>
                            <td>{{ $Task->qty }}</td>
                            <td>@if($Task->methods_units_id ) {{ $Task->Unit['label'] }}@endif</td>
                            <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->TotalTime() }} h</td>
                            <td>
                                @if($Task->progress() > 100 )
                                    <x-adminlte-progress theme="teal" value="100" with-label animated/>
                                @else
                                    <x-adminlte-progress theme="teal" value="{{ $Task->progress() }}" with-label animated/>
                                @endif
                            </td>
                            <td>{{ $Task->status['title'] }}</td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="11" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.service_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.cost_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.total_time_trans_key') }}</th>
                            <th>{{ __('general_content.progress_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.row -->
        </div>
    </div>