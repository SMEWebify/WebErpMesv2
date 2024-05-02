
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- Base Stylesheets --}}
    @if(!config('adminlte.enabled_laravel_mix'))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

        @if(config('adminlte.google_fonts.allowed', true))
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
        @endif
    @else
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


    {{-- Favicon --}}
    @if(config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('favicons/apple-icon-57x57.png') }}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('favicons/apple-icon-60x60.png') }}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('favicons/apple-icon-72x72.png') }}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('favicons/apple-icon-76x76.png') }}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('favicons/apple-icon-114x114.png') }}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('favicons/apple-icon-120x120.png') }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('favicons/apple-icon-144x144.png') }}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('favicons/apple-icon-152x152.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-icon-180x180.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192"  href="{{ asset('favicons/android-icon-192x192.png') }}">
        <link rel="manifest" crossorigin="use-credentials" href="{{ asset('favicons/manifest.json') }}">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    @endif

</head>
    <body>
        <style>
            body{
                background:#eee;
            }
            .card {
                box-shadow: 0 20px 27px 0 rgb(0 0 0 / 5%);
            }
            .card {
                position: relative;
                display: flex;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                background-clip: border-box;
                border: 0 solid rgba(0,0,0,.125);
                border-radius: 1rem;
            }
            .text-reset {
                --bs-text-opacity: 1;
                color: inherit!important;
            }
            a {
                color: #5465ff;
                text-decoration: none;
            }
        </style>

        <div class="container-fluid">
            <div class="container">
                <!-- Title -->
                <div class="d-flex justify-content-between align-items-center py-3">
                    <h2 class="h5 mb-0"><a href="#" class="text-muted"></a> {{__('general_content.quote_trans_key') }} #{{ $Quote->code }}</h2>
                </div>
                
                <!-- Main content -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Details -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3 d-flex justify-content-between">
                                    <div>
                                        <span class="me-3">{{ $Quote->GetshortCreatedAttribute() }}</span>
                                        <span class="me-3">#{{ $Quote->code }}</span>
                                        <span class="me-3"></span>
                                        @if(1 == $Quote->statu )   <span class="badge badge-info"> {{ __('general_content.open_trans_key') }}</span>@endif
                                        @if(2 == $Quote->statu )  <span class="badge badge-warning">{{ __('general_content.send_trans_key') }}</span>@endif
                                        @if(3 == $Quote->statu )  <span class="badge badge-success">{{ __('general_content.win_trans_key') }}</span>@endif
                                        @if(4 == $Quote->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif
                                        @if(5 == $Quote->statu )  <span class="badge badge-secondary">{{ __('general_content.closed_trans_key') }}</span>@endif
                                        @if(6 == $Quote->statu )   <span class="badge badge-secondary">{{ __('general_content.obsolete_trans_key') }}</span>@endif
                                    </div>
                                </div>
                                <table class="table table-borderless">
                                    <tbody>
                                        <thead>
                                            <tr>
                                                <th>{{ __('general_content.description_trans_key') }}</th>
                                                <th >{{ __('general_content.qty_trans_key') }}</th>
                                                <th>{{ __('general_content.price_trans_key') }}</th>
                                                <th>{{ __('general_content.discount_trans_key') }}</th>
                                                <th>{{ __('general_content.vat_trans_key') }}</th>
                                            </tr>
                                        </thead>
                                        @forelse($Quote->QuoteLines as $DocumentLine)
                                        <tr>
                                            <td>
                                                <div class="d-flex mb-2">
                                                    <!--<div class="flex-shrink-0">
                                                        <img src="" alt="" width="35" class="img-fluid">
                                                    </div>-->
                                                    <div class="flex-lg-grow-1 ms-3">
                                                        <h6 class="small mb-0">{{ $DocumentLine->label }}</h6>
                                                        <span style="color: #6c757d">{{ $DocumentLine->code }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</td>
                                            <td class="text-end">{{ $DocumentLine->selling_price }}  {{ $Factory->curency }}</td>
                                            <td>{{ $DocumentLine->discount }} %</td>
                                            <td>{{ $DocumentLine->VAT['rate'] }} %</td>
                                        </tr>
                                        @empty
                                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
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
                                        @forelse($vatPrice as $key => $value)
                                        <tr>
                                            <td colspan="3">{{ __('general_content.tax_trans_key') }} <?= $vatPrice[$key][0] ?> %</td> 
                                            <td colspan="2" class="text-end"><?= $vatPrice[$key][1] ?> {{ $Factory->curency }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3">{{ __('general_content.no_tax_trans_key') }}</td> 
                                            <td colspan="2" class="text-end"> </td>
                                        </tr>
                                        @endforelse
                                        <tr class="fw-bold">
                                            <td colspan="3">{{ __('general_content.total_trans_key') }}</td>
                                            <td colspan="2" class="text-end">{{ $totalPrices }} {{ $Factory->curency }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                </div>
                            </div>
                            <!-- Payment -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h3 class="h6">{{ __('general_content.payment_methods_trans_key') }}</h3>
                                            <p>{{ $Quote->payment_method['label'] }}</p>
                                        </div>
                                        <div class="col-lg-6">
                                            <h3 class="h6">{{ __('general_content.payment_conditions_trans_key') }}</h3>
                                            <p>{{ $Quote->payment_condition['label'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Factory -->
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
                                            <address>
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
                        @if ($Quote->comment)
                        <!-- Customer Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                                <p>{{  $Quote->comment }}</p>
                            </div>
                        </div>
                        @endif
                        @if ($Quote->customer_reference)
                        <!-- Customer Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.identifier_trans_key') }}</h3>
                                <p>{{ $Quote->customer_reference }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="card mb-4">
                            <!-- Shipping information -->
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.delevery_method_trans_key') }}</h3>
                                <strong>{{ $Quote->delevery_method['label'] }}</strong>
                                <hr>
                                <h3 class="h6">{{ __('general_content.adress_trans_key') }}</h3>
                                <address>
                                    <strong>{{ $Quote->companie['label'] }}</strong><br>
                                    {{ $Quote->contact['civility'] }} {{ $Quote->contact['first_name'] }} {{ $Quote->contact['name'] }}<br>
                                    {{ $Quote->adresse['adress'] }}<br>
                                    {{ $Quote->adresse['zipcode'] }} {{ $Quote->adresse['city'] }}<br>
                                    {{ $Quote->adresse['country'] }}<br>
                                </address>
                            </div>
                        </div>
                        @if ( $Factory->cgv_file && $Factory->public_link_cgv != 2)
                        <!-- CGV Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.cgv_trans_key') }}</h3>
                                <a class="btn btn-info btn-sm " href="{{ asset('/cgv/factory/'. $Factory->cgv_file) }}" target="_blank">Show SGV</a>
                            </div>
                        </div>
                        @endif
                        <div class="card mb-4">
                            <div class="card-body">
                                @livewire('ChatLive', ['idItem' => $Quote->id, 'Class' => 'Quotes'])
                            </div>
                        </div>
                    </div>
                </div>
                @if ( $Factory->cgv_file && $Factory->add_cgv_to_pdf != 2)
                <div class="row">
                    <h1>{{ __('general_content.cgv_trans_key') }}</h1>
                    <object data="{{ asset('/cgv/factory/'. $Factory->cgv_file) }}" type="application/pdf" width="100%" height="1000px"></object>
                </div>
                @endif
            </div>
        </div>
    </body>
</html>
