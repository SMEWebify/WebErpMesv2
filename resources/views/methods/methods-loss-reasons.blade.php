@extends('adminlte::page')

@section('title', __('losses.plural'))

@section('content_header')
    <h1>{{ __('losses.plural') }}</h1>
@stop

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent :note="__('losses.info')" />
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('losses.plural') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('losses.order') }}</th>
                <th>{{ __('losses.code') }}</th>
                <th>{{ __('general_content.label_trans_key') }}</th>
                <th>{{ __('losses.active') }}</th>
                <th>{{ __('losses.usage') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($reasons as $reason)
              <tr @class(['text-body-secondary' => ! $reason->active])>
                <td>{{ $reason->ordre }}</td>
                <td><code>{{ $reason->code }}</code></td>
                <td>{{ $reason->label }}</td>
                <td>
                  @if ($reason->active)
                    <span class="badge text-bg-success"><i class="fas fa-check"></i></span>
                  @else
                    <span class="badge text-bg-secondary">{{ __('losses.inactive') }}</span>
                  @endif
                </td>
                <td class="small">
                  {{ __('losses.usage_detail', [
                      'quotes' => $reason->quotes_count,
                      'opportunities' => $reason->opportunities_count,
                  ]) }}
                </td>
                <td class="py-0 align-middle">
                  <x-ButtonTextEdit :modalTarget="'LossReason' . $reason->id" />
                  <x-adminlte-modal id="LossReason{{ $reason->id }}" title="{{ __('losses.edit', ['label' => $reason->label]) }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.loss-reason.update', ['id' => $reason->id]) }}">
                      @csrf
                      <div class="card-body">
                        <div class="mb-3">
                          <label for="code_{{ $reason->id }}">{{ __('losses.code') }}</label>
                          <input type="text" class="form-control" id="code_{{ $reason->id }}" value="{{ $reason->code }}" disabled>
                          <div class="form-text">{{ __('losses.code_locked') }}</div>
                        </div>
                        <div class="mb-3">
                          <label for="ordre_{{ $reason->id }}">{{ __('losses.order') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            <input type="number" class="form-control" name="ordre" id="ordre_{{ $reason->id }}" min="0" value="{{ $reason->ordre }}">
                          </div>
                        </div>
                        <div class="mb-3">
                          <label for="label_{{ $reason->id }}">{{ __('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            <input type="text" class="form-control" name="label" id="label_{{ $reason->id }}" value="{{ $reason->label }}" required maxlength="255">
                          </div>
                        </div>
                        <div class="form-check form-switch">
                          <input type="hidden" name="active" value="0">
                          <input type="checkbox" class="form-check-input" name="active" value="1" id="active_{{ $reason->id }}" @checked($reason->active)>
                          <label class="form-check-label" for="active_{{ $reason->id }}">{{ __('losses.active') }}</label>
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
              <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
              @endforelse
            </tbody>
          </table>
        </div>
      </x-adminlte-card>
    </div>
    <div class="col-md-4">
      <form method="POST" action="{{ route('methods.loss-reason.create') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('losses.new') }}" theme="secondary" maximizable>
          @csrf
          <div class="mb-3">
            <label for="ordre">{{ __('losses.order') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
              <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="10" value="{{ old('ordre') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="code">{{ __('losses.code') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="code" maxlength="30" required value="{{ old('code') }}" placeholder="PRICE">
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
          <x-slot name="footerSlot">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
    </div>
  </div>
@stop
