@extends('adminlte::page')

@section('title', __('purchase_quality.plural'))

@section('content_header')
    <h1>{{ __('purchase_quality.plural') }}</h1>
@stop

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent :note="__('purchase_quality.info')" />
  <div class="row">
    <div class="col-lg-8">
      @forelse ($clauses as $clause)
      <div @class(['card mb-3', 'opacity-75' => ! $clause->active])>
        <div class="card-header d-flex align-items-center gap-2">
          <span class="text-body-secondary small">{{ $clause->ordre }}</span>
          <code>{{ $clause->code }}</code>
          <strong>{{ $clause->label }}</strong>
          @unless ($clause->active)
            <span class="badge text-bg-secondary">{{ __('purchase_quality.inactive') }}</span>
          @endunless
          <span class="ms-auto small text-body-secondary">{{ __('purchase_quality.usage', ['count' => $clause->order_copies_count]) }}</span>
          <x-ButtonTextEdit :modalTarget="'QualityClause' . $clause->id" />
        </div>
        <div class="card-body">
          <p class="mb-1 small text-body-secondary">{{ __('purchase_quality.text_fr') }}</p>
          <p style="white-space: pre-line;">{{ $clause->text_fr }}</p>
          @if (filled($clause->text_en))
            <p class="mb-1 small text-body-secondary">{{ __('purchase_quality.text_en') }}</p>
            <p style="white-space: pre-line;">{{ $clause->text_en }}</p>
          @endif

          <h6 class="mt-3">{{ __('purchase_quality.rules') }}</h6>
          <form method="POST" action="{{ route('purchases.quality-clauses.rules.store', ['id' => $clause->id]) }}" id="rule-form-{{ $clause->id }}">
            @csrf
          </form>
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>{{ __('purchase_quality.certificate_type') }}</th>
                <th>{{ __('purchase_quality.construction_code') }}</th>
                <th>{{ __('purchase_quality.nuclear_level') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($clause->rules as $rule)
              <tr>
                @foreach (\App\Services\Purchases\PurchaseCriticality::FIELDS as $field)
                  <td>{!! $rule->{$field} ? '<span class="badge text-bg-info">' . e($rule->{$field}) . '</span>' : '<span class="text-body-secondary small">' . e(__('purchase_quality.any')) . '</span>' !!}</td>
                @endforeach
                <td class="text-end">
                  <form method="POST" action="{{ route('purchases.quality-clauses.rules.destroy', ['id' => $clause->id, 'ruleId' => $rule->id]) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('purchase_quality.delete_rule') }}">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-body-secondary small">{{ __('purchase_quality.no_rule') }}</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                @foreach ($options as $field => $values)
                <td>
                  <select class="form-select form-select-sm" name="{{ $field }}" form="rule-form-{{ $clause->id }}" aria-label="{{ __('purchase_quality.' . $field) }}">
                    <option value="">{{ __('purchase_quality.any') }}</option>
                    @foreach ($values as $value)
                      <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                  </select>
                </td>
                @endforeach
                <td class="text-end">
                  <button type="submit" class="btn btn-sm btn-teal text-nowrap" form="rule-form-{{ $clause->id }}">
                    <i class="fa-solid fa-plus me-1"></i>{{ __('purchase_quality.add_rule') }}
                  </button>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <x-adminlte-modal id="QualityClause{{ $clause->id }}" title="{{ __('purchase_quality.edit', ['code' => $clause->code]) }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
        <form method="POST" action="{{ route('purchases.quality-clauses.update', ['id' => $clause->id]) }}">
          @csrf
          <div class="card-body">
            <div class="row">
              <div class="mb-3 col-md-4">
                <label for="code_{{ $clause->id }}">{{ __('purchase_quality.code') }}</label>
                <input type="text" class="form-control" id="code_{{ $clause->id }}" value="{{ $clause->code }}" disabled>
                <div class="form-text">{{ __('purchase_quality.code_locked') }}</div>
              </div>
              <div class="mb-3 col-md-6">
                <label for="label_{{ $clause->id }}">{{ __('purchase_quality.label') }}</label>
                <input type="text" class="form-control" name="label" id="label_{{ $clause->id }}" value="{{ $clause->label }}" required maxlength="255">
              </div>
              <div class="mb-3 col-md-2">
                <label for="ordre_{{ $clause->id }}">{{ __('purchase_quality.order') }}</label>
                <input type="number" class="form-control" name="ordre" id="ordre_{{ $clause->id }}" min="0" value="{{ $clause->ordre }}">
              </div>
            </div>
            <div class="mb-3">
              <label for="text_fr_{{ $clause->id }}">{{ __('purchase_quality.text_fr') }}</label>
              <textarea class="form-control" name="text_fr" id="text_fr_{{ $clause->id }}" rows="5" required>{{ $clause->text_fr }}</textarea>
            </div>
            <div class="mb-3">
              <label for="text_en_{{ $clause->id }}">{{ __('purchase_quality.text_en') }}</label>
              <textarea class="form-control" name="text_en" id="text_en_{{ $clause->id }}" rows="5">{{ $clause->text_en }}</textarea>
              <div class="form-text">{{ __('purchase_quality.text_en_help') }}</div>
            </div>
            <div class="form-check form-switch">
              <input type="hidden" name="active" value="0">
              <input type="checkbox" class="form-check-input" name="active" value="1" id="active_{{ $clause->id }}" @checked($clause->active)>
              <label class="form-check-label" for="active_{{ $clause->id }}">{{ __('purchase_quality.active') }}</label>
            </div>
          </div>
          <div class="card-footer">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
          </div>
        </form>
      </x-adminlte-modal>
      @empty
      <div class="card mb-3">
        <div class="card-body text-body-secondary">{{ __('general_content.no_data_trans_key') }}</div>
      </div>
      @endforelse
    </div>
    <div class="col-lg-4">
      <form method="POST" action="{{ route('purchases.quality-clauses.create') }}">
        <x-adminlte-card title="{{ __('purchase_quality.new') }}" theme="secondary" maximizable>
          @csrf
          <div class="row">
            <div class="mb-3 col-8">
              <label for="code">{{ __('purchase_quality.code') }}</label>
              <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="code" maxlength="30" required value="{{ old('code') }}" placeholder="CERT-31">
              @error('code')<span class="text-danger small">{{ $message }}</span>@enderror
            </div>
            <div class="mb-3 col-4">
              <label for="ordre">{{ __('purchase_quality.order') }}</label>
              <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="10" value="{{ old('ordre') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="label">{{ __('purchase_quality.label') }}</label>
            <input type="text" class="form-control @error('label') is-invalid @enderror" name="label" id="label" maxlength="255" required value="{{ old('label') }}">
            @error('label')<span class="text-danger small">{{ $message }}</span>@enderror
          </div>
          <div class="mb-3">
            <label for="text_fr">{{ __('purchase_quality.text_fr') }}</label>
            <textarea class="form-control @error('text_fr') is-invalid @enderror" name="text_fr" id="text_fr" rows="5" required>{{ old('text_fr') }}</textarea>
            @error('text_fr')<span class="text-danger small">{{ $message }}</span>@enderror
          </div>
          <div class="mb-3">
            <label for="text_en">{{ __('purchase_quality.text_en') }}</label>
            <textarea class="form-control" name="text_en" id="text_en" rows="5">{{ old('text_en') }}</textarea>
            <div class="form-text">{{ __('purchase_quality.text_en_help') }}</div>
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
    </div>
  </div>
@stop
