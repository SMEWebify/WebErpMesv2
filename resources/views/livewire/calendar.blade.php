<div>
    <div>
        <div id="calendar-container" wire:ignore>
            <div id="calendar"></div>
        </div>
    </div>
</div>

@section('js')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
    <script>
        document.addEventListener('livewire:initialized', function () {
            
            var data =   @this.events;
            const Calendar = FullCalendar.Calendar;
            const calendarEl = document.getElementById('calendar');
            const calendar = new Calendar(calendarEl, 
            {
                events: JSON.parse(data),
                eventColor: '#378006',
                height: 800,
                themeSystem: 'bootstrap',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                locale: '{{ config('app.locale') }}',
            });
            
            calendar.render();
        });
    </script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.css' rel='stylesheet'>
@stop
