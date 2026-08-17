@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.stock_trans_key') }}</h1>
@stop

@section('content')
<div class="container-fluid">

  @if($scanError)
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle mr-2"></i>{{ $scanError }}
    </div>
  @endif

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card card-danger card-outline">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>Scanner une étiquette de stock</h3>
        </div>
        <form method="GET" action="{{ route('workshop.stock.scan') }}" autocomplete="off">
          <div class="card-body">
            <p class="text-muted">
              Douchez le code-barres de l'étiquette (n° de mouvement) ou saisissez un n° de traçabilité.
            </p>
            <div class="input-group input-group-lg">
              <input type="text"
                     name="code"
                     class="form-control form-control-lg"
                     style="font-size: 1.6rem; height: 4rem;"
                     placeholder="N° de mouvement ou traçabilité"
                     autofocus>
              <div class="input-group-append">
                <button type="submit" class="btn btn-danger btn-lg px-4">
                  <i class="fas fa-search mr-2"></i>Ouvrir
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-history mr-2"></i>Derniers mouvements</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 6rem;">N°</th>
                <th>Produit</th>
                <th class="text-right">Qté</th>
                <th>Traçabilité</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recent as $move)
                <tr style="cursor: pointer;" onclick="window.location='{{ $move['url'] }}'">
                  <td><span class="badge badge-secondary">{{ $move['id'] }}</span></td>
                  <td>
                    <strong>{{ $move['product_code'] ?? '—' }}</strong>
                    <small class="d-block text-muted">{{ $move['product_label'] }}</small>
                  </td>
                  <td class="text-right">{{ $move['qty'] }}</td>
                  <td>{{ $move['tracability'] ?: '—' }}</td>
                  <td>{{ $move['date'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted p-4">{{ __('general_content.no_data_trans_key') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7 text-center mb-4">
      <a href="{{ route('workshop') }}" class="btn btn-default btn-lg">
        <i class="fas fa-arrow-left mr-2"></i>{{ __('general_content.back_trans_key') }}
      </a>
    </div>
  </div>

</div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $("body").addClass("sidebar-hidden");
        });
    </script>
@stop
