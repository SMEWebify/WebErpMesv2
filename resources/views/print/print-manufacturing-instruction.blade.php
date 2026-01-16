@extends('adminlte::page')

@section('title', 'Print document')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
        <h1>Print document</h1>
        </div>
        <div class="col-sm-6">
        <a class="btn btn-primary btn-sm float-sm-right" href="{{ url()->previous() }}">
            {{ __('general_content.back_trans_key') }}
        </a>
        </div>
    </div>
@stop

@section('right-sidebar')
@stop

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
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-uppercase mt-3">Résumé de fabrication</h5>
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr>
                                        <th>Commande</th>
                                        <td>{{ $Document->code }}</td>
                                        <th>Statut</th>
                                        <td>{{ data_get($Document, 'statu', 'N/A') }}</td>
                                        <th>Priorité</th>
                                        <td>{{ data_get($Document, 'priority', 'N/A') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Client</th>
                                        <td>{{ data_get($Document, 'companie.label', __('general_content.internal_order_trans_key')) }}</td>
                                        <th>Référence client</th>
                                        <td>{{ $Document->customer_reference ?: 'N/A' }}</td>
                                        <th>Date souhaitée</th>
                                        <td>{{ data_get($Document, 'validity_date', 'N/A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Table row -->
                    <div class="row">
                        <div class="col-12 table-responsive">
                        <table class="table table-bordered table-sm">
                            <tbody class="thead-light">
                            @forelse($Document->Lines as $DocumentLine)
                                <tr>
                                    <th rowspan="2" class="align-middle">
                                        {{ $DocumentLine->code }} <br/>
                                        {{ $DocumentLine->label }}
                                    </th>
                                    <th>{{ __('general_content.qty_trans_key') }} : {{ $DocumentLine->qty }} {{ $DocumentLine->Unit['label'] }}</th>
                                    <th>Délai interne :
                                        @if($DocumentLine->internal_delay )
                                            {{ $DocumentLine->internal_delay }}
                                        @else
                                            N/A
                                        @endif
                                    </th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <table class="table table-bordered">
                                            <tr class="thead-light">
                                                <th colspan="8">Données techniques</th>
                                            </tr>
                                            <tr>
                                                <td colspan="2"><strong>Matière</strong> : {{ data_get($DocumentLine->OrderLineDetails, 'material', 'N/A') }}</td>
                                                <td colspan="2"><strong>Epaisseur</strong> : {{ data_get($DocumentLine->OrderLineDetails, 'thickness', 'N/A') }}</td>
                                                <td colspan="2"><strong>Plan</strong> : {{ data_get($DocumentLine->OrderLineDetails, 'cad_file', 'N/A') }}</td>
                                                <td colspan="2"><strong>Finition</strong> : {{ data_get($DocumentLine->OrderLineDetails, 'finishing', 'N/A') }}</td>
                                            </tr>
                                        @forelse($DocumentLine->TechnicalCut as $TechnicalCut)
                                                        <tr>
                                                            <td class="align-middle" rowspan="3">
                                                                {{ __('general_content.sort_trans_key') }}. {{ $TechnicalCut->ordre }}
                                                            </td>
                                                            <td>{{ $TechnicalCut->label }}</td>
                                                            <td>Poste / Machine :</td>
                                                            <td>...............</td>
                                                            <td>{{ __('general_content.total_time_trans_key') }}</td>
                                                            <td>{{ $TechnicalCut->TotalTime() }}</td>
                                                            <td rowspan="3">
                                                                {{ __('general_content.visa_trans_key') }} :
                                                            </td>
                                                            <td rowspan="3">
                                                                {{ __('general_content.comment_trans_key') }} :
                                                            </td>
                                                       
                                                        </tr>
                                                        <tr>
                                                            <td>{{ $TechnicalCut->service['code'] }}</td>
                                                            <td>{{ __('general_content.ressource_trans_key') }} :</td>
                                                            <td></td>
                                                            <td>Temps passé</td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            
                                                            <td class="align-middle">
                                                                {!! DNS1D::getBarcodeHTML(strval($TechnicalCut->id), $Factory->task_barre_code) !!}
                                                            </td>
                                                            <td colspan="4">Consignes / Contrôles :</td>
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
                                                            <td>Qté / Pièce :</td>
                                                            <td>{{ data_get($BOM, 'qty', 'N/A') }}</td>
                                                            <td rowspan="3">
                                                                {{ __('general_content.visa_trans_key') }} :
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{{ $BOM->Component['label'] }}</td>
                                                            <td>Emplacement :</td>
                                                            <td>...............</td>
                                                            <td>Lot matière :</td>
                                                            <td>...............</td>
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
                            </tbody>
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
    //window.addEventListener("load", () => window.print());
  </script>
@stop
