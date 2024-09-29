@extends('adminlte::page')

@section('title', __('general_content.quote_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="{{ __('general_content.quote_trans_key') }} : {{  $Quote->code }}" previous="{{ $previousUrl }}" list="{{ route('quotes') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Quote" data-toggle="tab">{{ __('general_content.quote_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.quote_line_trans_key') }} ({{ count($Quote->QuoteLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Construction" data-toggle="tab">{{ __('general_content.construction_site_trans_key') }} <span class="badge badge-danger right">Beta</span></a></li>
      <li class="nav-item"><a class="nav-link" href="#Charts" data-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Views" data-toggle="tab">{{ __('general_content.guest_page_trans_key') }} ( {{  $Quote->visitsCount() }} )</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Quote">
        @livewire('arrow-steps.arrow-quote', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('quotes.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf 
                <div class="row">
                  <div class="form-group col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Quote->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Quote->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_quote_trans_key'), 'Value' =>  $Quote->label])
                  </div>
                </div>
                  @if($Quote->companie['active'] == 1)
                  <div class="row">
                    <label for="companies_id" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                        @if($Quote->opportunities_id)
                        {{ __('general_content.companie_trans_key') }} :  <x-CompanieButton id="{{ $Quote->companie['id'] }}" label="{{ $Quote->companie['label'] }}"  />
                          <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                          @else
                              @include('include.form.form-select-companie',['companiesId' =>  $Quote->companies_id])
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                      @include('include.form.form-input-customerInfo',['customerReference' =>  $Quote->customer_reference])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-adress',['adressId' =>   $Quote->companies_addresses_id])
                    </div>
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-contact',['contactId' =>   $Quote->companies_contacts_id])
                    </div>
                  </div>
                @else
                <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                <input type="hidden" name="customer_reference" value="{{ $Quote->customer_reference }}">
                <input type="hidden" name="companies_addresses_id" value="{{ $Quote->companies_addresses_id }}">
                <input type="hidden" name="companies_contacts_id" value="{{ $Quote->companies_contacts_id }}">
                <x-adminlte-alert theme="info" title="Info">
                  The customer <x-CompanieButton id="{{ $Quote->companie['id'] }}" label="{{ $Quote->companie['label'] }}"  /> is currently disabled, you cannot change the you cannot change the customer name, contact and address.
                </x-adminlte-alert>
                @endif
                  <div class="row">
                    <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Quote->accounting_payment_conditions_id])
                    </div>
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Quote->accounting_payment_methods_id])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Quote->accounting_deliveries_id])
                    </div>
                    <div class="form-group col-md-6">
                      <label for="validity_date">{{ __('general_content.validity_date_trans_key') }}</label>
                      <div class="input-group">
                        <div class="input-group-text bg-gradient-secondary">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                      </div>
                    </div>
                  </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Quote->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            @if($Quote->opportunities_id)
              <x-adminlte-card title="{{ __('general_content.historical_trans_key') }}" theme="info" maximizable>
                <div class="text-muted">
                  <h3>{{__('general_content.opportunity_trans_key')}} #{{ $Quote->opportunities->label }} </h3><x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Quote->opportunities_id])}}" />
                  
                  <p class="text-sm">{{ __('general_content.user_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->UserManagement['name'] }}</b>
                  </p>
                  <p class="text-sm">{{ __('general_content.probality_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->probality }} %</b> 
                  </p>
                  <p class="text-sm">{{ __('general_content.budget_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->budget }}</b>
                  </p>
                </div>
              </x-adminlte-card>
            @endif
            
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
              @include('include.sub-total-price')
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <table class="table table-hover">
                <tr>
                  <td style="width:50%">{{ __('general_content.quote_trans_key') }}</td>
                  <td><x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /></td>
                </tr>
                @if($Quote->uuid)
                <tr>
                  <td style="width:50%">{{ __('general_content.public_link_trans_key') }}</td>
                  <td>
                    <button class="btn btn-info btn-sm" onclick="copyToClipboard('{{ Request::root() }}/guest/quote/{{ $Quote->uuid }}')">
                      <i class="fas fa-copy"></i> {{ __('general_content.copy_trans_key') }} 
                    </button>
                  </td>
                </tr>
                @endif
                
                @forelse($Quote->Orders as $Order)
                <tr>
                    <td style="width:50%"><x-OrderButton id="{{ $Order->id }}" code="{{ $Order->code }}"  /></td>
                    <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                </tr>
                @empty
                <!--<tr>
                  <td colspan="2">
                      {{ __('general_content.no_data_trans_key') }}
                  </td>
                </tr>-->
                @endforelse
              </table>
            </x-adminlte-card>
            @include('include.file-store', ['inputName' => "quotes_id",'inputValue' => $Quote->id,'filesList' => $Quote->files,])
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
        @livewire('quote-line', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu, 'QuoteDelay' => $Quote->validity_date])
      </div>
      <div class="tab-pane " id="Construction">
        <form action="{{ route('quotes.project.estimates', ['id' => $Quote->id]) }}" method="POST" enctype="multipart/form-data">
          @csrf
      
          {{-- Exigences du client --}}
          <div class="form-group">
              <label for="client_requirements">{{ __('general_content.client_requirement_trans_key') }}</label>
              <textarea class="form-control" name="client_requirements" id="client_requirements" placeholder="{{ __('general_content.client_requirement_trans_key') }}">{{ old('client_requirements', $projectEstimate->client_requirements ?? '') }}</textarea>
          </div>
      
          {{-- Afficher Exigences du client sur le PDF --}}
          <div class="form-group">
              <label for="show_client_requirements_on_pdf">{{ __('general_content.show_client_requirements_on_pdf_trans_key') }}</label>
              
              @if(isset($projectEstimate) && $projectEstimate->show_client_requirements_on_pdf == 1)
                  <x-adminlte-input-switch id="show_client_requirements_on_pdf" name="show_client_requirements_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_client_requirements_on_pdf" name="show_client_requirements_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Plan de mise en page --}}
          <div class="form-group">
              <label for="layout_plan">{{ __('general_content.layout_plan_trans_key') }}</label>
              <textarea class="form-control" name="layout_plan" id="layout_plan" placeholder="{{ __('general_content.layout_plan_trans_key') }}">{{ old('layout_plan', $projectEstimate->layout_plan ?? '') }}</textarea>
          </div>
      
          {{-- Afficher Plan de mise en page sur le PDF --}}
          <div class="form-group">
              <label for="show_layout_on_pdf">{{ __('general_content.show_layout_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_layout_on_pdf == 1)
                  <x-adminlte-input-switch id="show_layout_on_pdf" name="show_layout_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_layout_on_pdf" name="show_layout_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Matériaux et Finitions --}}
          <div class="form-group">
              <label for="materials_finishes">{{ __('general_content.materials_finishes_trans_key') }}</label>
              <textarea class="form-control" name="materials_finishes" id="materials_finishes" placeholder="{{ __('general_content.materials_finishes_trans_key') }}">{{ old('materials_finishes', $projectEstimate->materials_finishes ?? '') }}</textarea>
          </div>
      
          {{-- Afficher Matériaux et Finitions sur le PDF --}}
          <div class="form-group">
              <label for="show_materials_on_pdf">{{ __('general_content.show_materials_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_materials_on_pdf == 1)
                  <x-adminlte-input-switch id="show_materials_on_pdf" name="show_materials_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_materials_on_pdf" name="show_materials_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Logistique --}}
          <div class="form-group">
              <label for="logistics">{{ __('general_content.logistics_trans_key') }}</label>
              <textarea class="form-control" name="logistics" id="logistics" placeholder="{{ __('general_content.logistics_trans_key') }}">{{ old('logistics', $projectEstimate->logistics ?? '') }}</textarea>
          </div>
      
          {{-- Coût de la logistique --}}
          <div class="form-group">
              <label for="logistics_cost">{{ __('general_content.logistics_cost_trans_key') }} ({{ $Factory->curency }})</label>
              <input type="number" step="0.01" class="form-control" name="logistics_cost" id="logistics_cost" value="{{ old('logistics_cost', $projectEstimate->logistics_cost ?? '') }}">
          </div>
      
          {{-- Afficher Logistique sur le PDF --}}
          <div class="form-group">
              <label for="show_logistics_on_pdf">{{ __('general_content.show_logistics_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_logistics_on_pdf == 1)
                  <x-adminlte-input-switch id="show_logistics_on_pdf" name="show_logistics_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_logistics_on_pdf" name="show_logistics_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Coordination avec les entrepreneurs --}}
          <div class="form-group">
              <label for="coordination_with_contractors">{{ __('general_content.coordination_with_contractors_trans_key') }}</label>
              <textarea class="form-control" name="coordination_with_contractors" id="coordination_with_contractors" placeholder="{{ __('general_content.coordination_with_contractors_trans_key') }}">{{ old('coordination_with_contractors_trans_key', $projectEstimate->coordination_with_contractors ?? '') }}</textarea>
          </div>
      
          {{-- Coût des entrepreneurs --}}
          <div class="form-group">
              <label for="contractors_cost">{{ __('general_content.contractors_cost_trans_key') }} ({{ $Factory->curency }})</label>
              <input type="number" step="0.01" class="form-control" name="contractors_cost" id="contractors_cost" value="{{ old('contractors_cost', $projectEstimate->contractors_cost ?? '') }}">
          </div>
      
          {{-- Afficher Entrepreneurs sur le PDF --}}
          <div class="form-group">
              <label for="show_contractors_on_pdf">{{ __('general_content.show_contractors_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_contractors_on_pdf == 1)
                  <x-adminlte-input-switch id="show_contractors_on_pdf" name="show_contractors_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_contractors_on_pdf" name="show_contractors_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Gestion des Déchets --}}
          <div class="form-group">
              <label for="waste_management">{{ __('general_content.waste_management_trans_key') }}</label>
              <textarea class="form-control" name="waste_management" id="waste_management" placeholder="{{ __('general_content.waste_management_trans_key') }}">{{ old('waste_management', $projectEstimate->waste_management ?? '') }}</textarea>
          </div>
      
          {{-- Coût de la gestion des déchets --}}
          <div class="form-group">
              <label for="waste_management_cost">{{ __('general_content.waste_management_cost_trans_key') }} ({{ $Factory->curency }})</label>
              <input type="number" step="0.01" class="form-control" name="waste_management_cost" id="waste_management_cost" value="{{ old('waste_management_cost', $projectEstimate->waste_management_cost ?? '') }}">
          </div>
      
          {{-- Afficher Gestion des Déchets sur le PDF --}}
          <div class="form-group">
              <label for="show_waste_on_pdf">{{ __('general_content.show_waste_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_waste_on_pdf == 1)
                  <x-adminlte-input-switch id="show_waste_on_pdf" name="show_waste_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_waste_on_pdf" name="show_waste_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Taxes et Frais Additionnels --}}
          <div class="form-group">
              <label for="taxes_and_fees">{{ __('general_content.taxes_and_fees_trans_key') }}</label>
              <textarea class="form-control" name="taxes_and_fees" id="taxes_and_fees" placeholder="{{ __('general_content.taxes_and_fees_trans_key') }}">{{ old('taxes_and_fees', $projectEstimate->taxes_and_fees ?? '') }}</textarea>
          </div>
      
          {{-- Coût des taxes --}}
          <div class="form-group">
              <label for="taxes_cost">{{ __('general_content.taxes_cost_trans_key') }} ({{ $Factory->curency }})</label>
              <input type="number" step="0.01" class="form-control" name="taxes_cost" id="taxes_cost" value="{{ old('taxes_cost', $projectEstimate->taxes_cost ?? '') }}">
          </div>
      
          {{-- Afficher Taxes sur le PDF --}}
          <div class="form-group">
              <label for="show_taxes_on_pdf">{{ __('general_content.show_taxes_on_pdf_trans_key') }}</label>
              @if(isset($projectEstimate) && $projectEstimate->show_taxes_on_pdf == 1)
                  <x-adminlte-input-switch id="show_taxes_on_pdf" name="show_taxes_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
              @else
                  <x-adminlte-input-switch id="show_taxes_on_pdf" name="show_taxes_on_pdf" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"/>
              @endif
          </div>
      
          {{-- Date de début des travaux --}}
          <div class="form-group">
              <label for="work_start_date">{{ __('general_content.work_start_date_trans_key') }}</label>
              <input type="date" class="form-control" name="work_start_date" id="work_start_date" value="{{ old('work_start_date', $projectEstimate->work_start_date ?? '') }}">
          </div>
      
          {{-- Date de fin des travaux --}}
          <div class="form-group">
              <label for="work_end_date">{{ __('general_content.work_end_date_trans_key') }}</label>
              <input type="date" class="form-control" name="work_end_date" id="work_end_date" value="{{ old('work_end_date', $projectEstimate->work_end_date ?? '') }}">
          </div>
      
          {{-- Marge de manœuvre (jours) --}}
          <div class="form-group">
              <label for="contingency_days">{{ __('general_content.contingency_days_trans_key') }}</label>
              <input type="number" class="form-control" name="contingency_days" id="contingency_days" value="{{ old('contingency_days', $projectEstimate->contingency_days ?? '') }}">
          </div>
      
          <button type="submit" class="btn btn-primary">{{ __('general_content.update_trans_key') }}</button>
        </form>
      
      
      </div>
      <div class="tab-pane" id="Charts">
        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total product time by service') }}" theme="secondary" maximizable>
              <canvas id="productDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total setting time by service') }}" theme="secondary" maximizable>
              <canvas id="settingDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total cost by service') }}" theme="secondary" maximizable>
              <canvas id="CostDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total price by service') }}" theme="secondary" maximizable>
                <canvas id="PriceDonutChart" width="400" height="400"></canvas>
              </x-adminlte-card>
          </div>
        </div>
      </div>  
      <div class="tab-pane " id="Views">
        <x-adminlte-card title="{{ __('general_content.view_count_trans_key') }}" theme="primary" maximizable>
          @forelse($Quote->guestVisits as $visit)
          <p>{{ __('general_content.date_trans_key') }}: {{ $visit->GetPrettyCreatedAttribute() }}</p>
          @empty
          <p>{{ __('general_content.no_data_trans_key') }}</p>
          @endforelse
        </x-adminlte-card>
        @livewire('ChatLive', ['idItem' => $Quote->id, 'Class' => 'Quotes'])
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Quote->id, 'type' => 'quote'])
      </div>
      @endif
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Quote->statu == 1)
        <x-InfocalloutComponent note="{{ __('general_content.csv_quote_info_trans_key') }}"  />

        <form method="POST" action="{{ route('quotes.import', ['idQuote'=>  $Quote->id]) }}" enctype="multipart/form-data">
          <x-adminlte-card title="{{ __('general_content.choose_file_trans_key') }}" theme="primary" maximizable>
              @csrf
              <div class="card-body">
                  {{-- Placeholder, sm size and prepend icon --}}
                  <x-adminlte-input-file name="import_file" igroup-size="sm" placeholder="{{ __('general_content.choose_csv_trans_key') }}">
                      <x-slot name="prependSlot">
                          <div class="input-group-text bg-lightblue">
                              <i class="fas fa-upload"></i>
                          </div>
                      </x-slot>
                  </x-adminlte-input-file>
              </div>
              <div class="card-body">
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label"> {{ __('general_content.header_line_ask_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input-switch name="header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.external_id_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="code" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number">
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.description_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="label" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.qty_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="qty" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.price_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="selling_price" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.discount_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="discount" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-blue">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.delivery_date_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="delivery_date" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-blue">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
          </x-adminlte-card>
        </form>
        @else
        <x-adminlte-alert theme="info" title="Info">
            {{ __('general_content.info_statu_trans_key') }}
        </x-adminlte-alert>
        @endif
      </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->

@stop

@section('css')
@stop

@section('js')
  <script type="text/javascript">
  $('a[href="#Charts"]').on('shown.bs.tab', function () {
    //-------------
    //- PIE CHART 1 -
    //-------------
    var productDonutChartCanvas  = $('#productDonutChart').get(0).getContext('2d')
    var productDonutData         = {
        labels: [
          @foreach ($TotalServiceProductTime as $item)
          "{{ $item[0] }} - {{ $item[1] }}h",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceProductTime as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceProductTime as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var productDonutChart = new Chart(productDonutChartCanvas , {
        type: 'pie',
        data: productDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 2 -
    //-------------
    var settingDonutChartCanvas  = $('#settingDonutChart').get(0).getContext('2d')
    var settingDonutData         = {
        labels: [
          @foreach ($TotalServiceSettingTime as $item)
          "{{ $item[0] }} - {{ $item[1] }}h",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceSettingTime as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceSettingTime as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var settingDonutChart = new Chart(settingDonutChartCanvas , {
        type: 'pie',
        data: settingDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 3 -
    //-------------
    var costDonutChartCanvas  = $('#CostDonutChart').get(0).getContext('2d')
    var costDonutData         = {
        labels: [
          @foreach ($TotalServiceCost as $item)
          "{{ $item[0] }} - {{ $item[1] }}{{ $Factory->curency }}",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceCost as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceCost as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var costDonutChart = new Chart(costDonutChartCanvas , {
        type: 'pie',
        data: costDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 4 -
    //-------------
    var priceDonutChartCanvas  = $('#PriceDonutChart').get(0).getContext('2d')
    var priceDonutData        = {
        labels: [
          @foreach ($TotalServicePrice as $item)
          "{{ $item[0] }} - {{ $item[1] }}{{ $Factory->curency }}",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServicePrice as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServicePrice as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var priceDonutChart = new Chart(priceDonutChartCanvas , {
        type: 'pie',
        data: priceDonutData,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

      $('a[href="#Charts"]').on('shown.bs.tab', function () {
          productDonutChart.update();
          settingDonutChart.update();
          costDonutChart.update();
          priceDonutChart.update();
      });
    });
  </script>

  <script type="text/javascript">
    $('.custom-file-input').on('change',function(){
      // Obtient le nom du fichier sélectionné
      var fileName = $(this).val().split('\\').pop(); 
      // Sélectionne le label correspondant et met à jour son contenu
      $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
  </script>

  <script type="text/javascript">
    $(document).ready(function(){
      $('[data-toggle="tooltip"]').tooltip(); // Active les infobulles Bootstrap pour tous les éléments qui ont l'attribut data-toggle="tooltip"
    });
  </script>

  <script>
    function copyToClipboard(text) {
        // Create a temporary textarea element
        var tempTextarea = document.createElement("textarea");
        tempTextarea.value = text;
        
        // Add it to the document body
        document.body.appendChild(tempTextarea);
        
        // Select the text in the textarea
        tempTextarea.select();
        tempTextarea.setSelectionRange(0, 99999); // For mobile devices
        
        // Copy the text inside the textarea to clipboard
        document.execCommand("copy");
        
        // Remove the temporary textarea
        document.body.removeChild(tempTextarea);
        
        // Optionally, you can show a message indicating that the text has been copied
        // alert("Lien copié dans le presse-papier !");
    }
  </script>
@stop