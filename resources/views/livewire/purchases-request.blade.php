<div>
    <div class="card">
        @include('include.alert-result')
        <div class="card-body">
            <form>
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="companies_id">{{ __('general_content.document_type_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <select class="form-control"  wire:click.prevent="changeDocument()" wire:model.live="document_type" name="document_type" id="document_type">
                                <option value="">{{ __('general_content.select_document_trans_key') }}</option>
                                <option value="PU">{{ __('general_content.purchase_order_trans_key') }}</option>
                                <option value="PQ">{{ __('general_content.purchase_quotation_trans_key') }}</option>
                            </select>
                        </div>
                        @error('document_type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="companies_id">{{ __('general_content.sort_companie_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                            </div>
                            <select class="form-control" wire:model.live="companies_id" name="companies_id" id="companies_id">
                                <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                            @forelse ($CompaniesSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                            @empty
                                <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model.live="code" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                        </div>
                        @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="label">{{ __('general_content.label_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model.live="label" name="label"  id="label"  placeholder="{{ __('general_content.name_purchase_trans_key') }}" required>
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-2">
                        <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" wire:model.live="user_id" name="user_id" id="user_id">
                                <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                            @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                            </select>
                        </div>
                        @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <div class="input-group">
                            <br/>
                            <button type="Submit" wire:click.prevent="storePurchase()" class="btn btn-success btn-block">{{ __('general_content.new_purchase_document_trans_key') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                        </th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.service_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($PurchasesRequestsLineslist as $PurchasesRequestsLines)
                        <tr>
                            <td>
                                @if($PurchasesRequestsLines->OrderLines ?? null)
                                    <x-OrderButton id="{{ $PurchasesRequestsLines->OrderLines->orders_id }}" code="{{ $PurchasesRequestsLines->OrderLines->order->code }}"  /> 
                                @else
                                {{__('general_content.generic_trans_key') }} 
                                @endif
                            </td>
                            <td>@if($PurchasesRequestsLines->OrderLines ?? null){{ $PurchasesRequestsLines->OrderLines->qty }} x @endif</td>
                            <td>@if($PurchasesRequestsLines->OrderLines ?? null){{ $PurchasesRequestsLines->OrderLines->label }}@endif</td>
                            <td>
                                <a href="{{ route('production.task.statu.id', ['id' => $PurchasesRequestsLines->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                #{{ $PurchasesRequestsLines->id }} - {{ $PurchasesRequestsLines->label }}
                                @if($PurchasesRequestsLines->component_id )
                                    - {{ $PurchasesRequestsLines->Component['label'] }}
                                @endif
                            </td>
                            
                            <td>
                                @if($PurchasesRequestsLines->component_id ) 
                                <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchasesRequestsLines->component_id])}}" />
                                @endif
                            </td>
                            <td>{{ $PurchasesRequestsLines->qty }}</td>
                            <td @if($PurchasesRequestsLines->methods_services_id ) style="background-color: {{ $PurchasesRequestsLines->service['color'] }};" @endif >
                                @if($PurchasesRequestsLines->methods_services_id )
                                    @if( $PurchasesRequestsLines->service['picture'])
                                        <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $PurchasesRequestsLines->service['picture']) }}'>">
                                            <span>{{ $PurchasesRequestsLines->service['label'] }}</span>
                                        </p>
                                    @else
                                        {{ $PurchasesRequestsLines->service['label'] }}
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $PurchasesRequestsLines->id }}" wire:model.live="data.{{ $PurchasesRequestsLines->id }}.task_id" id="data.{{ $PurchasesRequestsLines->id }}.task_id"  type="checkbox">
                                    <label for="data.{{ $PurchasesRequestsLines->id }}.task_id" class="custom-control-label">{{ __('general_content.add_to_document_trans_key') }} </label>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.service_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>