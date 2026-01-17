@extends('adminlte::page')

@section('title', __('general_content.compare_rfq_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <h1>{{ __('general_content.compare_rfq_trans_key') }} : {{ $rfqGroup->code }}</h1>
@stop

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">{{ $rfqGroup->label }}</h3>
  </div>
  <div class="card-body">
    <div class="table-responsive p-0">
      <table class="table table-hover table-bordered">
        <thead>
          <tr>
            <th>{{ __('general_content.line_trans_key') }}</th>
            <th>{{ __('general_content.qty_trans_key') }}</th>
            @foreach($quotations as $quotation)
              <th>
                <a href="{{ route('purchases.quotations.show', ['id' => $quotation->id]) }}">
                  {{ $quotation->companie?->label ?? $quotation->code }}
                </a>
                <div class="text-muted">
                  <a href="{{ route('purchases.quotations.show', ['id' => $quotation->id]) }}">
                    {{ $quotation->code }}
                  </a>
                </div>
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($lineGroups as $lineGroup)
            @php
              $lineEntries = collect($lineGroup['lines']);
              $bestPrice = $lineEntries->whereNotNull('unit_price')->min('unit_price');
            @endphp
            <tr>
              <td>{{ $lineGroup['label'] }}</td>
              <td>{{ number_format($lineGroup['qty'], 3, '.', ' ') }}</td>
              @foreach($quotations as $quotation)
                @php
                  $line = $lineGroup['lines'][$quotation->id] ?? null;
                  $isBestPrice = $line && $bestPrice !== null && $line->unit_price == $bestPrice;
                @endphp
                <td @if($isBestPrice) class="table-success" @endif>
                  @if($line)
                    <div><strong>{{ $line->formatted_selling_price }}</strong></div>
                    <div class="text-muted">{{ __('general_content.total_price_trans_key') }}: {{ $line->formatted_total_price }}</div>
                    @if($line->lead_time_days)
                      <div>{{ __('general_content.lead_time_trans_key') }}: {{ $line->lead_time_days }} d</div>
                    @endif
                    @if($line->conditions)
                      <div>{{ $line->conditions }}</div>
                    @endif
                    @if($line->supplier_score)
                      <div>{{ __('general_content.score_trans_key') }}: {{ $line->supplier_score }}</div>
                    @endif
                    @if($line->supplier_comment)
                      <div class="text-muted">{{ $line->supplier_comment }}</div>
                    @endif
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              @endforeach
            </tr>
          @empty
            <tr>
              <td colspan="{{ 2 + $quotations->count() }}">
                {{ __('general_content.no_data_trans_key') }}
              </td>
            </tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr>
            <th>{{ __('general_content.total_price_trans_key') }}</th>
            <th></th>
            @foreach($quotations as $quotation)
              <th>{{ number_format($supplierTotals[$quotation->id] ?? 0, 2, '.', ' ') }}</th>
            @endforeach
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@stop
