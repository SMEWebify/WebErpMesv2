@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Companies list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
          New companie
        </button>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-3">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"> Statistiques </h3>
          </div>
          <div class="card-body">
            <canvas id="donutChart" width="400" height="400"></canvas>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"> Latest companie added </h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table m-0">
                <thead>
                <tr>
                  <th>code</th>
                  <th>label</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                  @forelse ($LastComapnies as $LastComapnie)
                  <tr>
                    <td>{{ $LastComapnie->code }}</td>
                    <td>{{ $LastComapnie->label }}</td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('companies.show', ['id' => $LastComapnie->id])}}">
                        <i class="fas fa-folder"></i>
                        View
                      </a>
                    </td>
                  </tr>
                  <!-- /.item -->
                  @empty
                <tr>
                  <td colspan="4">No compnanies</td>
                </tr>
                @endforelse
                </tbody>
              </table>
            </div>
            <!-- /.table-responsive -->
          </div>
        </div>
      </div>
      <div class="col-md-9">
        <div class="card">
          @include('include.alert-result')
          
          @livewire('companies-lines')
        </div>
      </div>
    </div>
  </div>
  <!-- /.card -->
@stop

@section('css')
@stop

@section('js')
<script>
//-------------
//- PIE CHART -
//-------------
var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
    var donutData        = {
      labels: [
          'Client',
          'Prospect',
          'Supplier',
          'Client & Supplier',
      ],
      datasets: [
        {
          data: ["{{ $data['ClientCountRate'] }}","{{ $data['ProspectCountRate'] }}","{{ $data['SupplierCountRate'] }}","{{ $data['ClientSupplierCountRate'] }}"], 
          backgroundColor : ['#f56954', '#00a65a', '#f39c12','#00c0ef',],
        }
      ]
    }
    var donutOptions     = {
      maintainAspectRatio : false,
      responsive : true,
    }
    //Create pie or douhnut chart
    // You can switch between pie and douhnut using the method below.
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: donutOptions
    })

  </script>
@stop