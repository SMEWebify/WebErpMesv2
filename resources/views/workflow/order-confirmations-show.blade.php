@extends('adminlte::page')

@section('title', __('general_content.order_confirm_trans_key') . ' - ' . $Confirmation->code)

@section('content_header')
  <x-Content-header-previous-button
      h1="{{ __('general_content.order_confirm_trans_key') }} : {{ $Confirmation->code }} ({{ $Confirmation->revision }})"
      previous="{{ $previousUrl }}"
      list="{{ route('order.confirmations') }}"
      next="{{ $nextUrl }}"/>
@stop

@section('content')

@php
  $statusMap = [
    \App\Models\Workflow\OrderConfirmations::STATUS_IN_PROGRESS => ['secondary', __('general_content.arc_status_in_progress_trans_key')],
    \App\Models\Workflow\OrderConfirmations::STATUS_SENT        => ['primary',   __('general_content.arc_status_sent_trans_key')],
    \App\Models\Workflow\OrderConfirmations::STATUS_ACCEPTED    => ['success',   __('general_content.arc_status_accepted_trans_key')],
    \App\Models\Workflow\OrderConfirmations::STATUS_SUPERSEDED  => ['light',     __('general_content.arc_status_superseded_trans_key')],
  ];
  [$statusColor, $statusLabel] = $statusMap[(int) $Confirmation->statu] ?? ['secondary', $Confirmation->statu];
  $diffCount = count($diff['added']) + count($diff['removed']) + count($diff['modified']);
@endphp

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Confirmation" data-toggle="tab">{{ __('general_content.informations_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.lines_trans_key') }} ({{ count($Confirmation->OrderConfirmationLines) }})</a></li>
      <li class="nav-item">
        <a class="nav-link" href="#Diff" data-toggle="tab">
          {{ __('general_content.arc_differences_trans_key') }}
          @if($diffCount > 0)
            <span class="badge badge-warning ml-1">{{ $diffCount }}</span>
          @endif
        </a>
      </li>
      <li class="nav-item"><a class="nav-link" href="#Revisions" data-toggle="tab">{{ __('general_content.arc_revisions_trans_key') }} ({{ count($revisions) }})</a></li>
    </ul>
  </div>

  <div class="card-body">
    <div class="tab-content">

      {{-- ── Informations ──────────────────────────────────────────────── --}}
      <div class="tab-pane active" id="Confirmation">
        <x-relational-breadcrumb :entity="$Confirmation" />

        @if($diffCount > 0 && $Confirmation->is_current)
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ __('general_content.arc_diverged_warning_trans_key', ['revision' => $Confirmation->revision]) }}
          </div>
        @endif

        <div class="row">
          <div class="col-md-8">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
              <div class="row">
                <div class="col-md-6">
                  <p><label class="text-success">{{ __('general_content.external_id_trans_key') }}</label> {{ $Confirmation->code }}</p>
                  <p><label class="text-success">{{ __('general_content.revision_trans_key') }}</label>
                    <span class="badge badge-dark">{{ $Confirmation->revision }}</span>
                    @if($Confirmation->is_current)
                      <span class="badge badge-success ml-1">{{ __('general_content.arc_current_trans_key') }}</span>
                    @endif
                  </p>
                  <p><label class="text-success">{{ __('general_content.status_trans_key') }}</label>
                    <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                  </p>
                  <p><label class="text-success">{{ __('general_content.label_trans_key') }}</label> {{ $Confirmation->label }}</p>
                  <p><label class="text-success">{{ __('general_content.customer_reference_trans_key') }}</label> {{ $Confirmation->customer_reference ?: '-' }}</p>
                </div>
                <div class="col-md-6">
                  <p><label class="text-info">{{ __('general_content.orders_trans_key') }}</label>
                    @if($Confirmation->Order)
                      <a href="{{ route('orders.show', ['id' => $Confirmation->Order->id]) }}">{{ $Confirmation->Order->code }}</a>
                    @else
                      -
                    @endif
                  </p>
                  <p><label class="text-info">{{ __('general_content.companie_name_trans_key') }}</label> {{ optional($Confirmation->companie)->label ?: '-' }}</p>
                  <p><label class="text-info">{{ __('general_content.contact_name_trans_key') }}</label> {{ optional($Confirmation->contact)->name ?: '-' }}</p>
                  <p><label class="text-info">{{ __('general_content.adress_name_trans_key') }}</label> {{ optional($Confirmation->adresse)->label ?: '-' }}</p>
                  <p><label class="text-info">{{ __('general_content.delivery_date_trans_key') }}</label> {{ $Confirmation->validity_date?->format('d/m/Y') ?: '-' }}</p>
                </div>
              </div>

              <hr>

              <div class="row">
                <div class="col-md-4">
                  <p><label class="text-secondary">{{ __('general_content.payment_conditions_trans_key') }}</label> {{ optional($Confirmation->payment_condition)->label ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                  <p><label class="text-secondary">{{ __('general_content.payment_methods_trans_key') }}</label> {{ optional($Confirmation->payment_method)->label ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                  <p><label class="text-secondary">{{ __('general_content.delivery_method_trans_key') }}</label> {{ optional($Confirmation->delevery_method)->label ?: '-' }}</p>
                </div>
              </div>

              @if($Confirmation->comment)
                <hr>
                <p><label class="text-secondary">{{ __('general_content.comment_trans_key') }}</label></p>
                <p class="text-muted">{{ $Confirmation->comment }}</p>
              @endif
            </x-adminlte-card>
          </div>

          <div class="col-md-4">
            <x-adminlte-card title="{{ __('general_content.tracking_trans_key') }}" theme="info" maximizable>
              <table class="table table-sm">
                <tr>
                  <td>{{ __('general_content.user_management_trans_key') }}</td>
                  <td>{{ optional($Confirmation->UserManagement)->name ?: '-' }}</td>
                </tr>
                <tr>
                  <td>{{ __('general_content.date_trans_key') }}</td>
                  <td>{{ $Confirmation->issued_at?->format('d/m/Y H:i') ?: $Confirmation->GetshortCreatedAttribute() }}</td>
                </tr>
                <tr>
                  <td>{{ __('general_content.send_trans_key') }}</td>
                  <td>{{ $Confirmation->sent_at?->format('d/m/Y H:i') ?: '-' }}</td>
                </tr>
                <tr>
                  <td>{{ __('general_content.arc_status_accepted_trans_key') }}</td>
                  <td>{{ $Confirmation->customer_accepted_at?->format('d/m/Y H:i') ?: '-' }}</td>
                </tr>
                <tr>
                  <td>{{ __('general_content.total_trans_key') }}</td>
                  <td><strong>{{ $Confirmation->formatted_total_price }}</strong></td>
                </tr>
              </table>
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                    <td style="width:50%">{{ __('general_content.order_confirm_trans_key') }}</td>
                    <td>
                      @if($Confirmation->isSent())
                        <x-ButtonTextPDF route="{{ route('pdf.orders.confirm', ['Document' => $Confirmation->id]) }}" />
                      @else
                        <span class="text-muted small">{{ __('general_content.arc_draft_no_pdf_trans_key') }}</span>
                      @endif
                    </td>
                  </tr>
                  @if($Confirmation->isEditable())
                  <tr>
                    <td style="width:50%">{{ __('general_content.send_trans_key') }}</td>
                    <td>
                      <form method="POST" action="{{ route('order.confirmations.json.send', ['id' => $Confirmation->id]) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" type="submit">
                          <i class="fas fa-paper-plane"></i> {{ __('general_content.send_trans_key') }}
                        </button>
                      </form>
                    </td>
                  </tr>
                  @endif
                </table>
              </div>
            </x-adminlte-card>
          </div>
        </div>
      </div>

      {{-- ── Lignes figées ─────────────────────────────────────────────── --}}
      <div class="tab-pane" id="Lines">
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.qty_trans_key') }}</th>
                <th>{{ __('general_content.unit_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.price_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.discount_trans_key') }}</th>
                <th class="text-right">TVA</th>
                <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.total_trans_key') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($Confirmation->OrderConfirmationLines as $line)
                <tr>
                  <td>{{ $line->ordre }}</td>
                  <td>
                    {{ $line->label }}
                    @if($line->code)<br><span class="text-muted small">{{ $line->code }}</span>@endif
                  </td>
                  <td class="text-right">{{ $line->qty }}</td>
                  <td>{{ $line->unit_label ?: '-' }}</td>
                  <td class="text-right">{{ $line->formatted_selling_price }}</td>
                  <td class="text-right">{{ $line->discount }} %</td>
                  <td class="text-right">{{ $line->vat_rate }} %</td>
                  <td>{{ $line->delivery_date ?: '-' }}</td>
                  <td class="text-right">{{ $line->total }}</td>
                </tr>
              @empty
                <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}" />
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- ── Écarts avec la commande ───────────────────────────────────── --}}
      <div class="tab-pane" id="Diff">
        @if(!$Confirmation->is_current)
          <p class="text-muted">{{ __('general_content.arc_diff_only_current_trans_key') }}</p>
        @elseif($diffCount === 0)
          <div class="alert alert-success mb-0">
            <i class="fas fa-check-circle mr-1"></i>{{ __('general_content.arc_no_difference_trans_key') }}
          </div>
        @else
          <table class="table table-sm">
            <thead>
              <tr>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.arc_change_trans_key') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($diff['added'] as $line)
                <tr>
                  <td><span class="badge badge-success">{{ __('general_content.arc_line_added_trans_key') }}</span></td>
                  <td>{{ $line['label'] }}</td>
                  <td class="text-muted">-</td>
                </tr>
              @endforeach
              @foreach($diff['removed'] as $line)
                <tr>
                  <td><span class="badge badge-danger">{{ __('general_content.arc_line_removed_trans_key') }}</span></td>
                  <td>{{ $line['label'] }}</td>
                  <td class="text-muted">-</td>
                </tr>
              @endforeach
              @foreach($diff['modified'] as $line)
                <tr>
                  <td><span class="badge badge-warning">{{ __('general_content.arc_line_modified_trans_key') }}</span></td>
                  <td>{{ $line['label'] }}</td>
                  <td>
                    @foreach($line['changes'] as $field => $change)
                      <div>
                        <span class="text-muted">{{ $field }}</span> :
                        <del class="text-danger">{{ $change['before'] ?? '-' }}</del>
                        <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                        <strong class="text-success">{{ $change['after'] ?? '-' }}</strong>
                      </div>
                    @endforeach
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>

      {{-- ── Historique des indices ────────────────────────────────────── --}}
      <div class="tab-pane" id="Revisions">
        <table class="table table-hover table-sm">
          <thead>
            <tr>
              <th>{{ __('general_content.revision_trans_key') }}</th>
              <th>{{ __('general_content.external_id_trans_key') }}</th>
              <th>{{ __('general_content.status_trans_key') }}</th>
              <th>{{ __('general_content.send_trans_key') }}</th>
              <th class="text-right">{{ __('general_content.total_trans_key') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($revisions as $revision)
              @php [$c, $l] = $statusMap[(int) $revision->statu] ?? ['secondary', $revision->statu]; @endphp
              <tr class="{{ $revision->id === $Confirmation->id ? 'table-active' : '' }}">
                <td>
                  <span class="badge badge-dark">{{ $revision->revision }}</span>
                  @if($revision->is_current)
                    <i class="fas fa-check-circle text-success ml-1" title="{{ __('general_content.arc_current_trans_key') }}"></i>
                  @endif
                </td>
                <td>{{ $revision->code }}</td>
                <td><span class="badge badge-{{ $c }}">{{ $l }}</span></td>
                <td>{{ $revision->sent_at?->format('d/m/Y') ?: '-' }}</td>
                <td class="text-right">{{ $revision->formatted_total_price }}</td>
                <td>
                  @if($revision->id !== $Confirmation->id)
                    <a href="{{ route('order.confirmations.show', ['id' => $revision->id]) }}" class="btn btn-xs btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
@stop
