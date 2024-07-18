@extends('adminlte::page')

@section('title', __('general_content.import_export_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.import_export_trans_key') }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills"><li class="nav-item "><a class="nav-link active" href="#CustomerImport" data-toggle="tab">{{ __('general_content.customer_import_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="CustomerImport">
                @include('include.alert-result')
                <x-adminlte-card title="{{ __('general_content.choose_file_trans_key') }}" theme="primary" maximizable>
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
                                <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.name_company_trans_key') }}</label></div>
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
                                <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.web_link_trans_key') }}</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="website" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">Facebook</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="fbsite" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">X</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="twittersite" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">Linkedin</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="lkdsite" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">Siren</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="siren" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">Naf Code</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="naf_code" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
                                                <i class="fas fa-hashtag"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.vat_number_trans_key') }}</label></div>
                                <div class="col-8">
                                    <x-adminlte-input name="intra_community_vat" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                        <x-slot name="appendSlot">
                                            <div class="input-group-text bg-blue">
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
                        </div>
                        <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                        </div>
                    </form>
                </x-adminlte-card>
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