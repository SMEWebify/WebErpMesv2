@extends('adminlte::page')

@section('title', __('general_content.import_export_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.import_export_trans_key') }} <x-guide-help guide="mise-en-route" section="reprendre-vos-donnees" /></h1>
@stop

@section('content')
<div class="card mb-3">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item "><a class="nav-link active" href="#FECExport" data-bs-toggle="tab">{{ __('general_content.export_fec_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#InvoicesExport" data-bs-toggle="tab">{{ __('general_content.invoices_export_trans_key') }}</a></li>
            <li class="nav-item "><a class="nav-link" href="#DataImport" data-bs-toggle="tab"><i class="fa-solid fa-file-import me-1"></i>{{ __('imports.wizard.tab') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            
            @include('include.alert-result')

            <div class="tab-pane active" id="FECExport">
                <div class="text-end"><x-guide-help guide="comptabilite" section="transmettre-au-comptable" /></div>
                @php
                $fecProps = [
                    'endpoints' => [
                        'list'   => route('admin.fec.export.json.list'),
                        'export' => route('admin.fec.export', ['ext' => '__EXT__']),
                    ],
                    'trans' => [
                        'start_date' => __('general_content.start_date_trans_key'),
                        'end_date'   => __('general_content.end_date_trans_key'),
                        'submit'     => __('general_content.submit_trans_key'),
                        'add_export' => __('general_content.add_export_trans_key'),
                        'no_data'    => __('general_content.no_data_trans_key'),
                    ],
                ];
                @endphp
                <div
                    id="fec-export-lines-app"
                    data-endpoints="{{ json_encode($fecProps['endpoints']) }}"
                    data-trans="{{ json_encode($fecProps['trans']) }}"
                    data-start-date="{{ $fecStartDate }}"
                    data-end-date="{{ $fecEndDate }}"
                ></div>
            </div>
            <div class="tab-pane" id="InvoicesExport">
                @php
                $invoiceExportProps = [
                    'endpoints' => [
                        'list'     => route('admin.invoice.export.json.list'),
                        'export'   => route('admin.invoice.export', ['ext' => '__EXT__']),
                        'order'    => route('orders.show', ['id' => '__ID__']),
                        'companie' => route('companies.show', ['id' => '__ID__']),
                    ],
                    'trans' => [
                        'order'          => __('general_content.order_trans_key'),
                        'invoice'        => __('general_content.invoice_trans_key'),
                        'customer'       => __('general_content.customer_trans_key'),
                        'external_id'    => __('general_content.external_id_trans_key'),
                        'description'    => __('general_content.description_trans_key'),
                        'qty'            => __('general_content.qty_trans_key'),
                        'unit'           => __('general_content.unit_trans_key'),
                        'price'          => __('general_content.price_trans_key'),
                        'discount'       => __('general_content.discount_trans_key'),
                        'vat'            => __('general_content.vat_trans_key'),
                        'select_to_export' => __('general_content.select_to_export_trans_key'),
                        'add_export'     => __('general_content.add_export_trans_key'),
                        'no_data'        => __('general_content.no_data_trans_key'),
                    ],
                ];
                @endphp
                <div
                    id="invoice-export-lines-app"
                    data-endpoints="{{ json_encode($invoiceExportProps['endpoints']) }}"
                    data-trans="{{ json_encode($invoiceExportProps['trans']) }}"
                ></div>
            </div>
            <div class="tab-pane" id="DataImport">
                <div class="text-end"><x-guide-help guide="mise-en-route" section="reprendre-vos-donnees" /></div>
                {{-- Assistant d'import : components/imports/ImportWizard.jsx --}}
                <div id="import-wizard-app" data-props="{{ json_encode($importWizard) }}"></div>
            </div>
        </div>
    </div>
</div>
@stop


@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
<script>
    // Onglet ouvert par l'adresse (#DataImport, lien du guide ou du menu).
    document.addEventListener('DOMContentLoaded', () => {
        const link = location.hash && document.querySelector(`.nav-pills a[href="${location.hash}"]`);
        if (link && window.bootstrap) window.bootstrap.Tab.getOrCreateInstance(link).show();
    });
</script>
@stop
