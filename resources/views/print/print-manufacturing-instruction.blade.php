@extends('adminlte::page')

@section('title', 'Print document')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
        <h1>Print document</h1>
        </div>
        <div class="col-sm-6">
        <a class="btn btn-primary btn-sm" href="{{ url()->previous() }}">
            <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalQuote">
            {{ __('general_content.back_trans_key') }}
            </button>
        </a>
        </div>
    </div>
@stop

@section('right-sidebar')

@section('content')
<div class="container-fluid">
            <div class="row">
                <!-- this row will not appear when printing -->
                
                <div class="col-12">
                    <!-- Main content -->
                    <div class="invoice p-3 mb-3">
                    <!-- title row -->
                    <div class="row">
                        <div class="col-12">
                        <h4><small class="float-right">{{ __('general_content.date_trans_key') }} : {{ date('Y-m-d') }}</small>
                        </h4>
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- info row -->
                    <div class="row invoice-info">
                        @if($Document->type == 1)
                        <x-HeaderPrint  
                            factoryName="{{ $Factory->name }}"
                            factoryAddress="{{ $Factory->address }}"
                            factoryZipcode="{{ $Factory->zipcode }}"
                            factoryCity="{{ $Factory->city }}"
                            factoryPhoneNumber="{{ $Factory->phone_number }}"
                            factoryMail="{{ $Factory->mail }}"

                            
                            companieLabel="{{ $Document->companie['label'] }}"
                            companieCivility="{{ $Document->contact['civility'] }}"
                            companieFirstName="{{ $Document->contact['first_name'] }}"
                            companieName="{{ $Document->contact['name'] }}"
                            companieAdress="{{ $Document->adresse['adress'] }}"
                            companieZipcode="{{ $Document->adresse['zipcode'] }}"
                            companieCity="{{ $Document->adresse['city'] }}"
                            companieCountry="{{ $Document->adresse['country'] }}"
                            companieNumber="{{ $Document->contact['number'] }}"
                            companieMail="{{ $Document->contact['mail'] }}"

                            documentName="{{ $typeDocumentName}}"
                            code="{{ $Document->code }}"
                            customerReference="{{ $Document->customer_reference }}" 
                            />
                            @else
                            <x-HeaderPrint  
                            factoryName="{{ $Factory->name }}"
                            factoryAddress="{{ $Factory->address }}"
                            factoryZipcode="{{ $Factory->zipcode }}"
                            factoryCity="{{ $Factory->city }}"
                            factoryPhoneNumber="{{ $Factory->phone_number }}"
                            factoryMail="{{ $Factory->mail }}"

                            
                            companieLabel="{{__('general_content.internal_order_trans_key') }}"
                            companieCivility=" "
                            companieFirstName=" "
                            companieName=" "
                            companieAdress=" "
                            companieZipcode=" "
                            companieCity=" "
                            companieCountry=" "
                            companieNumber="N/A"
                            companieMail="N/A"

                            documentName="{{ $typeDocumentName}}"
                            code="{{ $Document->code }}"
                            customerReference=" " 
                            />
                            @endif
                        </div>
                    </div>
                    <!-- Table row -->
                    <div class="row">
                        <div class="col-12 table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                            @forelse($Document->Lines as $DocumentLine)
                                <tr>
                                    <th rowspan="2" class="align-middle">
                                        {{ $DocumentLine->code }} <br/>
                                        {{ $DocumentLine->label }}
                                    </th>
                                    <th>{{ __('general_content.qty_trans_key') }} : {{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</th>
                                    <th>Time limit :
                                        @if($DocumentLine->delivery_date )
                                            {{ $DocumentLine->delivery_date }}
                                        @else
                                            N/A
                                        @endif
                                    </th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <table class="table table-bordered">
                                        @forelse($DocumentLine->TechnicalCut as $TechnicalCut)
                                                        <tr>
                                                            <td class="align-middle" rowspan="3">
                                                                {{ __('general_content.sort_trans_key') }}. {{ $TechnicalCut->ordre }}
                                                            </td>
                                                            <td>{{ __('general_content.label_trans_key') }} : {{ $TechnicalCut->label }}</td>
                                                            <td>{{ __('general_content.user_trans_key') }} :</td>
                                                            <td>...............</td>
                                                            <td>{{ __('general_content.total_time_trans_key') }}</td>
                                                            <td>{{ $TechnicalCut->TotalTime() }}</td>
                                                            <td rowspan="3">
                                                                {{ __('general_content.visa_trans_key') }} :
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ $TechnicalCut->service['label'] }}</td>
                                                            <td>{{ __('general_content.ressource_trans_key') }} :</td>
                                                            <td></td>
                                                            <td>Time spent</td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            
                                                            <td class="align-middle">
                                                                {!! DNS1D::getBarcodeHTML(strval($TechnicalCut->id), $Factory->task_barre_code) !!}
                                                            </td>
                                                            <td>{{ $TechnicalCut->service['code'] }}</td>
                                                            <td colspan="6">{{ __('general_content.comment_trans_key') }} :</td>
                                                        </tr>
                                                    
                                        @empty
                                            <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse 

                                        @forelse($DocumentLine->BOM as $BOM)
                                                        <tr>
                                                            <td class="align-middle" rowspan="3">
                                                                {{ __('general_content.sort_trans_key') }}. {{ $BOM->ordre }}
                                                            </td>
                                                            <td>{{ __('general_content.label_trans_key') }} : {{ $BOM->label }}</td>
                                                            <td>{{ __('general_content.supplier_trans_key') }}  :</td>
                                                            <td>...............</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td rowspan="3">
                                                                {{ __('general_content.visa_trans_key') }} :
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ $BOM->Component['label'] }}</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="align-middle">
                                                                {!! DNS1D::getBarcodeHTML(strval($BOM->id), $Factory->task_barre_code) !!}
                                                            </td>
                                                            <td colspan="6">{{ __('general_content.comment_trans_key') }} :</td>
                                                        </tr>
                                                    
                                        @empty
                                            <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse 
                                        </table>
                                    </td>
                                </tr>
                                @empty
                                    <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                                @endforelse
                            </thead>
                        </table>
                    </div>
                    <!-- /.col -->
                </div>
            <!-- /.row -->
            </div>
@stop

@section('css')
@stop

@section('js')
  <script>
    window.addEventListener("load", window.print());
  </script>
@stop