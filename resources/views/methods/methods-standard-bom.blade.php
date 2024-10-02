@extends('adminlte::page')

@section('title', __('general_content.standard_bom_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.standard_bom_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.standard_bom_trans_key') }}" theme="purple" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th></th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($MethodsStandardNomenclatures as $MethodsStandardNomenclature)
              <tr>
                <td>#{{ $MethodsStandardNomenclature->id }}</td>
                <td>{{ $MethodsStandardNomenclature->code }}</td>
                <td>{{ $MethodsStandardNomenclature->label }}</td>
                <td>{{ $MethodsStandardNomenclature->getAllTaskCountAttribute() }}</td>
                <td class="py-0 align-middle">
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('task.manage', ['id_type'=> 'nomenclature_lines_id', 'id_page'=>  $MethodsStandardNomenclature->id, 'id_line' => $MethodsStandardNomenclature->id])}}" class="btn bg-primary"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                  </div>                    
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsStandardNomenclature{{ $MethodsStandardNomenclature->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $MethodsStandardNomenclature->id }} -->
                  <x-adminlte-modal id="MethodsStandardNomenclature{{ $MethodsStandardNomenclature->id }}" title="Update {{ $MethodsStandardNomenclature->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.standard.nomenclature.update', ['id' => $MethodsStandardNomenclature->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsStandardNomenclature->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <x-FormTextareaComment  comment="{{ $MethodsStandardNomenclature->comment }}" />
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th></th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-adminlte-card>
    </div>
    <div class="col-md-6">
      <form  method="POST" action="{{ route('methods.standard.nomenclature.create') }}" class="form-horizontal" enctype="multipart/form-data">
        <x-adminlte-card title="{{ __('general_content.new_standard_bom_trans_key') }}" theme="secondary" maximizable>
        @csrf
          <div class="form-group">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              </div>
              <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
            </div>
          </div>
          <div class="form-group">
            <label for="label">{{__('general_content.label_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
              </div>
              <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
            </div>
          </div>
          <div class="form-group">
            <x-FormTextareaComment  comment="" />
          </div>
          <div class="card-footer">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </div>
        </x-adminlte-card>
      </form>
    </div>
  </div>
  <!-- /.row -->
@stop

@section('css')
@stop

@section('js')
@stop