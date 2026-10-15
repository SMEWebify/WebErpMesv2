@extends('adminlte::page')

@section('title', __('receivables.reminders.levels_title'))

@section('content_header')
  <div class="d-flex justify-content-between align-items-center">
    <h1 class="mb-0">{{ __('receivables.reminders.levels_title') }}</h1>
    <a href="{{ route('invoices.reminders') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i> {{ __('receivables.reminders.title') }}
    </a>
  </div>
@stop

@section('content')
  @include('include.alert-result')

  <p class="text-body-secondary">{{ __('receivables.reminders.levels_help') }}</p>

  <div class="card mb-3">
    <div class="list-group list-group-flush">
      @foreach($levels as $level)
        <div class="list-group-item d-flex flex-wrap align-items-center gap-2">
          <form method="POST" action="{{ route('invoices.reminders.levels.update', $level) }}" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
            @csrf
            @method('PUT')
            <input type="number" name="level" min="1" class="form-control form-control-sm" style="width: 70px" value="{{ $level->level }}" title="{{ __('receivables.reminders.level') }}" required>
            <input type="text" name="label" class="form-control form-control-sm" style="min-width: 160px; width: 200px" value="{{ $level->label }}" title="{{ __('receivables.reminders.label') }}" required>
            <div class="input-group input-group-sm" style="width: 150px" title="{{ __('receivables.reminders.delay_days') }}">
              <span class="input-group-text">J+</span>
              <input type="number" name="delay_days" min="0" class="form-control" value="{{ $level->delay_days }}" required>
            </div>
            <select name="email_template_id" class="form-select form-select-sm" style="min-width: 180px; width: 260px" title="{{ __('receivables.reminders.email_template') }}">
              <option value="">{{ __('receivables.reminders.no_template') }}</option>
              @foreach($templates as $template)
                <option value="{{ $template->id }}" @selected($level->email_template_id === $template->id)>{{ $template->subject }}</option>
              @endforeach
            </select>
            <select name="color" class="form-select form-select-sm" style="width: 120px" title="{{ __('receivables.reminders.color') }}">
              @foreach($colors as $color)
                <option value="{{ $color }}" @selected($level->color === $color)>{{ $color }}</option>
              @endforeach
            </select>
            <span class="badge text-bg-{{ $level->color }}">{{ $level->label }}</span>
            <button type="submit" class="btn btn-sm btn-primary ms-auto" title="{{ __('receivables.reminders.save') }}"><i class="fas fa-check"></i></button>
          </form>
          <form method="POST" action="{{ route('invoices.reminders.levels.destroy', $level) }}"
                onsubmit="return confirm(@js(__('receivables.reminders.delete') . ' ?'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('receivables.reminders.delete') }}"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h3 class="card-title">{{ __('receivables.reminders.add_level') }}</h3></div>
    <div class="card-body">
      <form method="POST" action="{{ route('invoices.reminders.levels.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-1">
          <label class="form-label">{{ __('receivables.reminders.level') }}</label>
          <input type="number" name="level" min="1" class="form-control" value="{{ old('level', ($levels->max('level') ?? 0) + 1) }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('receivables.reminders.label') }}</label>
          <input type="text" name="label" class="form-control" value="{{ old('label') }}" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">{{ __('receivables.reminders.delay_days') }}</label>
          <input type="number" name="delay_days" min="0" class="form-control" value="{{ old('delay_days') }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('receivables.reminders.email_template') }}</label>
          <select name="email_template_id" class="form-select">
            <option value="">{{ __('receivables.reminders.no_template') }}</option>
            @foreach($templates as $template)
              <option value="{{ $template->id }}">{{ $template->subject }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">{{ __('receivables.reminders.color') }}</label>
          <select name="color" class="form-select">
            @foreach($colors as $color)
              <option value="{{ $color }}">{{ $color }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i></button>
        </div>
      </form>
    </div>
  </div>
@stop
