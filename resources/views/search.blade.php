@extends('adminlte::page')

@section('title', __('general_content.search_results_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.search_results_trans_key') }}</h1>
@stop



@section('content')
    <div class="card">
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                @php $Table =''  @endphp
                    @forelse ($results as $result)
                    @if( $result->getTable() != $Table) 
                    <tr>
                    <th colspan="4" class="bg-secondary disabled color-palette">{{ $result->getTable() }}</th>
                    @php $Table = $result->getTable()  @endphp
                    </tr>
                    @endif
                    <tr>
                        <td>{{ $result->code }}</td>
                        <td>{{ $result->label }}</td>
                        <td>{{ $result->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route( $result->getTable() .'.show', ['id' => $result->id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop