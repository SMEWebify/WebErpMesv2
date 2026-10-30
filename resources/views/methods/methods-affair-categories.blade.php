@extends('adminlte::page')

@section('title', __('affairs.category.plural'))

@section('content_header')
    <h1>{{ __('affairs.category.plural') }}</h1>
@stop

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent :note="__('affairs.category.info')" />
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('affairs.category.plural') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('affairs.category.order') }}</th>
                <th>{{ __('affairs.category.code') }}</th>
                <th>{{ __('general_content.label_trans_key') }}</th>
                <th>{{ __('affairs.category.required_services') }}</th>
                <th>{{ __('manufacturing_dossier.label') }}</th>
                <th>{{ __('affairs.risks.register') }}</th>
                <th>{{ __('affairs.category.active') }}</th>
                <th>{{ __('affairs.category.usage') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($categories as $category)
              <tr @class(['text-body-secondary' => ! $category->active])>
                <td>{{ $category->ordre }}</td>
                <td><code>{{ $category->code }}</code></td>
                <td>{{ $category->label }}</td>
                <td>
                  @forelse ($category->requiredServices as $service)
                    <span class="badge text-bg-warning" title="{{ $service->label }}">{{ $service->code }}</span>
                  @empty
                    <span class="text-body-secondary">—</span>
                  @endforelse
                </td>
                <td>
                  @if ($category->requires_manufacturing_dossier)
                    <span class="badge text-bg-info" title="{{ __('affairs.category.requires_dossier') }}"><i class="fas fa-folder-open me-1"></i>{{ __('manufacturing_dossier.short') }}</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td>
                  @if ($category->requires_risk_register)
                    <span class="badge text-bg-warning" title="{{ __('affairs.category.requires_risk_register') }}"><i class="fas fa-shield-alt me-1"></i>{{ __('affairs.risks.required_short') }}</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td>
                  @if ($category->active)
                    <span class="badge text-bg-success"><i class="fas fa-check"></i></span>
                  @else
                    <span class="badge text-bg-secondary">{{ __('affairs.category.inactive') }}</span>
                  @endif
                </td>
                <td class="small">
                  {{ __('affairs.category.usage_detail', [
                      'opportunities' => $category->opportunities_count,
                      'quotes' => $category->quotes_count,
                      'orders' => $category->orders_count,
                  ]) }}
                </td>
                <td class="py-0 align-middle">
                  <x-ButtonTextEdit :modalTarget="'AffairCategory' . $category->id" />
                  <x-adminlte-modal id="AffairCategory{{ $category->id }}" title="{{ __('affairs.category.edit', ['label' => $category->label]) }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.affair-category.update', ['id' => $category->id]) }}">
                      @csrf
                      <div class="card-body">
                        <div class="mb-3">
                          <label for="code_{{ $category->id }}">{{ __('affairs.category.code') }}</label>
                          <input type="text" class="form-control" id="code_{{ $category->id }}" value="{{ $category->code }}" disabled>
                          <div class="form-text">{{ __('affairs.category.code_locked') }}</div>
                        </div>
                        <div class="mb-3">
                          <label for="ordre_{{ $category->id }}">{{ __('affairs.category.order') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            <input type="number" class="form-control" name="ordre" id="ordre_{{ $category->id }}" min="0" value="{{ $category->ordre }}">
                          </div>
                        </div>
                        <div class="mb-3">
                          <label for="label_{{ $category->id }}">{{ __('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            <input type="text" class="form-control" name="label" id="label_{{ $category->id }}" value="{{ $category->label }}" required maxlength="255">
                          </div>
                        </div>
                        <div class="mb-3">
                          <x-adminlte-select-bs id="required_services_{{ $category->id }}" name="required_services[]" multiple
                              label="{{ __('affairs.category.required_services') }}">
                            @foreach ($services as $service)
                              <option value="{{ $service->id }}" @selected($category->requiredServices->contains('id', $service->id))>{{ $service->code }} — {{ $service->label }}</option>
                            @endforeach
                          </x-adminlte-select-bs>
                          <div class="form-text">{{ __('affairs.category.required_services_help') }}</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                          <input type="hidden" name="requires_manufacturing_dossier" value="0">
                          <input type="checkbox" class="form-check-input" name="requires_manufacturing_dossier" value="1" id="requires_dossier_{{ $category->id }}" @checked($category->requires_manufacturing_dossier)>
                          <label class="form-check-label" for="requires_dossier_{{ $category->id }}">{{ __('affairs.category.requires_dossier') }}</label>
                          <div class="form-text">{{ __('affairs.category.requires_dossier_help') }}</div>
                        </div>
                        <div class="form-check form-switch mb-3">
                          <input type="hidden" name="requires_risk_register" value="0">
                          <input type="checkbox" class="form-check-input" name="requires_risk_register" value="1" id="requires_risks_{{ $category->id }}" @checked($category->requires_risk_register)>
                          <label class="form-check-label" for="requires_risks_{{ $category->id }}">{{ __('affairs.category.requires_risk_register') }}</label>
                          <div class="form-text">{{ __('affairs.category.requires_risk_register_help') }}</div>
                        </div>
                        <div class="form-check form-switch">
                          <input type="hidden" name="active" value="0">
                          <input type="checkbox" class="form-check-input" name="active" value="1" id="active_{{ $category->id }}" @checked($category->active)>
                          <label class="form-check-label" for="active_{{ $category->id }}">{{ __('affairs.category.active') }}</label>
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}" />
              @endforelse
            </tbody>
          </table>
        </div>
      </x-adminlte-card>
    </div>
    <div class="col-md-4">
      <form method="POST" action="{{ route('methods.affair-category.create') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('affairs.category.new') }}" theme="secondary" maximizable>
          @csrf
          <div class="mb-3">
            <label for="ordre">{{ __('affairs.category.order') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
              <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="10" value="{{ old('ordre') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="code">{{ __('affairs.category.code') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="code" maxlength="30" required value="{{ old('code') }}" placeholder="DEBIT">
            </div>
            @error('code')<span class="text-danger small">{{ $message }}</span>@enderror
          </div>
          <div class="mb-3">
            <label for="label">{{ __('general_content.label_trans_key') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-tags"></i></span>
              <input type="text" class="form-control @error('label') is-invalid @enderror" name="label" id="label" maxlength="255" required value="{{ old('label') }}">
            </div>
            @error('label')<span class="text-danger small">{{ $message }}</span>@enderror
          </div>
          <div class="mb-3">
            <x-adminlte-select-bs id="required_services_new" name="required_services[]" multiple
                label="{{ __('affairs.category.required_services') }}">
              @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected(in_array($service->id, (array) old('required_services', [])))>{{ $service->code }} — {{ $service->label }}</option>
              @endforeach
            </x-adminlte-select-bs>
            <div class="form-text">{{ __('affairs.category.required_services_help') }}</div>
          </div>
          <div class="form-check form-switch">
            <input type="hidden" name="requires_manufacturing_dossier" value="0">
            <input type="checkbox" class="form-check-input" name="requires_manufacturing_dossier" value="1" id="requires_dossier_new" @checked(old('requires_manufacturing_dossier'))>
            <label class="form-check-label" for="requires_dossier_new">{{ __('affairs.category.requires_dossier') }}</label>
            <div class="form-text">{{ __('affairs.category.requires_dossier_help') }}</div>
          </div>
          <div class="form-check form-switch mt-3">
            <input type="hidden" name="requires_risk_register" value="0">
            <input type="checkbox" class="form-check-input" name="requires_risk_register" value="1" id="requires_risks_new" @checked(old('requires_risk_register'))>
            <label class="form-check-label" for="requires_risks_new">{{ __('affairs.category.requires_risk_register') }}</label>
            <div class="form-text">{{ __('affairs.category.requires_risk_register_help') }}</div>
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
    </div>
  </div>
@stop
