@extends('adminlte::page')

@section('title', __('supplier_ack.missing_title'))

@section('content_header')
    <h1>{{ __('supplier_ack.missing_title') }}</h1>
@stop

@section('content')
  <x-InfocalloutComponent :note="__('supplier_ack.missing_info', ['days' => $days])" />
  <div class="card mb-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ __('general_content.purchase_order_trans_key') }}</th>
              <th>{{ __('general_content.supplier_trans_key') }}</th>
              <th>{{ __('supplier_ack.ordered_at') }}</th>
              <th class="text-end">{{ __('supplier_ack.waiting_days') }}</th>
              <th class="text-end">{{ __('supplier_ack.lines_without_ack') }}</th>
              <th>{{ __('supplier_ack.next_requested_date') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($purchases as $purchase)
              <tr>
                <td>
                  <a href="{{ $purchase['url'] }}" class="fw-bold">{{ $purchase['code'] }}</a>
                  @if (filled($purchase['label']) && $purchase['label'] !== $purchase['code'])
                    <div class="small text-body-secondary">{{ $purchase['label'] }}</div>
                  @endif
                </td>
                <td>{{ $purchase['supplier'] }}</td>
                <td>{{ \Carbon\Carbon::parse($purchase['ordered_at'])->format('d/m/Y') }}</td>
                <td class="text-end">
                  <span class="badge {{ $purchase['days'] >= 2 * max(1, $days) ? 'text-bg-danger' : 'text-bg-warning' }}">
                    {{ trans_choice('supplier_ack.days', $purchase['days'], ['count' => $purchase['days']]) }}
                  </span>
                </td>
                <td class="text-end">{{ $purchase['awaiting_lines_count'] }} / {{ $purchase['lines_count'] }}</td>
                <td>{{ $purchase['next_delivery_date'] ? \Carbon\Carbon::parse($purchase['next_delivery_date'])->format('d/m/Y') : '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-body-secondary py-4">{{ __('supplier_ack.missing_none') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@stop
