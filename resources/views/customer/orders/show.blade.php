@extends('customer.layouts.app')

@section('title', __('general_content.order_trans_key') . ' #' . $order->code)

@section('content')
    <div class="container-fluid">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">{{ __('general_content.order_trans_key') }} #{{ $order->code }}</h2>
                <span class="text-muted">{{ $order->GetshortCreatedAttribute() }}</span>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3 d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="me-3">{{ $order->GetshortCreatedAttribute() }}</span>
                                    <span class="me-3">#{{ $order->code }}</span>
                                    @php($status = $order->statu)
                                    @if(isset($orderStatusBadges[$status]))
                                        <span class="badge badge-{{ $orderStatusBadges[$status] }}">{{ __($orderStatusLabels[$status]) }}</span>
                                    @endif
                                </div>
                            </div>

                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>{{ __('general_content.description_trans_key') }}</th>
                                        <th>{{ __('general_content.qty_trans_key') }}</th>
                                        <th>{{ __('general_content.price_trans_key') }}</th>
                                        <th>{{ __('general_content.discount_trans_key') }}</th>
                                        <th>{{ __('general_content.vat_trans_key') }}</th>
                                        <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                                        <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->OrderLines as $DocumentLine)
                                        <tr>
                                            <td>
                                                <div class="d-flex mb-2">
                                                    <div class="flex-lg-grow-1">
                                                        <h6 class="small mb-0">{{ $DocumentLine->label }}</h6>
                                                        <span class="text-muted">{{ $DocumentLine->code }}</span>
                                                        @php
                                                            $guestDetail = $DocumentLine->OrderLineDetails ?? null;
                                                            $guestCustomRequirements = $guestDetail ? collect($guestDetail->custom_requirements ?? [])->filter(function ($requirement) {
                                                                return !empty($requirement['label'] ?? null) || !empty($requirement['value'] ?? null);
                                                            }) : collect();
                                                        @endphp
                                                        @if($guestCustomRequirements->isNotEmpty())
                                                            <ul class="list-unstyled mb-0 small text-muted">
                                                                @foreach($guestCustomRequirements as $requirement)
                                                                    <li><strong>{{ $requirement['label'] ?? __('Requirement') }}:</strong> {{ $requirement['value'] ?? '' }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</td>
                                            <td class="text-end">{{ $DocumentLine->selling_price }} {{ $Factory->curency }}</td>
                                            <td>{{ $DocumentLine->discount }} %</td>
                                            <td>{{ $DocumentLine->VAT['rate'] }} %</td>
                                            <td>
                                                @if(1 == $DocumentLine->delivery_status)
                                                    <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>
                                                @endif
                                                @if(2 == $DocumentLine->delivery_status)
                                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $DocumentLine->id }}">
                                                        <span class="badge badge-warning">{{ __('general_content.partly_delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }})</span>
                                                    </a>
                                                @endif
                                                @if(3 == $DocumentLine->delivery_status)
                                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $DocumentLine->id }}">
                                                        <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }})</span>
                                                    </a>
                                                @endif
                                                @if(4 == $DocumentLine->delivery_status)
                                                    <span class="badge badge-primary">{{ __('general_content.delivered_without_dn_trans_key') }} ({{ $DocumentLine->delivered_qty }})</span>
                                                @endif

                                                <x-adminlte-modal id="modalDeliveryFor{{ $DocumentLine->id }}" title="{{ __('general_content.deliverys_notes_list_trans_key') }}" theme="info" icon="fas fa-bolt" size="lg" disable-animations>
                                                    <ul class="list-unstyled mb-0">
                                                        @foreach($DocumentLine->DeliveryLines as $deliveryLine)
                                                            <li class="mb-3">
                                                                <div><strong>{{ __('general_content.delivery_notes_trans_key') }}:</strong> {{ $deliveryLine->delivery->code }}</div>
                                                                <div><strong>{{ __('general_content.qty_trans_key') }}:</strong> {{ $deliveryLine->qty }}</div>
                                                                <div><strong>{{ __('general_content.created_at_trans_key') }}:</strong> {{ $deliveryLine->GetPrettyCreatedAttribute() }}</div>
                                                                <x-ButtonTextView route="{{ route('customer.deliveries.show', ['delivery' => $deliveryLine->delivery->uuid]) }}" />
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </x-adminlte-modal>
                                            </td>
                                            <td>
                                                @if(1 == $DocumentLine->invoice_status)
                                                    <span class="badge badge-info">{{ __('general_content.not_invoiced_trans_key') }}</span>
                                                @endif
                                                @if(2 == $DocumentLine->invoice_status)
                                                    <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>
                                                @endif
                                                @if(3 == $DocumentLine->invoice_status)
                                                    <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}" />
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td colspan="2" class="text-end"><hr></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">{{ __('general_content.sub_total_trans_key') }}</td>
                                        <td colspan="2" class="text-end">{{ $subPrice }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @forelse($vatPrice as $vatRate)
                                        <tr>
                                            <td colspan="3">{{ __('general_content.tax_trans_key') }} {{ $vatRate[0] }}%</td>
                                            <td colspan="2" class="text-end">{{ number_format($vatRate[1], 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">{{ __('general_content.no_tax_trans_key') }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    @endforelse
                                    <tr class="fw-bold">
                                        <td colspan="3">{{ __('general_content.total_trans_key') }}</td>
                                        <td colspan="2" class="text-end">{{ number_format($totalPrices, 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <h3 class="h6">{{ __('general_content.payment_methods_trans_key') }}</h3>
                                    <p class="mb-0">{{ $order->payment_method['label'] ?? '-' }}</p>
                                </div>
                                <div class="col-lg-6">
                                    <h3 class="h6">{{ __('general_content.payment_conditions_trans_key') }}</h3>
                                    <p class="mb-0">{{ $order->payment_condition['label'] ?? '-' }}</p>
                                </div>
                            </div>
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
                </div>

                <div class="col-lg-4">
                    @if ($order->comment)
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                                <p class="mb-0">{{ $order->comment }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($order->customer_reference)
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.identifier_trans_key') }}</h3>
                                <p class="mb-0">{{ $order->customer_reference }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="h6">{{ __('general_content.delevery_method_trans_key') }}</h3>
                            <strong>{{ $order->delevery_method['label'] ?? '-' }}</strong>
                            <hr>
                            <h3 class="h6">{{ __('general_content.adress_trans_key') }}</h3>
                            <address class="mb-0">
                                <strong>{{ $order->companie['label'] ?? '-' }}</strong><br>
                                {{ $order->contact['civility'] ?? '' }} {{ $order->contact['first_name'] ?? '' }} {{ $order->contact['name'] ?? '' }}<br>
                                {{ $order->adresse['adress'] ?? '' }}<br>
                                {{ $order->adresse['zipcode'] ?? '' }} {{ $order->adresse['city'] ?? '' }}<br>
                                {{ $order->adresse['country'] ?? '' }}
                            </address>
                        </div>
                    </div>

                    <div class="card">
                        @if($order->Rating->isEmpty())
                            <form action="{{ route('order.ratings.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <input type="hidden" name="orders_id" value="{{ $order->id }}">
                                    <input type="hidden" name="companies_id" value="{{ $order->companies_id }}">
                                    <div class="form-group">
                                        <label for="rating">{{ __('general_content.order_rate_trans_key') }}</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-star-half-alt"></i></span>
                                            </div>
                                            <select name="rating" id="rating" class="form-control">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <x-FormTextareaComment comment="" />
                                    </div>
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save" />
                                </div>
                            </form>
                        @else
                            @php($Rating = $order->Rating->first())
                            <div class="card-body">
                                <label for="rating">{{ __('general_content.order_rate_trans_key') }}</label>
                                <div>
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= ($Rating->rating ?? 0))
                                            <span class="badge badge-warning">&#9733;</span>
                                        @else
                                            <span class="badge badge-info">&#9734;</span>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
