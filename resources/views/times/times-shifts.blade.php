@extends('adminlte::page')

@section('title', __('general_content.times_shifts_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.times_shifts_trans_key') }}</h1>
@stop

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent note="{{ __('general_content.shift_pattern_info_trans_key') }}" />
  <div class="row">
    <div class="col-md-8">
      @forelse ($ShiftPatterns as $ShiftPattern)
        <x-adminlte-card title="{{ $ShiftPattern->label }}" theme="info" collapsible>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <span class="badge" style="background-color: {{ $ShiftPattern->color ?? '#6c757d' }}; color: #fff;">{{ $ShiftPattern->code }}</span>
              @if($ShiftPattern->is_default)
                <span class="badge badge-success">{{ __('general_content.shift_default_trans_key') }}</span>
              @endif
              <span class="text-muted ml-2">{{ $ShiftPattern->weeklyHours() }} h</span>
            </div>
            <button type="button" class="btn btn-sm btn-flat btn-info" data-toggle="modal" data-target="#modalShift{{ $ShiftPattern->id }}">
              <i class="fas fa-edit"></i>
            </button>
          </div>

          @if($ShiftPattern->comment)
            <p class="text-muted">{{ $ShiftPattern->comment }}</p>
          @endif

          @if($ShiftPattern->slots->isEmpty())
            <div class="callout callout-warning mb-2">
              {{ __('general_content.shift_no_slot_warning_trans_key') }}
            </div>
          @endif

          <div class="table-responsive p-0">
            <table class="table table-sm table-hover">
              <thead>
                <tr>
                  <th>{{ __('general_content.date_trans_key') }}</th>
                  <th>{{ __('general_content.shift_slots_trans_key') }}</th>
                  <th class="text-right">{{ __('general_content.hour_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($Weekdays as $isoDay => $dayLabel)
                  @php($daySlots = $ShiftPattern->slotsForWeekday($isoDay))
                  <tr>
                    <td class="text-capitalize">{{ $dayLabel }}</td>
                    <td>
                      @forelse ($daySlots as $slot)
                        <span class="badge badge-light border mr-1">
                          {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                          @if($slot->crossesMidnight())
                            <i class="fas fa-moon text-primary ml-1" title="{{ __('general_content.shift_crosses_midnight_trans_key') }}"></i>
                          @endif
                          @if($slot->label)<em class="text-muted">{{ $slot->label }}</em>@endif
                          <form action="{{ route('times.shift.slot.delete', ['id' => $ShiftPattern->id, 'slotId' => $slot->id]) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm p-0 text-danger"><i class="fas fa-times"></i></button>
                          </form>
                        </span>
                      @empty
                        <span class="text-muted">-</span>
                      @endforelse
                    </td>
                    <td class="text-right">{{ $daySlots->sum(fn ($slot) => $slot->durationHours()) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <form action="{{ route('times.shift.slot.create', ['id' => $ShiftPattern->id]) }}" method="POST" class="form-inline mt-2">
            @csrf
            <select class="form-control form-control-sm mr-1" name="weekday">
              @foreach ($Weekdays as $isoDay => $dayLabel)
                <option value="{{ $isoDay }}">{{ $dayLabel }}</option>
              @endforeach
            </select>
            <input type="time" class="form-control form-control-sm mr-1" name="start_time" value="06:00" required>
            <input type="time" class="form-control form-control-sm mr-1" name="end_time" value="14:00" required>
            <input type="text" class="form-control form-control-sm mr-1" name="label" placeholder="{{ __('general_content.label_trans_key') }}">
            <div class="custom-control custom-checkbox mr-2">
              <input type="checkbox" class="custom-control-input" id="apply_to_week_{{ $ShiftPattern->id }}" name="apply_to_week" value="1" checked>
              <label class="custom-control-label" for="apply_to_week_{{ $ShiftPattern->id }}">{{ __('general_content.shift_apply_to_week_trans_key') }}</label>
            </div>
            <button type="submit" class="btn btn-sm btn-flat btn-success"><i class="fas fa-plus"></i></button>
          </form>

          <x-adminlte-modal id="modalShift{{ $ShiftPattern->id }}" title="{{ $ShiftPattern->label }}" theme="info" icon="fas fa-clock" size="lg">
            <form action="{{ route('times.shift.update', ['id' => $ShiftPattern->id]) }}" method="POST">
              @csrf
              <div class="form-group">
                <label for="label_{{ $ShiftPattern->id }}">{{ __('general_content.label_trans_key') }}</label>
                <input type="text" class="form-control" name="label" id="label_{{ $ShiftPattern->id }}" value="{{ $ShiftPattern->label }}">
              </div>
              <div class="form-group">
                <label for="color_{{ $ShiftPattern->id }}">{{ __('general_content.color_trans_key') }}</label>
                <input type="color" class="form-control" name="color" id="color_{{ $ShiftPattern->id }}" value="{{ $ShiftPattern->color ?? '#3c8dbc' }}">
              </div>
              <div class="form-group">
                <label for="comment_{{ $ShiftPattern->id }}">{{ __('general_content.comment_trans_key') }}</label>
                <textarea class="form-control" name="comment" id="comment_{{ $ShiftPattern->id }}">{{ $ShiftPattern->comment }}</textarea>
              </div>
              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="is_default_{{ $ShiftPattern->id }}" name="is_default" value="1" @if($ShiftPattern->is_default) checked @endif>
                  <label class="custom-control-label" for="is_default_{{ $ShiftPattern->id }}">{{ __('general_content.shift_default_hint_trans_key') }}</label>
                </div>
              </div>
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </form>
          </x-adminlte-modal>
        </x-adminlte-card>
      @empty
        <x-InfocalloutComponent note="{{ __('general_content.no_data_trans_key') }}" />
      @endforelse
    </div>

    <div class="col-md-4">
      <form action="{{ route('times.shift.create') }}" method="POST">
        @csrf
        <x-adminlte-card title="{{ __('general_content.new_shift_pattern_trans_key') }}" theme="danger">
          <div class="form-group">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <input type="text" class="form-control" name="code" id="code" placeholder="3X8" required>
          </div>
          <div class="form-group">
            <label for="label">{{ __('general_content.label_trans_key') }}</label>
            <input type="text" class="form-control" name="label" id="label" required>
          </div>
          <div class="form-group">
            <label for="color">{{ __('general_content.color_trans_key') }}</label>
            <input type="color" class="form-control" name="color" id="color" value="#3c8dbc">
          </div>
          <div class="form-group">
            <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
            <textarea class="form-control" name="comment" id="comment"></textarea>
          </div>
          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1">
              <label class="custom-control-label" for="is_default">{{ __('general_content.shift_default_hint_trans_key') }}</label>
            </div>
          </div>
          <div class="card-footer">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </div>
        </x-adminlte-card>
      </form>
    </div>
  </div>
@stop
