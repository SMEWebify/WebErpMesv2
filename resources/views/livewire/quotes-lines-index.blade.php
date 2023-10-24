<div>
    <div class="card">
        @include('include.alert-result')
        <div class="card-body">
            <div class="row">
                @include('include.search-card')
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('quotes_id')" role="button" href="#">Quote @include('include.sort-icon', ['field' => 'quotes_id'])</a>
                            </th>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($QuoteLineslist as $QuoteLine)
                        <tr>
                            <td>
                                <x-QuoteButton id="{{ $QuoteLine->quotes_id }}" code="{{ $QuoteLine->quote['code'] }}"  />
                            </td>
                            <td>{{ $QuoteLine->ordre }}</td>
                            <td>{{ $QuoteLine->code }}</td>
                            <td>
                                @if($QuoteLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $QuoteLine->product_id])}}" />@endif
                            </td>
                            <td>{{ $QuoteLine->label }}</td>
                            <td>{{ $QuoteLine->qty }}</td>
                            <td>{{ $QuoteLine->Unit['label'] }}</td>
                            <td>{{ $QuoteLine->selling_price }}</td>
                            <td>{{ $QuoteLine->discount }}</td>
                            <td>{{ $QuoteLine->VAT['label'] }}</td>
                            <td>{{ $QuoteLine->delivery_date }}</td>
                            <td>
                                @if(1 == $QuoteLine->statu )   <span class="badge badge-info"> {{ __('general_content.open_trans_key') }}</span>@endif
                                @if(2 == $QuoteLine->statu )  <span class="badge badge-warning">{{ __('general_content.send_trans_key') }}</span>@endif
                                @if(3 == $QuoteLine->statu )  <span class="badge badge-success">{{ __('general_content.win_trans_key') }}</span>@endif
                                @if(4 == $QuoteLine->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif
                                @if(5 == $QuoteLine->statu )  <span class="badge badge-secondary">{{ __('general_content.closed_trans_key') }}</span>@endif
                                @if(6 == $QuoteLine->statu )   <span class="badge badge-secondary">{{ __('general_content.obsolete_trans_key') }}</span>@endif
                            </td>
                            <td>
                                <x-ButtonTextView route="{{ route('quotes.show', ['id' => $QuoteLine->quotes_id])}}" />
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="13" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Quote</th>
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
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{ $QuoteLineslist->links() }}
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
<!-- /.div -->
</div>
