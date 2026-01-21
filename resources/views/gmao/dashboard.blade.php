@extends('adminlte::page')

@section('title', 'GMAO - Dashboard')

@section('content_header')
    <h1>GMAO - Dashboard</h1>
@stop

@section('content')
    <div class="row">
        @foreach($kpis as $kpi)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-adminlte-info-box
                    :title="$kpi['name']"
                    :text="$kpi['description']"
                    :number="$kpi['value']"
                    icon="fas fa-chart-line"
                    theme="info"
                    icon-theme="white"
                />
            </div>
        @endforeach
    </div>

    <x-adminlte-card title="3. 📊 KPI & Indicateurs maintenance" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>KPI</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kpis as $kpi)
                        <tr>
                            <td>{{ $kpi['name'] }}</td>
                            <td>{{ $kpi['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop
