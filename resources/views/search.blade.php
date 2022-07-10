@extends('adminlte::page')

@section('title', 'Search results')

@section('content_header')
    <h1>Search results</h1>
@stop



@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Label</th>
                        <th>Created At</th>
                        <th>Action</th>
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
                        <x-EmptyDataLine col="4" text="No lines found ..."  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Created At</th>
                    <th>Action</th>
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