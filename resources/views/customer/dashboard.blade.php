@extends('customer.layouts.app')

@section('title', __('general_content.dashboard_trans_key'))

@section('content')
    <div class="row g-3 mb-4">
        @foreach($notifications as $notification)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">{{ $notification['title'] }}</p>
                                <h4 class="fw-bold mb-0">{{ $notification['value'] }}</h4>
                            </div>
                            <span class="badge bg-{{ $notification['variant'] }}">{{ $notification['subtitle'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
            <div>
                <h5 class="mb-0">{{ __('general_content.orders_trans_key') }}</h5>
                <small class="text-muted">{{ __('general_content.orders_list_trans_key') }}</small>
            </div>
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="invoice_status" value="{{ request('invoice_status') }}">
                <input type="hidden" name="invoice_search" value="{{ request('invoice_search') }}">
                <div class="col-auto">
                    <select name="order_status" class="form-select form-select-sm">
                        <option value="">{{ __('general_content.status_trans_key') }}</option>
                        @foreach($orderStatusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('order_status') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <input type="search" name="order_search" value="{{ request('order_search') }}" class="form-control form-control-sm" placeholder="{{ __('adminlte::menu.search_trans_key') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>{{ __('adminlte::menu.search_trans_key') }}
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('general_content.code_trans_key') }}</th>
                            <th>{{ __('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.created_at_trans_key') }}</th>
                            <th class="text-end">{{ __('general_content.total_trans_key') }}</th>
                            <th class="text-end">{{ __('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->code }}</td>
                                <td>{{ $order->label }}</td>
                                <td>
                                    @php($status = $order->statu)
                                    <span class="badge bg-{{ $orderStatusBadges[$status] ?? 'secondary' }}">
                                        {{ $orderStatusOptions[$status] ?? __('general_content.status_trans_key') }}
                                    </span>
                                </td>
                                <td>{{ $order->GetshortCreatedAttribute() }}</td>
                                <td class="text-end">{{ number_format($order->total_price, 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('customer.orders.show', $order->uuid) }}">
                                        <i class="fas fa-eye me-1"></i>{{ __('general_content.view_trans_key') ?? __('general_content.show_trans_key') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $orders->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
            <div>
                <h5 class="mb-0">{{ __('general_content.invoices_trans_key') }}</h5>
                <small class="text-muted">{{ __('general_content.invoices_list_trans_key') }}</small>
            </div>
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="order_status" value="{{ request('order_status') }}">
                <input type="hidden" name="order_search" value="{{ request('order_search') }}">
                <div class="col-auto">
                    <select name="invoice_status" class="form-select form-select-sm">
                        <option value="">{{ __('general_content.status_trans_key') }}</option>
                        @foreach($invoiceStatusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('invoice_status') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <input type="search" name="invoice_search" value="{{ request('invoice_search') }}" class="form-control form-control-sm" placeholder="{{ __('adminlte::menu.search_trans_key') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>{{ __('adminlte::menu.search_trans_key') }}
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('general_content.code_trans_key') }}</th>
                            <th>{{ __('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.due_date_trans_key') }}</th>
                            <th class="text-end">{{ __('general_content.total_trans_key') }}</th>
                            <th class="text-end">{{ __('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->code }}</td>
                                <td>{{ $invoice->label }}</td>
                                <td>
                                    @php($status = $invoice->statu)
                                    <span class="badge bg-{{ $invoiceStatusBadges[$status] ?? 'secondary' }}">
                                        {{ $invoiceStatusOptions[$status] ?? __('general_content.status_trans_key') }}
                                    </span>
                                </td>
                                <td>
                                    @if($invoice->due_date)
                                        {{ \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                    @else
                                        {{ __('general_content.undefined_trans_key') }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($invoice->total_price, 2, '.', ' ') }} {{ $Factory->curency }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('customer.invoices.show', $invoice->uuid) }}">
                                        <i class="fas fa-eye me-1"></i>{{ __('general_content.view_trans_key') ?? __('general_content.show_trans_key') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $invoices->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('general_content.delivery_notes_trans_key') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('general_content.code_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.created_at_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th class="text-end">{{ __('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                            <tr>
                                <td class="fw-semibold">{{ $delivery->code }}</td>
                                <td>
                                    @php($status = $delivery->statu)
                                    <span class="badge bg-{{ $deliveryStatusBadges[$status] ?? 'secondary' }}">
                                        {{ $deliveryStatusLabels[$status] ?? __('general_content.status_trans_key') }}
                                    </span>
                                </td>
                                <td>{{ $delivery->GetshortCreatedAttribute() }}</td>
                                <td>
                                    @if($delivery->Orders)
                                        <span class="badge bg-light text-dark">#{{ $delivery->Orders->code }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('customer.deliveries.show', $delivery->uuid) }}">
                                        <i class="fas fa-eye me-1"></i>{{ __('general_content.view_trans_key') ?? __('general_content.show_trans_key') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $deliveries->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection
