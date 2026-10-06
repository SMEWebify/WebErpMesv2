@extends('adminlte::page')

@section('title', __('general_content.opportunities_calendar_trans_key'))

@section('content_header')
  <div class="d-flex justify-content-between align-items-center flex-wrap">
    <h1>{{ __('general_content.opportunities_calendar_trans_key') }}</h1>
    <a href="{{ route('opportunities') }}" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-list"></i> {{ __('general_content.opportunities_list_trans_key') }}
    </a>
  </div>
@stop

@section('content')
<div class="card">
  <div class="card-header">
    <div class="row">
      <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
          </div>
          <select id="calendar-user" class="form-control">
            <option value="">{{ __('general_content.all_users_trans_key') }}</option>
            @foreach ($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-md-4 col-sm-6">
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-filter"></i></span>
          </div>
          <select id="calendar-kind" class="form-control">
            <option value="all">{{ __('general_content.activities_trans_key') }} + {{ __('general_content.events_trans_key') }}</option>
            <option value="activities">{{ __('general_content.activities_trans_key') }}</option>
            <option value="events">{{ __('general_content.events_trans_key') }}</option>
          </select>
        </div>
      </div>
    </div>
    <div class="mt-2 small">
      <strong>{{ __('general_content.activities_trans_key') }} :</strong>
      @foreach ($activityTypes as $type)
        <span class="badge mr-1" style="background-color: {{ $type['color'] }}; color: #fff;">{{ $type['label'] }}</span>
      @endforeach
      <span class="badge mr-3" style="background-color: #adb5bd; color: #fff;">{{ __('general_content.closed_trans_key') }}</span>
      <strong>{{ __('general_content.events_trans_key') }} :</strong>
      @foreach ($eventTypes as $type)
        <span class="badge mr-1" style="background-color: {{ $type['color'] }}; color: #fff;">{{ $type['label'] }}</span>
      @endforeach
    </div>
  </div>
  <div class="card-body">
    <div id="calendar"></div>
  </div>
</div>
@stop

@section('css')
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet'>
  <style>
    .fc-event-closed .fc-event-title { text-decoration: line-through; }
  </style>
@stop

@section('js')
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const STORAGE_KEY = 'opportunities-calendar-filters';
      const eventsUrl   = '{{ route('opportunities.calendar.events') }}';
      const userSelect  = document.getElementById('calendar-user');
      const kindSelect  = document.getElementById('calendar-kind');

      try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        if (saved.user !== undefined && userSelect.querySelector('option[value="' + saved.user + '"]')) {
          userSelect.value = saved.user;
        }
        if (saved.kind) kindSelect.value = saved.kind;
      } catch (e) {}

      const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        height: 800,
        themeSystem: 'bootstrap',
        headerToolbar: {
          left:   'prev,next today',
          center: 'title',
          right:  'dayGridMonth,timeGridWeek,listWeek',
        },
        locale: '{{ app()->getLocale() }}',
        events: function (info, successCallback, failureCallback) {
          const params = new URLSearchParams({
            start: info.startStr.substring(0, 10),
            end:   info.endStr.substring(0, 10),
            kind:  kindSelect.value,
          });
          if (userSelect.value) params.append('user_id', userSelect.value);

          fetch(eventsUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(successCallback)
            .catch(failureCallback);
        },
        eventDidMount: function (info) {
          const p = info.event.extendedProps;
          info.el.title = [p.type, p.company, p.user, p.comment].filter(Boolean).join('\n');
        },
      });

      calendar.render();

      function onFilterChange() {
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify({ user: userSelect.value, kind: kindSelect.value }));
        } catch (e) {}
        calendar.refetchEvents();
      }

      userSelect.addEventListener('change', onFilterChange);
      kindSelect.addEventListener('change', onFilterChange);
    });
  </script>
@stop
