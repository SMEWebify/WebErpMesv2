@extends('adminlte::page')

@section('title', $inventory->code)

@section('content_header')
  <h1>
    {{ $inventory->code }}
    <small class="text-muted">{{ $inventory->label }}</small>
  </h1>
@stop

@php
  $reactEndpoints = [
    'showJson'      => route('products.inventories.show.json', ['id' => $inventory->id]),
    'export'        => route('products.inventories.export',  ['id' => $inventory->id, 'blind' => 0]),
    'exportBlind'   => route('products.inventories.export',  ['id' => $inventory->id, 'blind' => 1]),
    'importPreview' => route('products.inventories.import.preview', ['id' => $inventory->id]),
    'import'        => route('products.inventories.import',  ['id' => $inventory->id]),
    'validate'      => route('products.inventories.validate', ['id' => $inventory->id]),
    'cancel'        => route('products.inventories.cancel',  ['id' => $inventory->id]),
    'index'         => route('products.inventories.index'),
    'fileRaw'       => $inventory->file_id ? route('files.raw', ['file' => $inventory->file_id]) : null,
  ];

  $reactTrans = [
    'title'                    => $inventory->code,
    'status'                   => __('general_content.status_trans_key'),
    'status_draft'             => __('general_content.status_draft_trans_key'),
    'status_exported'          => __('general_content.status_exported_trans_key'),
    'status_validated'         => __('general_content.status_validated_trans_key'),
    'status_cancelled'         => __('general_content.status_cancelled_trans_key'),
    'export_step'              => __('general_content.inventory_export_step_trans_key'),
    'export_normal'            => __('general_content.inventory_export_normal_trans_key'),
    'export_blind'             => __('general_content.inventory_export_blind_trans_key'),
    'export_hint'              => __('general_content.inventory_export_hint_trans_key'),
    'import_step'              => __('general_content.inventory_import_step_trans_key'),
    'import_hint'              => __('general_content.inventory_import_hint_trans_key'),
    'drop_file'                => __('general_content.drop_xlsx_file_trans_key'),
    'preview'                  => __('general_content.preview_trans_key'),
    'import'                   => __('general_content.import_trans_key'),
    'summary_step'             => __('general_content.inventory_summary_step_trans_key'),
    'total_lines'              => __('general_content.total_lines_trans_key'),
    'counted_lines'            => __('general_content.counted_lines_trans_key'),
    'positive_variance'        => __('general_content.positive_variance_trans_key'),
    'negative_variance'        => __('general_content.negative_variance_trans_key'),
    'net_variance'             => __('general_content.net_variance_trans_key'),
    'validate'                 => __('general_content.validate_trans_key'),
    'cancel'                   => __('general_content.cancel_trans_key'),
    'confirm_validate'         => __('general_content.inventory_confirm_validate_trans_key'),
    'confirm_cancel'           => __('general_content.confirm_cancel_trans_key'),
    'errors_found'             => __('general_content.errors_found_trans_key'),
    'row'                      => __('general_content.row_trans_key'),
    'back'                     => __('general_content.back_trans_key'),
    'download_counting_file'   => __('general_content.download_counting_file_trans_key'),
    'entry_move'               => __('general_content.entry_move_trans_key'),
    'exit_move'                => __('general_content.exit_move_trans_key'),
    'importing'                => __('general_content.importing_trans_key'),
    'reimport_hint'            => __('general_content.reimport_hint_trans_key'),
    'analysing'                => __('general_content.analysing_trans_key'),
    'locale'                   => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="inventory-show-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
