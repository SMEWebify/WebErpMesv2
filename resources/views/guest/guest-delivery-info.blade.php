<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('general_content.delivery_notes_trans_key') }} #{{ $Delivery->code }} - {{ $Factory->name ?? config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    @vite('resources/sass/app.scss')

    <style>
        body { background: #f4f6f9; }
        .guest-card { border: none; border-radius: .75rem; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .guest-navbar { background: #fff; border-bottom: 1px solid #e3e6ea; }
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
                    {{ __('general_content.delivery_notes_trans_key') }} #{{ $Delivery->code }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container pb-5">

        @include('include.alert-result')

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <h1 class="h4 mb-0">{{ __('general_content.delivery_notes_trans_key') }} #{{ $Delivery->code }}</h1>
            <span class="text-muted small">{{ $Delivery->GetshortCreatedAttribute() }}</span>
            @if(1 == $Delivery->statu) <span class="badge bg-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
            @if(2 == $Delivery->statu) <span class="badge bg-success">{{ __('general_content.send_trans_key') }}</span> @endif
        </div>

        <div class="row g-4">

            {{-- Delivery lines --}}
            <div class="col-lg-8">
                <div class="card guest-card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('general_content.order_trans_key') }}</th>
                                        <th>{{ __('general_content.external_id_trans_key') }}</th>
                                        <th>{{ __('general_content.description_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.qty_trans_key') }}</th>
                                        <th>{{ __('general_content.unit_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.delivered_qty_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.remaining_qty_trans_key') }}</th>
                                        <th class="text-center">{{ __('general_content.invoice_status_trans_key') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($Delivery->DeliveryLines as $DocumentLine)
                                    <tr>
                                        <td>
                                            @if($DocumentLine->OrderLine->order->uuid)
                                                <a href="{{ route('guest.order.show', ['uuid' => $DocumentLine->OrderLine->order->uuid]) }}" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>{{ $DocumentLine->OrderLine->order['code'] }}
                                                </a>
                                            @else
                                                {{ $DocumentLine->OrderLine->order['code'] }}
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $DocumentLine->OrderLine['code'] }}</td>
                                        <td>{{ $DocumentLine->OrderLine['label'] }}</td>
                                        <td class="text-center">{{ $DocumentLine->OrderLine['qty'] }}</td>
                                        <td>{{ $DocumentLine->OrderLine->Unit['label'] }}</td>
                                        <td class="text-center fw-medium">{{ $DocumentLine->qty }}</td>
                                        <td class="text-center">{{ $DocumentLine->OrderLine['delivered_remaining_qty'] }}</td>
                                        <td class="text-center">
                                            @if(1 == $DocumentLine->invoice_status) <span class="badge bg-secondary">{{ __('general_content.chargeable_trans_key') }}</span> @endif
                                            @if(2 == $DocumentLine->invoice_status) <span class="badge bg-danger">{{ __('general_content.not_chargeable_trans_key') }}</span> @endif
                                            @if(3 == $DocumentLine->invoice_status) <span class="badge bg-warning text-dark">{{ __('general_content.partly_invoiced_trans_key') }}</span> @endif
                                            @if(4 == $DocumentLine->invoice_status) <span class="badge bg-success">{{ __('general_content.invoiced_trans_key') }}</span> @endif
                                        </td>
                                        <td>
                                            @if($DocumentLine->QualityNonConformity)
                                                <div class="d-flex flex-column gap-1">
                                                    <a class="btn btn-danger btn-sm" href="#">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $DocumentLine->QualityNonConformity->label }}
                                                    </a>
                                                    @if($DocumentLine->QualityNonConformity->statu == 1) <span class="badge bg-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu == 2) <span class="badge bg-warning text-dark">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu == 3) <span class="badge bg-success">{{ __('general_content.validate_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu == 4) <span class="badge bg-danger">{{ __('general_content.canceled_trans_key') }}</span> @endif
                                                </div>
                                            @else
                                                <a class="btn btn-warning btn-sm" href="{{ route('guest.nonConformitie.create', ['uuid' => $Delivery->uuid, 'id' => $DocumentLine->id]) }}">
                                                    <i class="fas fa-exclamation me-1"></i>{{ __('general_content.new_non_conformitie_trans_key') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Factory --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ $Factory->name }}</h6>
                        <div class="row">
                            <div class="col-sm-4">
                                @if($Factory->picture)
                                    <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="Logo" height="48">
                                @endif
                            </div>
                            <div class="col-sm-8">
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

                {{-- Shipping address --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.adress_trans_key') }}</h6>
                        <address class="small mb-0">
                            <strong>{{ $Delivery->companie['label'] }}</strong><br>
                            {{ $Delivery->contact['civility'] }} {{ $Delivery->contact['first_name'] }} {{ $Delivery->contact['name'] }}<br>
                            {{ $Delivery->adresse['adress'] }}<br>
                            {{ $Delivery->adresse['zipcode'] }} {{ $Delivery->adresse['city'] }} {{ $Delivery->adresse['province'] ?? '' }}<br>
                            {{ $Delivery->adresse['country'] }}
                        </address>
                    </div>
                </div>

                @if($Delivery->comment)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.comment_trans_key') }}</h6>
                        <p class="small mb-0">{{ $Delivery->comment }}</p>
                    </div>
                </div>
                @endif

                @if($Delivery->purchases_id)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.suppliers_trans_key') }}</h6>
                        <p class="mb-2">{{ $Delivery->purchase->companie['label'] }}</p>
                        @if($Delivery->tracking_number)
                            <div class="small text-muted mb-1">{{ __('general_content.tracking_number_note_trans_key') }}</div>
                            <div class="fw-medium">{{ $Delivery->tracking_number }}</div>
                        @endif
                    </div>
                </div>
                @endif

            </div>{{-- /col-lg-4 --}}
        </div>{{-- /row --}}
    </div>{{-- /container --}}

    @vite('resources/js/guest.js')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</body>
</html>
