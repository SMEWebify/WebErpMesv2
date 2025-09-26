@extends('customer.layouts.app')

@section('title', __('general_content.invoice_trans_key') . ' #' . $invoice->code)

@section('content')
    <div class="container-fluid">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">{{ __('general_content.invoice_trans_key') }} #{{ $invoice->code }}</h2>
                <span class="text-muted">{{ $invoice->GetshortCreatedAttribute() }}</span>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3 d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="me-3">{{ $invoice->GetshortCreatedAttribute() }}</span>
                                    <span class="me-3">#{{ $invoice->code }}</span>
                                    @php($status = $invoice->statu)
                                    @if(isset($invoiceStatusBadges[$status]))
                                        <span class="badge badge-{{ $invoiceStatusBadges[$status] }}">{{ __($invoiceStatusLabels[$status]) }}</span>
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
                                        <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->invoiceLines as $DocumentLine)
                                        <tr>
                                            <td>
                                                <div class="d-flex mb-2">
                                                    <div class="flex-lg-grow-1">
                                                        <h6 class="small mb-0">{{ $DocumentLine->orderLine?->label }}</h6>
                                                        <span class="text-muted">{{ $DocumentLine->orderLine?->code }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $DocumentLine->qty }} {{ $DocumentLine->orderLine?->Unit['label'] }}</td>
                                            <td class="text-end">{{ $DocumentLine->selling_price }} {{ $Factory->curency }}</td>
                                            <td>{{ $DocumentLine->discount }} %</td>
                                            <td>{{ $DocumentLine->orderLine?->VAT['rate'] }} %</td>
                                            <td>
                                                @if($DocumentLine->deliveryLine?->delivery)
                                                    <x-ButtonTextView route="{{ route('customer.deliveries.show', ['delivery' => $DocumentLine->deliveryLine->delivery->uuid]) }}" />
                                                    {{ $DocumentLine->deliveryLine->delivery->code }}
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"></td>
                                        <td colspan="2" class="text-end"><hr></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">{{ __('general_content.sub_total_trans_key') }}</td>
                                        <td colspan="2" class="text-end">{{ $subPrice }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @forelse($vatPrice as $vatRate)
                                        <tr>
                                            <td colspan="2">{{ __('general_content.tax_trans_key') }} {{ $vatRate[0] }}%</td>
                                            <td colspan="2" class="text-end">{{ number_format($vatRate[1], 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2">{{ __('general_content.no_tax_trans_key') }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    @endforelse
                                    <tr>
                                        <td colspan="2">{{ __('general_content.total_trans_key') }}</td>
                                        <td colspan="2" class="text-end fw-bold">{{ number_format($totalPrices, 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    @if($invoice->comment)
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                                <p class="mb-0">{{ $invoice->comment }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="h6">{{ __('general_content.customer_trans_key') }}</h3>
                              <p class="mb-0">
                                  {{ $invoice->companie?->label }}<br>
                                  {{ $invoice->contact?->full_name }}<br>
                                  {{ $invoice->adresse?->adress }}<br>
                                  {{ $invoice->adresse?->zipcode }} {{ $invoice->adresse?->city }}
                              </p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="h6">{{ __('general_content.payment_method_trans_key') }}</h3>
                            <p class="mb-0">{{ $invoice->payment_method?->label }}</p>
                            <h3 class="h6 mt-3">{{ __('general_content.delivery_notes_trans_key') }}</h3>
                            <p class="mb-0">{{ $invoice->deliverys_notes }}</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h3 class="h6">{{ __('general_content.factory_trans_key') }}</h3>
                            <p class="mb-0">
                                {{ $Factory->name }}<br>
                                {{ $Factory->address }}<br>
                                {{ $Factory->zipcode }} {{ $Factory->city }}<br>
                                {{ __('general_content.phone_trans_key') }} : {{ $Factory->phone_number }}<br>
                                {{ __('general_content.email_trans_key') }} : {{ $Factory->mail }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
