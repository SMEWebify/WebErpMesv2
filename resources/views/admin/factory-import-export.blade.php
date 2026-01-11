@extends('adminlte::page')

@section('title', __('general_content.import_export_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.import_export_trans_key') }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item "><a class="nav-link active" href="#FECExport" data-toggle="tab">{{ __('general_content.export_fec_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#InvoicesExport" data-toggle="tab">{{ __('general_content.invoices_export_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#CustomerImport" data-toggle="tab">{{ __('general_content.customer_import_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#QuotesImport" data-toggle="tab">{{ __('general_content.quotes_import_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#OrderImport" data-toggle="tab">{{ __('general_content.orders_import_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#ProductImport" data-toggle="tab">{{ __('general_content.products_import_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            
            @include('include.alert-result')

            <div class="tab-pane active" id="FECExport">
                @livewire('fec-export-lines')
            </div>
            <div class="tab-pane" id="InvoicesExport">
                @livewire('invoice-export-lines')
            </div>
            <div class="tab-pane" id="CustomerImport">
                <form method="POST" action="{{ route('companies.import') }}" enctype="multipart/form-data">
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
                            <div class="col-4 text-right">
                                <label class="col-form-label" for="companie_header">{{ __('general_content.header_line_ask_trans_key') }}</label>
                            </div>
                            <div class="col-8">
                                <x-adminlte-input-switch name="header" id="companie_header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true"/>
                            </div>
                        </div>
                        @php
                            $fields = [
                                ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'label', 'label' => __('general_content.name_company_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'website', 'label' => __('general_content.web_link_trans_key'), 'icon' => 'fab fa-internet-explorer', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'fbsite', 'label' => 'Facebook', 'icon' => 'fab fa-facebook-square', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'twittersite', 'label' => 'X', 'icon' => 'fab fa-twitter-square', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'lkdsite', 'label' => 'Linkedin', 'icon' => 'fab fa-linkedin', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'siren', 'label' => 'Siren', 'icon' => 'fas fa-hashtag', 'color' => 'bg-blue', 'required' => false],
                                ['name' => 'naf_code', 'label' => 'Naf Code', 'icon' => 'fas fa-hashtag', 'color' => 'bg-blue', 'required' => false],
                                ['name' => 'intra_community_vat', 'label' => __('general_content.vat_number_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-blue', 'required' => false],
                                ['name' => 'discount', 'label' => __('general_content.discount_trans_key'), 'icon' => 'fas fa-percentage', 'color' => 'bg-green', 'required' => false],
                            ];
                        @endphp
                        @foreach ($fields as $field)
                            <div class="row">
                                <div class="col-4 text-right">
                                    <label class="col-form-label">{{ $field['label'] }}</label>
                                </div>
                                <div class="col-8">
                                    @if($field['required'] == true)
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @else
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                </form>
            </div>
            <div class="tab-pane" id="QuotesImport">
                <form method="POST" action="{{ route('quotes.import') }}" enctype="multipart/form-data">
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
                            <div class="col-4 text-right">
                                <label class="col-form-label" for="quote_header">{{ __('general_content.header_line_ask_trans_key') }}</label>
                            </div>
                            <div class="col-8">
                                <x-adminlte-input-switch name="header" id="quote_header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true"/>
                            </div>
                        </div>
                        @php
                            $fields = [
                                ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'label', 'label' => __('general_content.label_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'customer_reference', 'label' => __('general_content.customer_reference_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-green', 'required' => false],
                                ['name' => 'companies_id', 'label' => __('general_content.id_customer_trans_key'), 'icon' => 'fas fa-building', 'color' => 'bg-purple', 'required' => true],
                                ['name' => 'validity_date', 'label' => __('general_content.validity_date_trans_key'), 'icon' => 'fas fa-calendar-alt', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'comment', 'label' => __('general_content.comment_trans_key'), 'icon' => 'fas fa-comment', 'color' => 'bg-gray', 'required' => false],
                            ];
                        @endphp
                        @foreach ($fields as $field)
                            <div class="row">
                                <div class="col-4 text-right">
                                    <label class="col-form-label">{{ $field['label'] }}</label>
                                </div>
                                <div class="col-8">
                                    @if($field['required'] == true)
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @else
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                </form>
            </div>
            <div class="tab-pane" id="OrderImport">
                <form method="POST" action="{{ route('orders.import') }}" enctype="multipart/form-data">
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
                            <div class="col-4 text-right">
                                <label class="col-form-label" for="order_header">{{ __('general_content.header_line_ask_trans_key') }}</label>
                            </div>
                            <div class="col-8">
                                <x-adminlte-input-switch name="header" id="order_header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true"/>
                            </div>
                        </div>
                        @php
                            $fields = [
                                ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'label', 'label' => __('general_content.label_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                                ['name' => 'customer_reference', 'label' => __('general_content.customer_reference_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-green', 'required' => false],
                                ['name' => 'companies_id', 'label' => __('general_content.id_customer_trans_key'), 'icon' => 'fas fa-building', 'color' => 'bg-purple', 'required' => true],
                                ['name' => 'validity_date', 'label' => __('general_content.validity_date_trans_key'), 'icon' => 'fas fa-calendar-alt', 'color' => 'bg-yellow', 'required' => false],
                                ['name' => 'comment', 'label' => __('general_content.comment_trans_key'), 'icon' => 'fas fa-comment', 'color' => 'bg-gray', 'required' => false],
                            ];
                        @endphp
                        @foreach ($fields as $field)
                            <div class="row">
                                <div class="col-4 text-right">
                                    <label class="col-form-label">{{ $field['label'] }}</label>
                                </div>
                                <div class="col-8">
                                    @if($field['required'] == true)
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @else
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                </form>
            </div>
            <div class="tab-pane" id="ProductImport">
                <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
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
                            <div class="form-group col-md-4">
                                <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                                    </div>
                                    <select class="form-control" name="methods_services_id" id="methods_services_id" required>
                                        <option value="">{{ __('general_content.select_service_trans_key') }}</option>
                                        @forelse ($ServicesSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                </div>
                                @error('methods_services_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="methods_families_id">{{ __('general_content.select_family_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-grip-horizontal"></i></span>
                                    </div>
                                    <select class="form-control"  name="methods_families_id" id="methods_families_id" required>
                                        <option value="">{{ __('general_content.family_trans_key') }}</option>
                                        @forelse ($FamiliesSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_family_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                </div>
                                @error('methods_families_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="methods_units_id">{{ __('general_content.unit_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                                    </div>
                                    <select class="form-control" name="methods_units_id" id="methods_units_id" required>
                                        <option value="">{{ __('general_content.select_unit_trans_key') }}</option>
                                        @forelse ($UnitsSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_unit_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                </div>
                                @error('methods_units_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="purchased">{{ __('general_content.purchased_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                    </div>
                                    <select class="form-control" name="purchased" id="purchased">
                                        <option value="">{{ __('general_content.select_statu_trans_key') }}</option>
                                        <option value="2">{{ __('general_content.no_trans_key') }}</option>
                                        <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                                    </select>
                                </div>
                                @error('purchased') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="sold">{{ __('general_content.sold_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                    </div>
                                    <select class="form-control" name="sold" id="sold">
                                        <option value="">{{ __('general_content.select_statu_trans_key') }}</option>
                                        <option value="2">{{ __('general_content.no_trans_key') }}</option>
                                        <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                                    </select>
                                </div>
                                @error('sold') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 text-right">
                                <label class="col-form-label" for="product_header">{{ __('general_content.header_line_ask_trans_key') }}</label>
                            </div>
                            <div class="col-8">
                                <x-adminlte-input-switch name="header" id="product_header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true"/>
                            </div>
                        </div>
                        @php
                            $fields = [
                                    ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'type' => 'text', 'required' => true],
                                    ['name' => 'label', 'label' => __('general_content.label_trans_key'), 'icon' => 'fas fa-tag', 'color' => 'bg-blue', 'type' => 'text', 'required' => true],
                                    ['name' => 'ind', 'label' => __('general_content.index_trans_key'), 'icon' => 'fas fa-industry', 'color' => 'bg-blue', 'type' => 'text', 'required' => false],
                                    ['name' => 'purchased_price', 'label' => __('general_content.purchased_price_trans_key'), 'icon' => 'fas fa-cash-register', 'color' => 'bg-purple', 'type' => 'number','required' => false],
                                    ['name' => 'selling_price', 'label' => __('general_content.price_trans_key'), 'icon' => 'fas fa-cash-register', 'color' => 'bg-purple', 'type' => 'number','required' => false],
                                    ['name' => 'material', 'label' => __('general_content.material_trans_key'), 'icon' => 'fas fa-box', 'color' => 'bg-teal', 'type' => 'text', 'required' => false],
                                    ['name' => 'thickness', 'label' => __('general_content.thickness_trans_key'), 'icon' => 'fas fa-ruler', 'color' => 'bg-yellow', 'type' => 'number', 'required' => false],
                                    ['name' => 'weight', 'label' => __('general_content.weight_trans_key'), 'icon' => 'fas fa-weight', 'color' => 'bg-green', 'type' => 'number', 'required' => false],
                                    ['name' => 'finishing', 'label' => __('general_content.finishing_trans_key'), 'icon' => 'fab fa-mdb', 'color' => 'bg-green', 'type' => 'text', 'required' => false],
                                    ['name' => 'x_size', 'label' => __('general_content.x_size_trans_key'), 'icon' => 'fas fa-ruler-horizontal', 'color' => 'bg-orange', 'type' => 'number', 'required' => false],
                                    ['name' => 'y_size', 'label' => __('general_content.y_size_trans_key'), 'icon' => 'fas fa-ruler-vertical', 'color' => 'bg-orange', 'type' => 'number', 'required' => false],
                                    ['name' => 'z_size', 'label' => __('general_content.z_size_trans_key'), 'icon' => 'fas fa-ruler', 'color' => 'bg-orange', 'type' => 'number', 'required' => false],
                                    ['name' => 'x_oversize', 'label' => __('general_content.x_oversize_trans_key'), 'icon' => 'fas fa-expand-arrows-alt', 'color' => 'bg-pink', 'type' => 'number', 'required' => false],
                                    ['name' => 'y_oversize', 'label' => __('general_content.y_oversize_trans_key'), 'icon' => 'fas fa-expand-arrows-alt', 'color' => 'bg-pink', 'type' => 'number', 'required' => false],
                                    ['name' => 'z_oversize', 'label' => __('general_content.z_oversize_trans_key'), 'icon' => 'fas fa-expand-arrows-alt', 'color' => 'bg-pink', 'type' => 'number', 'required' => false],
                                    ['name' => 'comment', 'label' => __('general_content.comment_trans_key'), 'icon' => 'fas fa-comment', 'color' => 'bg-gray', 'type' => 'text', 'required' => false],
                                    ['name' => 'qty_eco_min', 'label' => __('general_content.quantite_eco_min_trans_key'), 'icon' => 'fas fa-cube', 'color' => 'bg-lightblue', 'type' => 'number', 'required' => false],
                                    ['name' => 'qty_eco_max', 'label' => __('general_content.quantite_eco_max_trans_key'), 'icon' => 'fas fa-cubes', 'color' => 'bg-lightblue', 'type' => 'number', 'required' => false],
                                    ['name' => 'diameter', 'label' => __('general_content.diameter_trans_key'), 'icon' => 'fas fa-circle', 'color' => 'bg-blue', 'type' => 'number', 'required' => false],
                                    ['name' => 'diameter_oversize', 'label' => __('general_content.diameter_oversize_trans_key'), 'icon' => 'fas fa-circle-notch', 'color' => 'bg-blue', 'type' => 'number', 'required' => false],
                                    ['name' => 'section_size', 'label' => __('general_content.section_size_trans_key'), 'icon' => 'fas fa-arrows-alt-h', 'color' => 'bg-yellow', 'type' => 'number', 'required' => false],
                                ];
                        @endphp
                        @foreach ($fields as $field)
                            <div class="row">
                                <div class="col-4 text-right">
                                    <label class="col-form-label">{{ $field['label'] }}</label>
                                </div>
                                <div class="col-8">
                                    @if($field['required'] == true)
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @else
                                        <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                            <x-slot name="appendSlot">
                                                <div class="input-group-text {{ $field['color'] }}">
                                                    <i class="{{ $field['icon'] }}"></i>
                                                </div>
                                            </x-slot>
                                        </x-adminlte-input>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
@stop
