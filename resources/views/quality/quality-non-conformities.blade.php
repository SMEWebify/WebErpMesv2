@extends('adminlte::page')

@section('title', __('general_content.non_conformities_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.non_conformities_trans_key') }}</h1>
@stop

@section('content')
    <x-InfocalloutComponent note="{{ __('general_content.non_conformitie_note_trans_key') }}" />

    <div
        id="non-conformities-index-app"
        data-endpoints="{{ json_encode($endpoints) }}"
        data-next-code="{{ $codeNonConformity }}"
        data-users="{{ json_encode($userSelect) }}"
        data-services="{{ json_encode($ServicesSelect) }}"
        data-companies="{{ json_encode($CompanieSelect) }}"
        data-failures="{{ json_encode($FailuresSelect) }}"
        data-causes="{{ json_encode($CausesSelect) }}"
        data-corrections="{{ json_encode($CorrectionsSelect) }}"
        data-trans="{{ json_encode([
            'search'                => __('general_content.search_trans_key'),
            'results'               => 'résultats',
            'no_data'               => __('general_content.no_data_trans_key'),
            'external_id'           => __('general_content.external_id_trans_key'),
            'label'                 => __('general_content.label_trans_key'),
            'user'                  => __('general_content.user_trans_key'),
            'type'                  => __('general_content.type_trans_key'),
            'status'                => __('general_content.status_trans_key'),
            'company'               => __('general_content.customer_trans_key'),
            'order'                 => __('general_content.order_trans_key'),
            'task'                  => __('general_content.task_trans_key'),
            'delivery_notes'        => __('general_content.delivery_notes_trans_key'),
            'created_at'            => __('general_content.created_at_trans_key'),
            'actions'               => __('general_content.action_trans_key'),
            'internal'              => __('general_content.internal_trans_key'),
            'external'              => __('general_content.external_trans_key'),
            'in_progress'           => __('general_content.in_progress_trans_key'),
            'waiting_customer_data' => __('general_content.waiting_customer_data_trans_key'),
            'solved'                => __('general_content.solved_trans_key'),
            'canceled'              => __('general_content.canceled_trans_key'),
            'view'                  => __('general_content.view_trans_key'),
            'edit'                  => __('general_content.edit_trans_key'),
            'update'                => __('general_content.update_trans_key'),
            'submit'                => __('general_content.submit_trans_key'),
            'cancel'                => __('general_content.cancel_trans_key'),
            'saving'                => __('general_content.saving_trans_key'),
            'close'                 => __('general_content.close_trans_key'),
            'reopen'                => __('general_content.reopen_trans_key'),
            'info'                  => __('general_content.info_trans_key'),
            'failure'               => __('general_content.failure_trans_key'),
            'failure_comment'       => __('general_content.failure_comment_trans_key'),
            'cause'                 => __('general_content.cause_trans_key'),
            'cause_comment'         => __('general_content.cause_comment_trans_key'),
            'correction'            => __('general_content.correction_trans_key'),
            'correction_comment'    => __('general_content.correction_comment_trans_key'),
            'service'               => __('general_content.service_trans_key'),
            'qty'                   => __('general_content.qty_trans_key'),
            'resolution_date'       => 'Date de résolution',
            'failure_type'          => __('general_content.failure_type_trans_key'),
            'cause_type'            => __('general_content.cause_type_trans_key'),
            'correction_type'       => __('general_content.correction_type_trans_key'),
            'new_non_conformity'    => __('general_content.new_non_conformitie_trans_key'),
            'locked_by_order'       => 'Verrouillé (lié à une commande)',
            'error'                 => 'Erreur',
        ]) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop


@section('js')
@stop
