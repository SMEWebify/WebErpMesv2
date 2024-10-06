@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
<div class="andon-table">
    @foreach($andonAlerts as $alert)
        <div style="color: white; display: flex; justify-content: space-between; padding: 10px; border-radius: 10px; background-color: {{ $alert->status == 1 ? '#e3342f' : ($alert->status == 2 ? '#3490dc' : '#38c172') }}; margin-bottom: 10px;">
            <!-- Première colonne : Type et Déclenchement -->
            <div class="col-3">
                <h3>{{ $alert->type }}</h3>
                <small>{{ $alert->GetPrettyCreatedAttribute() }} par {{ $alert->UserManagement['name'] }}</small>
            </div>
            
            <!-- Deuxième colonne : Description -->
            <div class="col-2">
                <p>{{ $alert->description }}</p>
            </div>
            
            <!-- Troisième colonne : Lien à la tâche ou à la ressource -->
            <div class="col-2">
                @if($alert->task)
                    <p>{{ __('general_content.task_trans_key') }}  : #{{ $alert->task->id }} - {{ $alert->task->label }}</p>
                @elseif($alert->resource)
                    <p>{{ __('general_content.ressource_trans_key') }} : {{ $alert->resource->label }}</p>
                @else
                    <p>Aucune tâche ou ressource liée</p>
                @endif
            </div>

            <!-- Quatrième colonne : Résolution -->
            <div class="col-2">
                @if($alert->status == 3)
                    <small>{{ __('general_content.solved_trans_key') }} {{ $alert->GetPrettyResolveddAttribute() }}</small>
                @elseif($alert->status == 2)
                    <small>{{ __('general_content.in_progress_trans_key') }}</small>
                @else
                    <small>{{ __('general_content.open_trans_key') }}</small> 
                @endif
            </div>

            <div class="col-2">
                @if($alert->status == 3)
                    {{ __('general_content.solved_trans_key') }} {{ $alert->getTimeToResolveAttribute() }}
                    
                @elseif($alert->status == 2)
                    <form action="{{ route('workshop.andon.resolve', ['id'=> $alert->id ]) }}" method="POST">
                        <div class="card-footer">
                            @csrf
                            <button type="submit" class="btn btn-danger">{{ __('general_content.solved_trans_key') }}</button>
                        </div>
                    </form>
                @else
                    <form action="{{ route('workshop.andon.inProgress', $alert->id) }}" method="POST">
                        <div class="card-footer">
                            @csrf
                            <button type="submit" class="btn btn-primary">{{ __('general_content.in_progress_trans_key') }}</button>
                        </div>
                    </form>
                @endif
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
        // Ajoute la classe sidebar-hidden à la balise body dès que la page est chargée
        $("body").addClass("sidebar-hidden");
        });
    </script>

    <script src="{{ mix('js/app.js') }}"></script>
    <script>
        Echo.channel('AndonAlert')
        .listen('.andon.alert.triggered', function(data) {
                setTimeout(() => {
                location.reload(); 
            }, 200);
        });
    </script>
@stop