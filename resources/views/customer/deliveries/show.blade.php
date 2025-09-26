@extends('customer.layouts.app')

@section('title', __('general_content.delivery_notes_trans_key') . ' #' . $delivery->code)

@section('content')
    <div class="container-fluid">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">{{ __('general_content.delivery_notes_trans_key') }} #{{ $delivery->code }}</h2>
                <span class="text-muted">{{ $delivery->GetshortCreatedAttribute() }}</span>
            </div>

            @include('include.alert-result')

            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between">
                        <div>
                            <span class="me-3">{{ $delivery->GetshortCreatedAttribute() }}</span>
                            <span class="me-3">#{{ $delivery->code }}</span>
                            @php($status = $delivery->statu)
                            @if(isset($deliveryStatusBadges[$status]))
                                <span class="badge badge-{{ $deliveryStatusBadges[$status] }}">{{ __($deliveryStatusLabels[$status]) }}</span>
                            @endif
                        </div>
                    </div>

                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.order_trans_key') }}</th>
                                <th>{{ __('general_content.external_id_trans_key') }}</th>
                                <th>{{ __('general_content.description_trans_key') }}</th>
                                <th>{{ __('general_content.qty_trans_key') }}</th>
                                <th>{{ __('general_content.unit_trans_key') }}</th>
                                <th>{{ __('general_content.delivered_qty_trans_key') }}</th>
                                <th>{{ __('general_content.remaining_qty_trans_key') }}</th>
                                <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($delivery->DeliveryLines as $DocumentLine)
                                <tr>
                                    <td>
                                        @if($DocumentLine->OrderLine->order->uuid)
                                            <x-ButtonTextView route="{{ route('customer.orders.show', ['order' => $DocumentLine->OrderLine->order->uuid]) }}" />
                                        @endif
                                        {{ $DocumentLine->OrderLine->order['code'] }}
                                    </td>
                                    <td>{{ $DocumentLine->OrderLine['code'] }}</td>
                                    <td>{{ $DocumentLine->OrderLine['label'] }}</td>
                                    <td>{{ $DocumentLine->OrderLine['qty'] }}</td>
                                    <td>{{ $DocumentLine->OrderLine->Unit['label'] }}</td>
                                    <td>{{ $DocumentLine->qty }}</td>
                                    <td>{{ $DocumentLine->OrderLine['delivered_remaining_qty'] }}</td>
                                    <td>
                                        @if(1 == $DocumentLine->invoice_status)
                                            <span class="badge badge-info">{{ __('general_content.chargeable_trans_key') }}</span>
                                        @endif
                                        @if(2 == $DocumentLine->invoice_status)
                                            <span class="badge badge-danger">{{ __('general_content.not_chargeable_trans_key') }}</span>
                                        @endif
                                        @if(3 == $DocumentLine->invoice_status)
                                            <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>
                                        @endif
                                        @if(4 == $DocumentLine->invoice_status)
                                            <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($DocumentLine->QualityNonConformity)
                                            <a class="btn btn-danger btn-sm" href="#">
                                                <i class="fa fa-light fa-fw fa-exclamation"></i>
                                                {{ $DocumentLine->QualityNonConformity->label }}
                                            </a>
                                            @if($DocumentLine->QualityNonConformity->statu == 1)
                                                <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>
                                            @endif
                                            @if($DocumentLine->QualityNonConformity->statu == 2)
                                                <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span>
                                            @endif
                                            @if($DocumentLine->QualityNonConformity->statu == 3)
                                                <span class="badge badge-success">{{ __('general_content.validate_trans_key') }}</span>
                                            @endif
                                            @if($DocumentLine->QualityNonConformity->statu == 4)
                                                <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span>
                                            @endif
                                        @else
                                            <a class="btn btn-warning btn-sm" href="{{ route('guest.nonConformitie.create', ['id' => $DocumentLine->id]) }}">
                                                <i class="fa fa-light fa-fw fa-exclamation"></i>
                                                {{ __('general_content.new_non_conformitie_trans_key') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h3 class="h6">{{ $Factory->name }}</h3>
                            @if($Factory->picture)
                                <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="Logo" width="64" class="logo"/>
                            @endif
                        </div>
                        <div class="col-lg-6">
                            <h3 class="h6">{{ __('general_content.adress_trans_key') }}</h3>
                            <address class="mb-0">
                                {{ $Factory->address }}<br/>
                                {{ $Factory->zipcode }} {{ $Factory->city }}<br/>
                                {{ __('general_content.phone_trans_key') }} : {{ $Factory->phone_number }}<br/>
                                {{ __('general_content.email_trans_key') }} : {{ $Factory->mail }}<br/>
                            </address>
                        </div>
                    </div>
                </div>
            </div>

            @if ($delivery->comment)
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                        <p class="mb-0">{{ $delivery->comment }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
