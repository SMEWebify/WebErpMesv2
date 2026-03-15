@extends('adminlte::page')

@section('title', __('general_content.logs_activity_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.logs_activity_trans_key') }}</h1>
@stop

@section('content')
<div class="timeline">
    @foreach($taskActivities as $taskActivity)
        <div style=" display: flex; justify-content: space-between; padding: 10px; border-radius: 10px; background-color: 
            @if($taskActivity->type == 1) #3490dc
            @elseif($taskActivity->type == 2) #ffed4a
            @elseif($taskActivity->type == 3) #38c172
            @elseif($taskActivity->type == 4) #4dc0b5
            @elseif($taskActivity->type == 5) #e3342f
            @else #6c757d @endif; margin-bottom: 10px;">
            
            <!-- Première colonne : Type d'activité -->
            <div class="col-2">
                @if($taskActivity->type == 1)
                    <h3><i class="fas fa-play-circle"></i> {{ __('general_content.set_to_start_trans_key') }}</h3>
                @elseif($taskActivity->type == 2)
                    <h3><i class="fas fa-pause-circle"></i> {{ __('general_content.set_to_end_trans_key') }}</h3>
                @elseif($taskActivity->type == 3)
                    <h3><i class="fas fa-check-circle"></i> {{ __('general_content.set_to_finish_trans_key') }}</h3>
                @elseif($taskActivity->type == 4)
                    <h3><i class="fas fa-box"></i> {{ __('general_content.declare_finish_trans_key') }} : {{ $taskActivity->good_qt }} {{ __('general_content.part_trans_key') }}</h3>
                @elseif($taskActivity->type == 5)
                    <h3><i class="fas fa-times-circle"></i> {{ __('general_content.declare_rejected_trans_key') }} : {{ $taskActivity->bad_qt }} {{ __('general_content.part_trans_key') }}</h3>
                @else
                    <h3><i class="fas fa-question-circle"></i> {{ __('general_content.unknown_activity_trans_key') }}</h3>
                @endif
            </div>

            <!-- Deuxième colonne : Date et Utilisateur -->
            <div class="col-3">
                <h3>{{ $taskActivity->GetPrettyCreatedAttribute() }} / {{ $taskActivity->user->name ?? 'System' }}</h3>
            </div>
            
            <!-- Troisième colonne : Détails de la tâche -->
            <div class="col-3"><h3>
                <a href="{{ route('production.task.statu.id', ['id' =>  $taskActivity->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                
                {{ __('general_content.task_trans_key') }} : #{{ $taskActivity->task_id }} - {{ $taskActivity->Tasks->label }}</h3>
            </div>

            <!-- Quatrième colonne : Commentaire -->
            <div class="col-3">
                <x-OrderButton id="{{ $taskActivity->Tasks->OrderLines->orders_id }}" code="{{ $taskActivity->Tasks->OrderLines->order->code }} #{{ __('general_content.line_trans_key') }} {{ $taskActivity->Tasks->OrderLines->label }}"  /> 
            </div>

        </div>
    @endforeach
</div>
@stop

@section('css')

@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            // Cache la sidebar dès le chargement
            $("body").addClass("sidebar-hidden");
        });
    </script>

    <script src="{{ asset(Vite::asset('js/app.js')) }}"></script>
    <script>
        Echo.channel('TaskActivity')
        .listen('.task.activity.triggered', function(data) {
            setTimeout(() => {
                location.reload(); 
            }, 200);
        });
    </script>
@stop