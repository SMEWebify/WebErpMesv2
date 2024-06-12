@extends('adminlte::page')

@section('title', __('general_content.users_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.users_list_trans_key') }}</h1>
@stop

@section('content')
  <div class="row">
      @foreach ($Users as $User)
      <div class="col-3">
        <x-adminlte-profile-widget name="{{ $User->name }}" desc="{{ $User->GetPrettyCreatedAttribute() }}" theme="primary"
          img="{{ $User->adminlte_image() }}">
          <x-adminlte-profile-col-item class="text-primary border-right" icon="far fa-envelope" title="E-mail" text="{{ $User->email }}" size=6 badge="primary"/>
          <x-adminlte-profile-col-item class="text-danger" icon="fas fa-lg fa-phone" title="{{ __('general_content.phone_trans_key') }}" text="{{ $User->personnal_phone_number }}" size=6 badge="danger"/>
        </x-adminlte-profile-widget>
      </div>
      @endforeach
  </div>
  <!-- /.row -->
  <div class="row">
    <div class="col-5">
      {{ $Users->links() }}
    </div>
  </div>
  <!-- /.row -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop