
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
                    <h2 class="h5 mb-0"><a href="#" class="text-muted"></a> {{__('general_content.delivery_notes_trans_key') }} #{{ $Delivery->code }}</h2>
                </div>
                
                @include('include.alert-result')
                
                <!-- Main content -->
                <div class="row">
                    <div class="col-lg-12">
                        <!-- Details -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3 d-flex justify-content-between">
                                    <div>
                                        <span class="me-3">{{ $Delivery->GetshortCreatedAttribute() }}</span>
                                        <span class="me-3">#{{ $Delivery->code }}</span>
                                        <span class="me-3"></span>
                                        @if(1 == $Delivery->statu )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                                        @if(2 == $Delivery->statu )  <span class="badge badge-warning">{{ __('general_content.send_trans_key') }}</span>@endif
                                    </div>
                                </div>
                                <table class="table table-borderless">
                                    <tbody>
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
                                        @forelse($Delivery->DeliveryLines as $DocumentLine)
                                        <tr>
                                            <td>@if( $DocumentLine->OrderLine->order->uuid)<x-ButtonTextView route="{{ route('guest.order.show', ['uuid' => $DocumentLine->OrderLine->order->uuid])}}" />@endif
                                                {{ $DocumentLine->OrderLine->order['code'] }}</td>
                                            <td>{{ $DocumentLine->OrderLine['code'] }}</td>
                                            <td>{{ $DocumentLine->OrderLine['label'] }}</td>
                                            <td>{{ $DocumentLine->OrderLine['qty'] }}</td>
                                            <td>{{ $DocumentLine->OrderLine->Unit['label'] }}</td>
                                            <td>{{ $DocumentLine->qty }}</td>
                                            <td>{{ $DocumentLine->OrderLine['delivered_remaining_qty'] }}</td>
                                            <td>
                                                @if(1 == $DocumentLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.chargeable_trans_key') }}</span>@endif
                                                @if(2 == $DocumentLine->invoice_status )  <span class="badge badge-danger">{{ __('general_content.not_chargeable_trans_key') }}</span>@endif
                                                @if(3 == $DocumentLine->invoice_status )  <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>@endif
                                                @if(4 == $DocumentLine->invoice_status )  <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>@endif
                                            </td>
                                            <td> 
                                                @if($DocumentLine->QualityNonConformity)
                                                    <a class="btn btn-danger btn-sm" href="#">
                                                        <i class="fa fa-light fa-fw  fa-exclamation"></i>
                                                        {{  $DocumentLine->QualityNonConformity->label}}
                                                    </a>
                                                    
                                                    @if($DocumentLine->QualityNonConformity->statu  == 1) <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu  == 2) <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu  == 3) <span class="badge badge-success">{{ __('general_content.validate_trans_key') }}</span> @endif
                                                    @if($DocumentLine->QualityNonConformity->statu  == 4) <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span> @endif
                                                @else
                                                    <a class="btn btn-warning btn-sm" href="{{ route('guest.nonConformitie.create', ['id' => $DocumentLine->id])}}">
                                                        <i class="fa fa-light fa-fw  fa-exclamation"></i>
                                                        {{ __('general_content.new_non_conformitie_trans_key') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse
                                    </tbody>
                                </table>
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
                        @if ($Delivery->comment)
                        <!-- Customer Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                                <p>{{  $Delivery->comment }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="card mb-4">
                            <!-- Shipping information -->
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.adress_trans_key') }}</h3>
                                <address>
                                    <strong>{{ $Delivery->companie['label'] }}</strong><br>
                                    {{ $Delivery->contact['civility'] }} {{ $Delivery->contact['first_name'] }} {{ $Delivery->contact['name'] }}<br>
                                    {{ $Delivery->adresse['adress'] }}<br>
                                    {{ $Delivery->adresse['zipcode'] }} {{ $Delivery->adresse['city'] }}<br>
                                    {{ $Delivery->adresse['country'] }}<br>
                                </address>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
