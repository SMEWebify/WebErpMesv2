
@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@stop

@section('content')

<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#Profil" data-toggle="tab">Profil setting</a></li>
            <li class="nav-item"><a class="nav-link" href="#History" data-toggle="tab">Notification history</a></li> 
        </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Profil">
                <div class="row">
                    @livewire('user-profile')

                    <div class="col-md-3">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Information</h3>
                            </div>
                            <div class="card-body">
                                Account created at : {{ $UserProfil->GetPrettyCreatedAttribute() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="History">
                @include('include.alert-result')
                <div class="row">
                    @livewire('notification-line')
                    <div class="col-md-3">
                        <form method="POST" action="{{ route('notifications.setting') }}" >
                            @csrf
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Notification choice </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New companie</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->companies_notification == 1)  
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal"  checked />
                                            @else
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal"  />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New user</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->users_notification == 1)  
                                            <x-adminlte-input-switch name="users_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="users_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New quote</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->quotes_notification == 1)  
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New order</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->orders_notification == 1)  
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="success" icon="fas fa-lg fa-save"/>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.card -->
@stop

@section('plugins.BootstrapSwitch', true)


@section('css')
@stop

@section('js')
@stop