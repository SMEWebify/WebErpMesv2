
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
        
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

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
                    <h2 class="h5 mb-0"><a href="#" class="text-muted"></a> {{__('general_content.order_trans_key') }} #{{ $Order->code }}</h2>
                </div>
                
                <!-- Main content -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Details -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-3 d-flex justify-content-between">
                                    <div>
                                        <span class="me-3">{{ $Order->GetshortCreatedAttribute() }}</span>
                                        <span class="me-3">#{{ $Order->code }}</span>
                                        <span class="me-3"></span>
                                        @if(1 == $Order->statu )  <span class="badge badge-info">{{ __('general_content.open_trans_key') }}</span>@endif
                                        @if(2 == $Order->statu )  <span class="badge badge-warning">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                                        @if(3 == $Order->statu )  <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }}</span>@endif
                                        @if(4 == $Order->statu )  <span class="badge badge-danger">{{ __('general_content.partly_delivered_trans_key') }}</span>@endif
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
                                                <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                                                <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                                            </tr>
                                        </thead>
                                        @forelse($Order->OrderLines as $DocumentLine)
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
                                            <td>
                                                @if(1 == $DocumentLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                                @if(2 == $DocumentLine->delivery_status )  
                                                <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $DocumentLine->id }}"><span class="badge badge-warning">{{ __('general_content.partly_delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }} )</span></a>
                                                @endif
                                                @if(3 == $DocumentLine->delivery_status )  
                                                <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $DocumentLine->id }}"><span class="badge badge-success">{{ __('general_content.delivered_trans_key') }} ({{ $DocumentLine->delivered_qty }} )</span></a>
                                                @endif
                                                @if(4 == $DocumentLine->delivery_status )  <span class="badge badge-primary" >{{ __('general_content.delivered_without_dn_trans_key') }} ({{ $DocumentLine->delivered_qty }} )</span>@endif
                                            
                                                {{-- Modal for delivery detail --}}
                                                <x-adminlte-modal id="modalDeliveryFor{{ $DocumentLine->id }}" title="{{__('general_content.deliverys_notes_list_trans_key') }}" theme="info"
                                                    icon="fas fa-bolt" size='lg' disable-animations>
                                                    <ul>
                                                        @foreach($DocumentLine->DeliveryLines as $deliveryLine)
                                                            <li>
                                                                {{ __('general_content.delivery_notes_trans_key') }}: {{ $deliveryLine->delivery->code }} <br>
                                                                {{ __('general_content.qty_trans_key') }} : {{ $deliveryLine->qty }} <br>
                                                                {{__('general_content.created_at_trans_key') }} : {{ $deliveryLine->GetPrettyCreatedAttribute() }} <br>
                                                                <x-ButtonTextView route="{{ route('guest.delivery.show', ['uuid' => $deliveryLine->delivery->uuid])}}" />
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </x-adminlte-modal></td>
                                            <td>
                                                @if(1 == $DocumentLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.not_invoiced_trans_key') }}</span>@endif
                                                @if(2 == $DocumentLine->invoice_status )  <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>@endif
                                                @if(3 == $DocumentLine->invoice_status )  <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>@endif
                                            </td>
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
                                            <p>{{ $Order->payment_method['label'] }}</p>
                                        </div>
                                        <div class="col-lg-6">
                                            <h3 class="h6">{{ __('general_content.payment_conditions_trans_key') }}</h3>
                                            <p>{{ $Order->payment_condition['label'] }}</p>
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
                        @if ($Order->comment)
                        <!-- Customer Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.comment_trans_key') }}</h3>
                                <p>{{  $Order->comment }}</p>
                            </div>
                        </div>
                        @endif
                        @if ($Order->customer_reference)
                        <!-- Customer Notes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.identifier_trans_key') }}</h3>
                                <p>{{ $Order->customer_reference }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="card mb-4">
                            <!-- Shipping information -->
                            <div class="card-body">
                                <h3 class="h6">{{ __('general_content.delevery_method_trans_key') }}</h3>
                                <strong>{{ $Order->delevery_method['label'] }}</strong>
                                <hr>
                                <h3 class="h6">{{ __('general_content.adress_trans_key') }}</h3>
                                <address>
                                    <strong>{{ $Order->companie['label'] }}</strong><br>
                                    {{ $Order->contact['civility'] }} {{ $Order->contact['first_name'] }} {{ $Order->contact['name'] }}<br>
                                    {{ $Order->adresse['adress'] }}<br>
                                    {{ $Order->adresse['zipcode'] }} {{ $Order->adresse['city'] }}<br>
                                    {{ $Order->adresse['country'] }}<br>
                                </address>
                            </div>
                        </div>
                        <div class="card mb-4">
                            @if($Order->Rating->isEmpty())
                                <form action="{{ route('order.ratings.store') }}" method="POST">
                                    @csrf
                                    <div class="card-body">
                                        <input type="hidden" name="orders_id" value="{{ $Order->id }}" >
                                        <input type="hidden" name="companies_id" value="{{ $Order->companies_id }}" >
                                        <div class="form-group">
                                            <label for="rating">{{ __('general_content.order_rate_trans_key') }}</label>
                                            <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-star-half-alt"></i></span>
                                            </div>
                                            <select name="rating" id="rating" class="form-control">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <x-FormTextareaComment  comment="" />
                                        </div>
                                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                    </div>
                                </form> 
                            @else
                                @php
                                    $Rating = $Order->Rating->toArray();
                                @endphp
                                <div class="card-body">
                                    <label for="rating">{{ __('general_content.order_rate_trans_key') }}</label>
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $Rating[0]['rating'])
                                            <span class="badge badge-warning">&#9733;</span>
                                        @else
                                            <span class="badge badge-info">&#9734;</span>
                                        @endif
                                    @endfor
                                </div>
                            @endif  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
