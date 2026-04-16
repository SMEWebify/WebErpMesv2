@extends('adminlte::page')

@section('title', __('general_content.dashboard_trans_key'))

@section('content_header')
@stop

@section('content')

  @if($userRoleCount < 1)
  <div class="card">
    <div class="card-body">
        <x-adminlte-alert theme="info" title="Info">
          your account currently have no role defined and the menu has a reduced display.  Contact the administrator or use user demo login for demo page =>
      LOGIN: contact@wem-project.org
      PASSWORD: password
        </x-adminlte-alert>
    </div>
  </div>
  @endif

  <div
    id="home-dashboard-app"
    data-props="{{ json_encode($reactProps) }}"
    style="margin-top: 2rem;"
  ></div>

@stop

@section('css')@stop

@section('js')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
