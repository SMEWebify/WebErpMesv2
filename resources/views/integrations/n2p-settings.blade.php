@extends('adminlte::page')

@section('title', 'Nest2Prod')

@section('content_header')
    <h1>Nest2Prod</h1>
@stop

@section('content')
    @if(session('success'))
        <x-adminlte-alert theme="success" title="{{ __('general_content.success_trans_key') }}">
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    <x-adminlte-card title="Configuration" theme="primary" theme-mode="outline">
        <form method="POST" action="{{ route('admin.integrations.n2p.update') }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4">
                    <x-adminlte-input-switch name="n2p_enabled" label="Activer l'intégration" data-on-text="Oui" data-off-text="Non" :checked="old('n2p_enabled', $settings['n2p_enabled'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_base_url" label="Base URL" placeholder="https://n2p.example.com" :value="old('n2p_base_url', $settings['n2p_base_url'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_api_token" label="API Token" type="password" :value="old('n2p_api_token', $settings['n2p_api_token'])"/>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_send_on_order_status_from" label="Status source" placeholder="OPEN" :value="old('n2p_send_on_order_status_from', $settings['n2p_send_on_order_status_from'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_send_on_order_status_to" label="Status cible" placeholder="IN_PROGRESS" :value="old('n2p_send_on_order_status_to', $settings['n2p_send_on_order_status_to'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_job_status_on_send" label="Status job N2P" placeholder="released" :value="old('n2p_job_status_on_send', $settings['n2p_job_status_on_send'])"/>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-adminlte-input name="n2p_priority_default" label="Priorité par défaut (1..5)" type="number" min="1" max="5" :value="old('n2p_priority_default', $settings['n2p_priority_default'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input-switch name="n2p_send_tasks" label="Inclure les tâches" data-on-text="Oui" data-off-text="Non" :checked="old('n2p_send_tasks', $settings['n2p_send_tasks'])"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input-switch name="n2p_verify_ssl" label="Vérifier le certificat SSL" data-on-text="Oui" data-off-text="Non" :checked="old('n2p_verify_ssl', $settings['n2p_verify_ssl'])"/>
                </div>
            </div>

            <div class="mt-3">
                <x-adminlte-button type="submit" label="{{ __('general_content.save_trans_key') }}" theme="primary" icon="fas fa-save"/>
            </div>
        </form>
    </x-adminlte-card>
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
<script>
    $(function () {
        $("input[data-bootstrap-switch]").each(function () {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });
    });
</script>
@stop
