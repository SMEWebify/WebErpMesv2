<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('general_content.quote_trans_key') }} #{{ $Quote->code }} — {{ $Factory->name ?? config('app.name') }}</title>

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
                    {{ __('general_content.quote_trans_key') }} #{{ $Quote->code }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container pb-5">

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <h1 class="h4 mb-0">{{ __('general_content.quote_trans_key') }} #{{ $Quote->code }}</h1>
            <span class="text-muted small">{{ $Quote->GetshortCreatedAttribute() }}</span>
            @if(1 == $Quote->statu) <span class="badge bg-info">{{ __('general_content.open_trans_key') }}</span> @endif
            @if(2 == $Quote->statu) <span class="badge bg-warning text-dark">{{ __('general_content.send_trans_key') }}</span> @endif
            @if(3 == $Quote->statu) <span class="badge bg-success">{{ __('general_content.win_trans_key') }}</span> @endif
            @if(4 == $Quote->statu) <span class="badge bg-danger">{{ __('general_content.lost_trans_key') }}</span> @endif
            @if(5 == $Quote->statu) <span class="badge bg-secondary">{{ __('general_content.closed_trans_key') }}</span> @endif
            @if(6 == $Quote->statu) <span class="badge bg-secondary">{{ __('general_content.obsolete_trans_key') }}</span> @endif
        </div>

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Quote lines --}}
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
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($Quote->QuoteLines as $DocumentLine)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $DocumentLine->label }}</div>
                                            <div class="small text-muted">{{ $DocumentLine->code }}</div>
                                        </td>
                                        <td class="text-center">{{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</td>
                                        <td class="text-end">{{ number_format((float) $DocumentLine->selling_price, 2, '.', '') }} {{ $Factory->curency }}</td>
                                        <td class="text-center">{{ $DocumentLine->discount }} %</td>
                                        <td class="text-center">{{ $DocumentLine->VAT['rate'] }} %</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end text-muted">{{ __('general_content.sub_total_trans_key') }}</td>
                                        <td colspan="2" class="text-end fw-medium">{{ number_format((float) $subPrice, 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @forelse($vatPrice as $key => $value)
                                    <tr>
                                        <td colspan="3" class="text-end text-muted">{{ __('general_content.tax_trans_key') }} {{ number_format((float) $vatPrice[$key][0], 2, '.', '') }} %</td>
                                        <td colspan="2" class="text-end">{{ number_format((float) $vatPrice[$key][1], 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                    @empty
                                    @endforelse
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">{{ __('general_content.total_trans_key') }}</td>
                                        <td colspan="2" class="text-end fw-bold">{{ number_format((float) $totalPrices, 2, '.', '') }} {{ $Factory->curency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('general_content.payment_methods_trans_key') }}</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">{{ __('general_content.payment_methods_trans_key') }}</div>
                                <div>{{ $Quote->payment_method['label'] }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">{{ __('general_content.payment_conditions_trans_key') }}</div>
                                <div>{{ $Quote->payment_condition['label'] }}</div>
                            </div>
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

                {{-- CGV embedded --}}
                @if($Factory->cgv_file && $Factory->add_cgv_to_pdf != 2)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ __('general_content.cgv_trans_key') }}</h6>
                        <object data="{{ asset('/cgv/factory/'.$Factory->cgv_file) }}" type="application/pdf" width="100%" height="600px" class="rounded"></object>
                    </div>
                </div>
                @endif

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">

                {{-- Delivery & address --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.delevery_method_trans_key') }}</h6>
                        <div class="fw-medium mb-3">{{ $Quote->delevery_method['label'] }}</div>
                        <hr class="my-2">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.adress_trans_key') }}</h6>
                        <address class="small mb-0">
                            <strong>{{ $Quote->companie['label'] }}</strong><br>
                            {{ $Quote->contact['civility'] }} {{ $Quote->contact['first_name'] }} {{ $Quote->contact['name'] }}<br>
                            {{ $Quote->adresse['adress'] }}<br>
                            {{ $Quote->adresse['zipcode'] }} {{ $Quote->adresse['city'] }} {{ $Quote->adresse['province'] ?? '' }}<br>
                            {{ $Quote->adresse['country'] }}
                        </address>
                    </div>
                </div>

                @if($Quote->customer_reference)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <div class="small text-muted mb-1">{{ __('general_content.identifier_trans_key') }}</div>
                        <div class="fw-medium">{{ $Quote->customer_reference }}</div>
                    </div>
                </div>
                @endif

                @if($Quote->comment)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.comment_trans_key') }}</h6>
                        <p class="small mb-0">{{ $Quote->comment }}</p>
                    </div>
                </div>
                @endif

                @if($Factory->cgv_file && $Factory->public_link_cgv != 2)
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ __('general_content.cgv_trans_key') }}</h6>
                        <a class="btn btn-outline-primary btn-sm" href="{{ asset('/cgv/factory/'.$Factory->cgv_file) }}" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>{{ __('general_content.cgv_trans_key') }}
                        </a>
                    </div>
                </div>
                @endif

                {{-- Chat --}}
                <div class="card guest-card mb-4">
                    <div class="card-body">
                        <div
                          data-react="chat-live"
                          data-endpoints="{{ json_encode([
                            'list'        => route('guest.chats.index', ['uuid' => $Quote->uuid]),
                            'store'       => route('guest.chats.store', ['uuid' => $Quote->uuid]),
                            'relatedId'   => $Quote->id,
                            'relatedType' => 'Quotes',
                          ]) }}"
                          data-trans="{{ json_encode([
                            'add_trans_key'     => __('general_content.add_trans_key'),
                            'no_data_trans_key' => __('general_content.no_data_trans_key'),
                          ]) }}"
                        ></div>
                    </div>
                </div>

            </div>{{-- /col-lg-4 --}}
        </div>{{-- /row --}}
    </div>{{-- /container --}}

    @viteReactRefresh
    @vite('resources/js/guest.js')
</body>
</html>
