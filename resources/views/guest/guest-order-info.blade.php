<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('general_content.order_trans_key') }} #{{ $Order->code }} — {{ $Factory->name ?? config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    @vite('resources/sass/app.scss')

    <style>
        body { background: #f4f6f9; }
        .guest-card { border: none; border-radius: .75rem; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .guest-navbar { background: #fff; border-bottom: 1px solid #e3e6ea; }
        .badge { font-size: .75rem; }
    </style>
</head>
<body>

    {{-- Navbar --}}
    <nav class="guest-navbar py-2 mb-4">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    @if($Factory->picture)
                        <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="{{ $Factory->name }}" height="36">
                    @endif
                    <span class="fw-semibold text-dark">{{ $Factory->name ?? config('app.name') }}</span>
                </div>
                <span class="text-muted small">
                    {{ __('general_content.order_trans_key') }} #{{ $Order->code }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container pb-5">

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <h1 class="h4 mb-0">{{ __('general_content.order_trans_key') }} #{{ $Order->code }}</h1>
            <span class="text-muted small">{{ $Order->GetshortCreatedAttribute() }}</span>
            @if(1 == $Order->statu) <span class="badge bg-info">{{ __('general_content.open_trans_key') }}</span> @endif
            @if(2 == $Order->statu) <span class="badge bg-warning text-dark">{{ __('general_content.in_progress_trans_key') }}</span> @endif
            @if(3 == $Order->statu) <span class="badge bg-success">{{ __('general_content.delivered_trans_key') }}</span> @endif
            @if(4 == $Order->statu) <span class="badge bg-danger">{{ __('general_content.partly_delivered_trans_key') }}</span> @endif
        </div>

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Order lines --}}
                <div class="card guest-card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('general_content.description_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.qty_trans_key') }}</th>
                                        <th class="text-end">{{ __('general_content.price_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.discount_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.vat_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.delivery_status_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.invoice_status_trans_key') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($Order->OrderLines as $DocumentLine)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $DocumentLine->label }}</div>
                                            <div class="small text-muted">{{ $DocumentLine->code }}</div>
                                            @php
                                                $guestDetail = $DocumentLine->OrderLineDetails ?? null;
                                                $guestCustomRequirements = $guestDetail ? collect($guestDetail->custom_requirements ?? [])->filter(fn($r) => !empty($r['label'] ?? null) || !empty($r['value'] ?? null)) : collect();
                                            @endphp
                                            @if($guestCustomRequirements->isNotEmpty())
                                                <ul class="list-unstyled mb-0 small text-muted mt-1">
                                                    @foreach($guestCustomRequirements as $requirement)
                                                        <li><strong>{{ $requirement['label'] ?? __('Requirement') }}:</strong> {{ $requirement['value'] ?? '' }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</td>
                                        <td class="text-end">{{ number_format((float) $DocumentLine->selling_price, 2, '.', '') }} {{ $Factory->curency }}</td>
                                        <td class="text-center">{{ $DocumentLine->discount }} %</td>
                                        <td class="text-center">{{ $DocumentLine->VAT['rate'] }} %</td>
                                        <td class="text-center">
                                            @if(1 == $DocumentLine->delivery_status)
                                                <span class="badge bg-secondary">{{ __('general_content.not_delivered_trans_key') }}</span>
                                            @endif
                                            @if(2 == $DocumentLine->delivery_status)
                                                <button type="button" class="badge bg-warning text-dark border-0" data-bs-toggle="modal" data-bs-target="#modalDeliveryFor{{ $DocumentLine->id }}">
                                                    {{ __('general_content.partly_delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }})
                                                </button>
                                            @endif
                                            @if(3 == $DocumentLine->delivery_status)
                                                <button type="button" class="badge bg-success border-0" data-bs-toggle="modal" data-bs-target="#modalDeliveryFor{{ $DocumentLine->id }}">
                                                    {{ __('general_content.delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }})
                                                </button>
                                            @endif
                                            @if(4 == $DocumentLine->delivery_status)
                                                <span class="badge bg-primary">{{ __('general_content.delivered_without_dn_trans_key') }} ({{ $DocumentLine->delivered_qty }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(1 == $DocumentLine->invoice_status) <span class="badge bg-secondary">{{ __('general_content.not_invoiced_trans_key') }}</span> @endif
                                            @if(2 == $DocumentLine->invoice_status) <span class="badge bg-warning text-dark">{{ __('general_content.partly_invoiced_trans_key') }}</span> @endif
                                            @if(3 == $DocumentLine->invoice_status) <span class="badge bg-success">{{ __('general_content.invoiced_trans_key') }}</span> @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">{{ __('general_content.sub_total_trans_key') }}</td>
                                        <td colspan="3" class="text-end fw-medium">{{ number_format((float) $subPrice, 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @forelse($vatPrice as $key => $value)
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">{{ __('general_content.tax_trans_key') }} {{ number_format((float) $vatPrice[$key][0], 2, '.', '') }} %</td>
                                        <td colspan="3" class="text-end">{{ number_format((float) $vatPrice[$key][1], 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @empty
                                    @endforelse
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">{{ __('general_content.total_trans_key') }}</td>
                                        <td colspan="3" class="text-end fw-bold">{{ number_format((float) $totalPrices, 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase text-muted small fw-bold mb-3">{{ __('general_content.payment_methods_trans_key') }}</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">{{ __('general_content.payment_methods_trans_key') }}</div>
                                <div>{{ $Order->payment_method['label'] }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">{{ __('general_content.payment_conditions_trans_key') }}</div>
                                <div>{{ $Order->payment_condition['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Factory --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase text-muted small fw-bold mb-3">{{ $Factory->name }}</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                @if($Factory->picture)
                                    <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="Logo" height="48" class="mb-2">
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <address class="small mb-0">
                                    {{ $Factory->address }}<br>
                                    {{ $Factory->zipcode }} {{ $Factory->city }}<br>
                                    <i class="fas fa-phone fa-fw text-muted"></i> {{ $Factory->phone_number }}<br>
                                    <i class="fas fa-envelope fa-fw text-muted"></i> {{ $Factory->mail }}
                                </address>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">

                {{-- Delivery --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('general_content.delevery_method_trans_key') }}</h6>
                        <div class="fw-medium mb-3">{{ $Order->delevery_method['label'] }}</div>
                        <hr class="my-2">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.adress_trans_key') }}</h6>
                        <address class="small mb-0">
                            <strong>{{ $Order->companie['label'] }}</strong><br>
                            {{ $Order->contact['civility'] }} {{ $Order->contact['first_name'] }} {{ $Order->contact['name'] }}<br>
                            {{ $Order->adresse['adress'] }}<br>
                            {{ $Order->adresse['zipcode'] }} {{ $Order->adresse['city'] }} {{ $Order->adresse['province'] ?? '' }}<br>
                            {{ $Order->adresse['country'] }}
                        </address>
                    </div>
                </div>

                @if($Order->customer_reference)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <div class="small text-muted mb-1">{{ __('general_content.identifier_trans_key') }}</div>
                        <div class="fw-medium">{{ $Order->customer_reference }}</div>
                    </div>
                </div>
                @endif

                @if($Order->comment)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.comment_trans_key') }}</h6>
                        <p class="mb-0 small">{{ $Order->comment }}</p>
                    </div>
                </div>
                @endif

                {{-- Rating --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        @if($Order->Rating->isEmpty())
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('general_content.order_rate_trans_key') }}</h6>
                            <form action="{{ route('order.ratings.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="orders_id" value="{{ $Order->id }}">
                                <input type="hidden" name="companies_id" value="{{ $Order->companies_id }}">
                                <div class="mb-3">
                                    <label class="form-label small">{{ __('general_content.order_rate_trans_key') }}</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-star-half-alt"></i></span>
                                        <select name="rating" class="form-select">
                                            @foreach([1,2,3,4,5] as $r)
                                                <option value="{{ $r }}">{{ $r }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <x-FormTextareaComment comment="" />
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="fas fa-save me-1"></i>{{ __('general_content.submit_trans_key') }}
                                </button>
                            </form>
                        @else
                            @php $Rating = $Order->Rating->toArray(); @endphp
                            <div class="small text-muted mb-1">{{ __('general_content.order_rate_trans_key') }}</div>
                            <div>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $Rating[0]['rating'] ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /col-lg-4 --}}
        </div>{{-- /row --}}
    </div>{{-- /container --}}

    {{-- Delivery modals --}}
    @foreach($Order->OrderLines as $DocumentLine)
    <div class="modal fade" id="modalDeliveryFor{{ $DocumentLine->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('general_content.deliverys_notes_list_trans_key') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($DocumentLine->DeliveryLines->isEmpty())
                        <p class="text-muted text-center py-3">{{ __('general_content.no_data_trans_key') }}</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($DocumentLine->DeliveryLines as $deliveryLine)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="small">
                                        <div><strong>{{ __('general_content.delivery_notes_trans_key') }}:</strong> {{ $deliveryLine->delivery->code }}</div>
                                        <div><strong>{{ __('general_content.qty_trans_key') }}:</strong> {{ $deliveryLine->qty }}</div>
                                        <div><strong>{{ __('general_content.created_at_trans_key') }}:</strong> {{ $deliveryLine->GetPrettyCreatedAttribute() }}</div>
                                    </div>
                                    <a href="{{ route('guest.delivery.show', ['uuid' => $deliveryLine->delivery->uuid]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>{{ __('general_content.view_trans_key') ?? 'Voir' }}
                                    </a>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('general_content.close_trans_key') ?? 'Fermer' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @vite('resources/js/guest.js')
</body>
</html>
