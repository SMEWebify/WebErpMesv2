@php
    $logsEndpoints = [
        'meta' => route('admin.logs-viewer.json.meta'),
        'list' => route('admin.logs-viewer.json.list'),
    ];
    $logsTrans = [
        'user'       => __('general_content.user_trans_key'),
        'view_all'   => __('general_content.view_all_trans_key'),
        'start_date' => __('general_content.start_date_trans_key'),
        'end_date'   => __('general_content.end_date_trans_key'),
        'submit'     => __('general_content.submit_trans_key'),
        'label'      => __('general_content.label_trans_key'),
        'created_at' => __('general_content.created_trans_key'),
        'no_data'    => __('general_content.no_data_trans_key'),
    ];
@endphp
<div id="logs-viewer-app"
     @if(!empty($logsSubjectType)) data-subject-type="{{ $logsSubjectType }}" @endif
     @if(!empty($logsSubjectId))   data-subject-id="{{ $logsSubjectId }}"     @endif
     data-endpoints='@json($logsEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
     data-trans='@json($logsTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
></div>
