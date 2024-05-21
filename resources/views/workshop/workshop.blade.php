@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
<div class="container mt-5">

    <div class="row">
        <div class="col-md-4 offset-md-2">
            <div class="card text-center mb-4" style="background-color: #17a2b8;">
                <div class="card-body">
                    <h5 class="card-title text-white">{{ __('general_content.tasks_list_trans_key') }}</h5> <span class="badge badge-danger right">Beta</span>
                    <p class="card-text text-white">{{ __('general_content.note_1_trans_key') }}</p>
                    <a href="{{ route('workshop.task.lines') }}" class="btn btn-primary btn-lg btn-block">{{ __('general_content.to_access_trans_key') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center mb-4" style="background-color: #28a745;">
                <div class="card-body">
                    <h5 class="card-title text-white">{{ __('general_content.production_declaration_trans_key') }}</h5><span class="badge badge-danger right">Beta</span>
                    <p class="card-text text-white">{{ __('general_content.note_2_trans_key') }}</p>
                    <a href="{{ route('workshop.task.statu') }}" class="btn btn-primary btn-lg btn-block">{{ __('general_content.to_access_trans_key') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 offset-md-2">
            <div class="card text-center mb-4" style="background-color: #dc3545;"> 
                <div class="card-body">
                    <h5 class="card-title text-white">Stocks</h5> <span class="badge badge-danger right">Beta</span>
                    <p class="card-text text-white"></p>
                    <a href="{{ route('workshop.stock.detail') }}" class="btn btn-primary btn-lg btn-block">{{ __('general_content.to_access_trans_key') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center mb-4" style="background-color: #ffc107;"> 
                <div class="card-body">
                    <h5 class="card-title text-white">Reports</h5> <span class="badge badge-info right">Soon</span>
                    <p class="card-text text-white"></p>
                    <a href="#" class="btn btn-primary btn-lg btn-block">{{ __('general_content.to_access_trans_key') }}</a>
                </div>
            </div>
        </div>
    </div>
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
@stop