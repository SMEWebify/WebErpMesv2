@extends('adminlte::page')

@section('title', $Derogation->code . ' - ' . __('general_content.derogations_trans_key'))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="mb-0">{{ $Derogation->code }} <small class="text-body-secondary">{{ $Derogation->label }}</small></h1>
        @if($Derogation->isExternal())
            <span class="badge text-bg-danger">{{ __('derogations.external') }}</span>
        @else
            <span class="badge text-bg-warning">{{ __('derogations.internal') }}</span>
        @endif
        @switch((int) $Derogation->statu)
            @case(1) <span class="badge text-bg-info">{{ __('general_content.in_progress_trans_key') }}</span> @break
            @case(2) <span class="badge text-bg-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @break
            @case(3) <span class="badge text-bg-success">{{ __('general_content.validate_trans_key') }}</span> @break
            @case(4) <span class="badge text-bg-danger">{{ __('general_content.canceled_trans_key') }}</span> @break
        @endswitch
        <a href="{{ route('quality.derogation') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fas fa-arrow-left"></i> {{ __('derogations.back') }}
        </a>
    </div>
@stop

@section('content')
@include('include.alert-result')

@php
    $locked = $Derogation->isValidated() && ! $canValidate;
@endphp

<div class="row">
  <div class="col-lg-8">
    <form method="POST" action="{{ route('quality.derogation.update', ['id' => $Derogation->id]) }}">
      @csrf
      <fieldset @disabled($locked)>
      <div class="card card-outline card-info mb-3">
        <div class="card-header"><h3 class="card-title">{{ __('derogations.details') }}</h3></div>
        <div class="card-body">
          <div class="row gx-3">
            <div class="mb-3 col-md-6">
              <label class="form-label" for="label">{{ __('general_content.label_trans_key') }}</label>
              <input type="text" class="form-control" name="label" id="label" value="{{ old('label', $Derogation->label) }}" required>
            </div>
            <div class="mb-3 col-md-3">
              <label class="form-label" for="statu">{{ __('general_content.statu_trans_key') }}</label>
              <select class="form-select" name="statu" id="statu">
                <option value="1" @selected(old('statu', $Derogation->statu) == 1)>{{ __('general_content.in_progress_trans_key') }}</option>
                <option value="2" @selected(old('statu', $Derogation->statu) == 2)>{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                <option value="3" @selected(old('statu', $Derogation->statu) == 3) @disabled(! $canValidate && ! $Derogation->isValidated())>{{ __('general_content.validate_trans_key') }}</option>
                <option value="4" @selected(old('statu', $Derogation->statu) == 4)>{{ __('general_content.canceled_trans_key') }}</option>
              </select>
            </div>
            <div class="mb-3 col-md-3">
              <label class="form-label" for="type_update">{{ __('general_content.type_trans_key') }} ({{ __('derogations.internal') }})</label>
              <x-adminlte-input-switch id="type_update" name="type_update" :is-checked="! $Derogation->isExternal()" />
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label" for="user_id">{{ __('general_content.user_trans_key') }}</label>
              <select class="form-select" name="user_id" id="user_id">
                @foreach ($userSelect as $item)
                <option value="{{ $item->id }}" @selected($Derogation->user_id == $item->id)>{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label" for="quality_non_conformitie_id">{{ __('derogations.non_conformity') }}</label>
              <select class="form-select" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                <option value="">-</option>
                @foreach ($NonConformitysSelect as $item)
                <option value="{{ $item->id }}" @selected($Derogation->quality_non_conformitie_id == $item->id)>{{ $item->code }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label">{{ __('general_content.problem_description_trans_key') }}</label>
              <textarea class="form-control" rows="4" name="pb_descp" required>{{ old('pb_descp', $Derogation->pb_descp) }}</textarea>
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label">{{ __('general_content.proposal_description_trans_key') }}</label>
              <textarea class="form-control" rows="4" name="proposal" required>{{ old('proposal', $Derogation->proposal) }}</textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-outline card-secondary mb-3">
        <div class="card-header"><h3 class="card-title">{{ __('derogations.customer_card') }}</h3></div>
        <div class="card-body">
          <p class="text-body-secondary small mb-3">{{ __('derogations.external_hint') }}</p>
          <div class="row gx-3">
            <div class="mb-3 col-md-4">
              <x-adminlte-select-bs :config="['allowEmptyOption' => true]" name="companies_id" id="derogation_companies_id" label="{{ __('derogations.customer') }}">
                <option value="">—</option>
                @foreach ($CompanieSelect as $item)
                <option value="{{ $item->id }}" @selected($Derogation->companies_id == $item->id)>{{ $item->label }}</option>
                @endforeach
                @if($Derogation->companie && ! $CompanieSelect->contains('id', $Derogation->companies_id))
                <option value="{{ $Derogation->companies_id }}" selected>{{ $Derogation->companie->label }}</option>
                @endif
              </x-adminlte-select-bs>
            </div>
            <div class="mb-3 col-md-4">
              <label class="form-label" for="derogation_contact">{{ __('derogations.contact') }}</label>
              <select class="form-select" name="companies_contacts_id" id="derogation_contact">
                <option value="">—</option>
                @foreach ($ContactSelect as $item)
                <option value="{{ $item->id }}" @selected($Derogation->companies_contacts_id == $item->id)>{{ trim($item->first_name . ' ' . $item->name) }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3 col-md-4">
              <label class="form-label" for="derogation_order_line">{{ __('derogations.order_line') }}</label>
              <select class="form-select" name="order_lines_id" id="derogation_order_line">
                <option value="">{{ __('derogations.order_line_none') }}</option>
                @foreach ($OrderLineSelect as $item)
                <option value="{{ $item->id }}" @selected($Derogation->order_lines_id == $item->id)>{{ $item->order?->code }} — {{ $item->code }} {{ $item->label }} ({{ $item->qty }})</option>
                @endforeach
              </select>
              <div class="form-text">{{ __('derogations.order_line_from_nc') }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-outline card-secondary mb-3">
        <div class="card-header"><h3 class="card-title">{{ __('derogations.reply_card') }}</h3></div>
        <div class="card-body">
          <div class="row gx-3">
            <div class="mb-3 col-md-4">
              <label class="form-label" for="customer_reply_date">{{ __('derogations.reply_date') }}</label>
              <input type="date" class="form-control" name="customer_reply_date" id="customer_reply_date"
                     value="{{ old('customer_reply_date', $Derogation->customer_reply_date?->format('Y-m-d')) }}">
            </div>
            <div class="mb-3 col-md-8">
              <label class="form-label" for="customer_signatory">{{ __('derogations.signatory') }}</label>
              <input type="text" class="form-control" name="customer_signatory" id="customer_signatory" maxlength="255"
                     value="{{ old('customer_signatory', $Derogation->customer_signatory) }}">
              <div class="form-text">{{ __('derogations.signatory_hint') }}</div>
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label">{{ __('general_content.customer_reply_description_trans_key') }}</label>
              <textarea class="form-control" rows="4" name="reply">{{ old('reply', $Derogation->reply) }}</textarea>
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label">{{ __('general_content.decision_description_trans_key') }}</label>
              <textarea class="form-control" rows="4" name="decision">{{ old('decision', $Derogation->decision) }}</textarea>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <x-adminlte-button type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-save"/>
        </div>
      </div>
      </fieldset>
    </form>

    <div class="card card-outline card-secondary mb-3">
      <div class="card-header"><h3 class="card-title">{{ __('derogations.documents') }}</h3></div>
      <div class="card-body">
        <p class="text-body-secondary small">{{ __('derogations.documents_hint') }}</p>
        @include('include.file-manager-mount', [
            'fileableType' => 'derogation',
            'fileableId' => $Derogation->id,
            'fileRoles' => \App\Services\Files\FileRole::forDerogations(),
            'fileManagerCanEdit' => ! $locked,
        ])
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    {{-- Validation --}}
    <div class="card card-outline {{ $Derogation->isValidated() ? 'card-success' : ($missing ? 'card-warning' : 'card-success') }} mb-3">
      <div class="card-header"><h3 class="card-title">{{ __('derogations.validation_card') }}</h3></div>
      <div class="card-body">
        @if($Derogation->isValidated())
          <p class="mb-0 text-success">
            <i class="fas fa-check-circle"></i>
            {{ __('derogations.validated_by', ['name' => $Derogation->validator?->name ?? '—', 'date' => $Derogation->validated_at?->format('d/m/Y H:i') ?? '—']) }}
          </p>
        @elseif($missing)
          <p class="mb-1">{{ __('derogations.missing_title') }}</p>
          <ul class="mb-0">
            @foreach($missing as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        @else
          <p class="mb-0 text-success"><i class="fas fa-check"></i> {{ __('derogations.ready') }}</p>
        @endif
        @unless($canValidate || $Derogation->isValidated())
          <p class="small text-body-secondary mt-2 mb-0">{{ __('derogations.permission_hint') }}</p>
        @endunless
      </div>
    </div>

    {{-- Signature DocuSign --}}
    @if($Derogation->isExternal())
    <div class="card card-outline card-primary mb-3">
      <div class="card-header"><h3 class="card-title">{{ __('derogations.sign_card') }}</h3></div>
      <div class="card-body">
        <p class="small text-body-secondary">{{ __('derogations.sign_intro') }}</p>

        @forelse($Derogation->signatures->sortByDesc('id')->take(5) as $signature)
          <div class="small mb-1">
            @switch($signature->status)
              @case('completed')
                <i class="fas fa-check-circle text-success"></i>
                {{ __('derogations.status_completed', ['name' => $signature->signer_name, 'date' => $signature->completed_at?->format('d/m/Y H:i')]) }}
                @break
              @case('declined')
                <i class="fas fa-times-circle text-danger"></i>
                {{ __('derogations.status_declined', ['name' => $signature->signer_name]) }}@if($signature->status_reason) : « {{ $signature->status_reason }} »@endif
                @break
              @case('voided')
                <i class="fas fa-ban text-body-secondary"></i>
                {{ __('derogations.status_voided') }}@if($signature->status_reason) — {{ $signature->status_reason }}@endif
                @break
              @case('delivered')
                <i class="fas fa-envelope-open text-info"></i>
                {{ __('derogations.status_delivered', ['email' => $signature->signer_email]) }}
                @break
              @default
                <i class="fas fa-paper-plane text-info"></i>
                {{ __('derogations.status_sent', ['email' => $signature->signer_email, 'date' => $signature->sent_at?->format('d/m/Y H:i')]) }}
            @endswitch
          </div>
        @empty
        @endforelse

        @if($signatureBlocked)
          <div class="alert alert-light small mb-2 mt-2">{{ $signatureBlocked }}</div>
        @endif

        <div class="d-flex flex-wrap gap-2 mt-2">
          @unless($signatureBlocked)
          <form method="POST" action="{{ route('quality.derogation.signature.send', ['id' => $Derogation->id]) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="fas fa-file-signature"></i>
              {{ $Derogation->signatures->contains(fn ($s) => $s->isPending()) ? __('derogations.sign_resend') : __('derogations.sign_button') }}
            </button>
          </form>
          @endunless
          @if($signatureAvailable && $Derogation->signatures->isNotEmpty())
          <form method="POST" action="{{ route('quality.derogation.signature.sync', ['id' => $Derogation->id]) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync"></i> {{ __('derogations.sync_button') }}</button>
          </form>
          @endif
          <a href="{{ route('quality.derogation.preview', ['id' => $Derogation->id]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-pdf"></i> {{ __('derogations.preview_button') }}
          </a>
        </div>
      </div>
    </div>
    @endif

    {{-- Historique --}}
    <div class="card card-outline card-secondary mb-3">
      <div class="card-header"><h3 class="card-title">{{ __('derogations.history') }}</h3></div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush small">
          @forelse($history as $activity)
            <li class="list-group-item">
              <div class="d-flex justify-content-between">
                <strong>{{ $activity->causer?->name ?? __('derogations.history_system') }}</strong>
                <span class="text-body-secondary">{{ $activity->created_at?->format('d/m/Y H:i') }}</span>
              </div>
              <div>{{ $activity->description }}</div>
              @php($changes = $activity->properties['attributes'] ?? [])
              @if($changes)
                <div class="text-body-secondary">
                  @foreach($changes as $field => $value)
                    <div><code>{{ $field }}</code> : {{ \Illuminate\Support\Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 80) }}</div>
                  @endforeach
                </div>
              @endif
            </li>
          @empty
            <li class="list-group-item text-body-secondary">{{ __('derogations.history_empty') }}</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@stop

@section('js')
<script>
(function () {
    var company = document.getElementById('derogation_companies_id');
    if (!company) return;

    var url = @json(route('quality.derogation.company-data'));

    function fill(id, items, emptyLabel) {
        var select = document.getElementById(id);
        if (!select) return;
        select.replaceChildren(new Option(emptyLabel, ''));
        items.forEach(function (item) { select.add(new Option(item.label, item.id)); });
    }

    // Changer de client recharge ses contacts et ses lignes de commande à livrer.
    company.addEventListener('change', function () {
        if (!company.value) {
            fill('derogation_contact', [], '—');
            fill('derogation_order_line', [], @json(__('derogations.order_line_none')));
            return;
        }
        fetch(url + '?' + new URLSearchParams({ company_id: company.value }), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) throw new Error(response.status);
                return response.json();
            })
            .then(function (data) {
                fill('derogation_contact', data.contacts || [], '—');
                fill('derogation_order_line', data.order_lines || [], @json(__('derogations.order_line_none')));
            })
            .catch(function (error) { console.error('derogation company-data', error); });
    });
})();
</script>
@stop
