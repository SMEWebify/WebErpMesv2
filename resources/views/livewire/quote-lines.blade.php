<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')
            @if($QuoteStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="updateQuoteLine">
                            <input type="hidden" wire:model.live="quote_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeQuoteLine">
                            <input type="hidden"  name="quotes_id"  id="quotes_id" value="1" wire:model.live="quotes_id" >
                            @include('livewire.form.line-create')
                @endif
            @else
            <x-adminlte-alert theme="info" title="Info">
                {{ __('general_content.info_statu_trans_key') }}
            </x-adminlte-alert>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    @include('include.search-card')
                </div>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th colspan="2">{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($QuoteLineslist as $QuoteLine)
                        <tr>
                            <td>
                                
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="upQuoteLine({{ $QuoteLine->id }})" class="btn btn-secondary"><i class="fas fa-sort-up"></i></a>
                                    <span class="btn btn-info">{{ $QuoteLine->ordre }}</span>
                                    <a href="#" wire:click="downQuoteLine({{ $QuoteLine->id }})" class="btn btn-primary"><i class="fas fa-sort-down"></i></a>
                                </div>
                            </td>
                            <td>{{ $QuoteLine->code }}</td>
                            <td>
                                @if($QuoteLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $QuoteLine->product_id])}}" />@endif
                            </td>
                            <td>{{ $QuoteLine->label }}</td>
                            <td>{{ $QuoteLine->qty }}</td>
                            <td>{{ $QuoteLine->Unit['label'] }}</td>
                            <td>{{ $QuoteLine->selling_price }} {{ $Factory->curency }}</td>
                            <td>{{ $QuoteLine->discount }} %</td>
                            <td>{{ $QuoteLine->VAT['rate'] }} %</td>
                            <td>{{ $QuoteLine->delivery_date }}</td>
                            <td>
                                @if(1 == $QuoteLine->statu )   <span class="badge badge-info">{{__('general_content.open_trans_key') }}</span>@endif
                                @if(2 == $QuoteLine->statu )  <span class="badge badge-warning">{{__('general_content.send_trans_key') }}</span>@endif
                                @if(3 == $QuoteLine->statu )  <span class="badge badge-success">{{__('general_content.win_trans_key') }}</span>@endif
                                @if(4 == $QuoteLine->statu )  <span class="badge badge-danger">{{__('general_content.lost_trans_key') }}</span>@endif
                                @if(5 == $QuoteLine->statu )  <span class="badge badge-secondary">{{__('general_content.closed_trans_key') }}</span>@endif
                                @if(6 == $QuoteLine->statu )   <span class="badge badge-secondary">{{__('general_content.obsolete_trans_key') }}</span>@endif
                            </td>
                            <td>
                                <div class="input-group mb-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QuoteLine{{ $QuoteLine->id }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <!-- Modal {{ $QuoteLine->id }} -->
                                        <x-adminlte-modal id="QuoteLine{{ $QuoteLine->id }}" title="Update detail information for {{ $QuoteLine->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('quotes.update.detail.line', ['idQuote'=>  $QuoteLine->quotes_id, 'id' => $QuoteLine->QuoteLineDetails->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="form-group col-md-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->material }}" name="material" id="material"  placeholder="{{ __('general_content.material_trans_key') }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->thickness }}" name="thickness" id="thickness"  placeholder="{{ __('general_content.thickness_trans_key') }}" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->weight }}" name="weight" id="weight"  placeholder="{{ __('general_content.weight_trans_key') }}" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="form-group col-md-4">
                                                        <label for="x_size">X</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->x_size }}" name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label for="y_size">Y</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->y_size }}"  name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label for="z_size">Z</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->z_size }}" name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control"  value="{{ $QuoteLine->QuoteLineDetails->x_oversize }}" name="x_oversize" id="x_oversize"  placeholder="{{ __('general_content.x_oversize_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->y_oversize }}" name="y_oversize" id="y_oversize"  placeholder="{{ __('general_content.y_oversize_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->z_oversize }}" name="z_oversize" id="z_oversize"  placeholder="{{ __('general_content.z_oversize_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->diameter }}" name="diameter" id="diameter"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->material_loss_rate }}" name="material_loss_rate" id="material_loss_rate"  placeholder="{{ __('general_content.material_loss_rate_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->finishing }}" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <x-FormTextareaComment  label="Internal comment" name="internal_comment" comment="{{ $QuoteLine->QuoteLineDetails->internal_comment }}" />
                                                    </div>
                                                    <div class="row">
                                                        <x-FormTextareaComment  label="External comment" name="external_comment" comment="{{ $QuoteLine->QuoteLineDetails->external_comment }}" />
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
                                            <div class="card-body">
                                                <form action="{{ route('quotes.update.detail.picture', ['idQuote'=>  $QuoteLine->quotes_id, 'id' => $QuoteLine->QuoteLineDetails->id]) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-image"></i></span>
                                                        </div>
                                                        <div class="custom-file">
                                                            <input type="hidden" name="id" value="{{ $QuoteLine->id }}">
                                                            <input type="file" class="custom-file-input" name="picture" id="picture">
                                                            <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-adminlte-modal>
                                    </div>
                                    <div class="input-group-prepend">
                                        @if( $QuoteLine->product_id && $QuoteLine->Product->drawing_file)
                                        <!-- Drawing link -->
                                        <a class="btn btn-info" href="{{ asset('drawing/'. $QuoteLine->Product->drawing_file) }}" target="_blank"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                                        @endif
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($QuoteStatu == 1)
                                            <a href="#" class="dropdown-item " wire:click="duplicateLine({{$QuoteLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                            <a href="#" class="dropdown-item" wire:click="editQuoteLine({{$QuoteLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                            <a href="#" class="dropdown-item" wire:click="destroyQuoteLine({{$QuoteLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                            @if($QuoteLine->product_id )
                                            <a href="#" class="dropdown-item" wire:click="breakDown({{$QuoteLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>{{ __('general_content.break_down_task_trans_key') }}</span></a>
                                            @endif
                                            @else
                                                <p class="dropdown-item "><span class="text-info">{{ __('general_content.quote_not_open_trans_key') }}</span></p>
                                            @endif

                                            @if($QuoteLine->code && $QuoteLine->label)
                                            <a href="#" class="dropdown-item" wire:click="CreatProduct({{$QuoteLine->id}})" ><span class="text-success"><i class="fa fa-lg fa-fw fas fa-barcode"></i>{{ __('general_content.create_product_trans_key') }}</span></a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-warning" data-toggle="modal" data-target="#QuoteLineTasks{{ $QuoteLine->id }}">
                                            <i class="fa fa-lg fa-fw  fas fa-list"></i>
                                        </button>
                                        <!-- Modal {{ $QuoteLine->id }} -->
                                        <x-adminlte-modal id="QuoteLineTasks{{ $QuoteLine->id }}" title="Task detail for {{ $QuoteLine->label }}" theme="warning" icon="fa fa-pen" size='lg' disable-animations>
                                            <div class="card-body">
                                                <div class="row">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('general_content.order_trans_key') }}</th>
                                                                <th>{{ __('general_content.label_trans_key') }}</th>
                                                                <th>{{ __('general_content.service_trans_key') }}</th>
                                                                <th>{{ __('general_content.total_time_trans_key') }}</th>
                                                                <th>{{ __('general_content.qty_trans_key') }}</th>
                                                                <th>{{ __('general_content.cost_trans_key') }}</th>
                                                                <th>{{ __('general_content.margin_trans_key') }}</th>
                                                                <th>{{ __('general_content.price_trans_key') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ( $QuoteLine->Task as $Task)
                                                            <tr>
                                                                <td>{{ $Task->ordre }}</td>
                                                                <td>{{ $Task->label }}</td>
                                                                <td @if($Task->methods_services_id ) style="background-color: {{ $Task->service['color'] }};" @endif >
                                                                    @if($Task->methods_services_id )
                                                                        @if( $Task->service['picture'])
                                                                            <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $Task->service['picture']) }}'>">
                                                                                <span>{{ $Task->service['label'] }}</span>
                                                                            </p>
                                                                        @else
                                                                            {{ $Task->service['label'] }}
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                                <td>{{ $Task->TotalTime() }} h</td>
                                                                <td>{{ $Task->qty }}</td>
                                                                <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                                                                <td>{{ $Task->Margin() }} %</td>
                                                                <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
                                                            </tr>
                                                            @empty
                                                            <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <a class="btn btn-info btn-sm" href="{{ route('task.manage', ['id_type'=> 'quote_lines_id', 'id_page'=>  $QuoteLine->quotes_id, 'id_line' => $QuoteLine->id])}}">
                                                    <i class="fas fa-folder"></i>
                                                    {{ __('general_content.view_trans_key') }}
                                                </a>
                                            </div>
                                        </x-adminlte-modal>
                                        <a href="{{ route('task.manage', ['id_type'=> 'quote_lines_id', 'id_page'=>  $QuoteLine->quotes_id, 'id_line' => $QuoteLine->id])}}" class="dropdown-item" ><span class="text-success"> {{ __('general_content.tasks_trans_key') }}{{  $QuoteLine->getAllTaskCountAttribute() }}</span></a></button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $QuoteLine->id }}" wire:model.live="data.{{ $QuoteLine->id }}.quote_line_id" id="data.{{ $QuoteLine->id }}.quote_line_id"  type="checkbox">
                                    <label for="data.{{ $QuoteLine->id }}.quote_line_id" class="custom-control-label">+</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="13" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th></th>
                            <th >
                                <a class="btn btn-primary btn-sm" wire:click="storeOrder({{ $QuoteId }})" href="#">
                                    <i class="fas fa-folder"></i>
                                    {{ __('general_content.new_order_trans_key') }}
                                </a>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
