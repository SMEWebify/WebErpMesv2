@extends('adminlte::page')

@section('title',  __('general_content.companie_trans_key')) 

@section('content_header')
  <x-Content-header-previous-button  h1="{{ $Companie->label }}" previous="{{ $previousUrl }}" list="{{ route('companies') }}" next="{{ $nextUrl }}"/>
@stop

@php
  $reactQuotesEndpoints = [
    'list'         => route('quotes.json.list'),
    'store'        => route('quotes.json.store'),
    'selectData'   => route('quotes.json.select-data'),
    'addresses'    => route('quotes.json.addresses',    ['companyId' => '__ID__']),
    'contacts'     => route('quotes.json.contacts',     ['companyId' => '__ID__']),
    'storeAddress' => route('quotes.json.address.store'),
    'storeContact' => route('quotes.json.contact.store'),
  ];
  $reactQuotesTrans = [
    'dashboard'             => __('general_content.dashboard_trans_key'),
    'quotes_list'           => __('general_content.quotes_list_trans_key'),
    'open'                  => __('general_content.open_trans_key'),
    'send'                  => __('general_content.send_trans_key'),
    'win'                   => __('general_content.win_trans_key'),
    'lost'                  => __('general_content.lost_trans_key'),
    'closed'                => __('general_content.closed_trans_key'),
    'obsolete'              => __('general_content.obsolete_trans_key'),
    'new_quote'             => __('general_content.new_quote_trans_key'),
    'search'                => __('general_content.search_trans_key'),
    'code'                  => 'Code',
    'label'                 => __('general_content.label_trans_key'),
    'company'               => __('general_content.name_company_trans_key'),
    'contact'               => __('general_content.contact_trans_key'),
    'address'               => __('general_content.new_address_trans_key'),
    'validity_date'         => __('general_content.validity_date_trans_key'),
    'status'                => __('general_content.status_trans_key'),
    'lines'                 => __('general_content.quote_line_trans_key'),
    'created_at'            => __('general_content.created_at_trans_key'),
    'customer_reference'    => __('general_content.customer_reference_trans_key'),
    'payment_condition'     => __('general_content.payment_conditions_trans_key'),
    'payment_method'        => __('general_content.payment_method_trans_key'),
    'delivery'              => __('general_content.delivery_constraint_trans_key'),
    'assignee'              => __('general_content.user_management_trans_key'),
    'comment'               => __('general_content.comment_trans_key'),
    'ordre'                 => __('general_content.ordre_trans_key'),
    'adress_label'          => __('general_content.adress_name_trans_key'),
    'adress'                => __('general_content.adress_trans_key'),
    'postal_code'           => __('general_content.postal_code_trans_key'),
    'city'                  => __('general_content.city_trans_key'),
    'country'               => __('general_content.country_trans_key'),
    'phone'                 => __('general_content.phone_trans_key'),
    'email'                 => __('general_content.email_trans_key'),
    'civility'              => __('general_content.civility_trans_key'),
    'first_name'            => __('general_content.first_name_trans_key'),
    'name'                  => __('general_content.name_trans_key'),
    'function'              => __('general_content.function_trans_key'),
    'mobile'                => __('general_content.mobile_phone_trans_key'),
    'new_address'           => __('general_content.new_address_trans_key'),
    'new_contact'           => __('general_content.new_companie_trans_key'),
    'save'                  => __('general_content.save_trans_key'),
    'saving'                => __('general_content.saving_trans_key'),
    'cancel'                => __('general_content.cancel_trans_key'),
    'no_results'            => __('general_content.no_results_trans_key'),
    'total'                 => __('general_content.total_trans_key'),
    'external_id'           => __('general_content.external_id_trans_key'),
    'client'                => __('general_content.client_trans_key'),
    'currency'              => app('Factory')->curency ?? 'EUR',
    'locale'                => str_replace('_', '-', config('app.locale')),
  ];

  $reactOrdersEndpoints = [
    'list'         => route('orders.json.list'),
    'store'        => route('orders.json.store'),
    'selectData'   => route('orders.json.select-data'),
    'addresses'    => route('orders.json.addresses',    ['companyId' => '__ID__']),
    'contacts'     => route('orders.json.contacts',     ['companyId' => '__ID__']),
    'storeAddress' => route('orders.json.address.store'),
    'storeContact' => route('orders.json.contact.store'),
  ];
  $reactOrdersTrans = [
    'dashboard'         => __('general_content.dashboard_trans_key'),
    'orders_list'       => __('general_content.orders_list_trans_key'),
    'open'              => __('general_content.open_trans_key'),
    'in_progress'       => __('general_content.in_progress_trans_key'),
    'delivered'         => __('general_content.delivered_trans_key'),
    'partly_delivered'  => __('general_content.partly_delivered_trans_key'),
    'stopped'           => __('general_content.stopped_trans_key'),
    'canceled'          => __('general_content.canceled_trans_key'),
    'new_order'         => __('general_content.new_order_trans_key'),
    'search'            => __('general_content.search_trans_key'),
    'code'              => 'Code',
    'label'             => __('general_content.label_trans_key'),
    'company'           => __('general_content.name_company_trans_key'),
    'contact'           => __('general_content.contact_trans_key'),
    'address'           => __('general_content.new_address_trans_key'),
    'validity_date'     => __('general_content.validity_date_trans_key'),
    'status'            => __('general_content.status_trans_key'),
    'lines'             => __('general_content.order_line_trans_key'),
    'created_at'        => __('general_content.created_at_trans_key'),
    'customer_reference'=> __('general_content.customer_reference_trans_key'),
    'total_amount'      => __('general_content.total_trans_key'),
    'payment_condition' => __('general_content.payment_conditions_trans_key'),
    'payment_method'    => __('general_content.payment_method_trans_key'),
    'delivery'          => __('general_content.delivery_constraint_trans_key'),
    'assignee'          => __('general_content.user_management_trans_key'),
    'comment'           => __('general_content.comment_trans_key'),
    'order_type'        => __('general_content.order_type_trans_key'),
    'customer_type'     => __('general_content.customer_type_order_trans_key'),
    'internal_type'     => __('general_content.internal_type_order_trans_key'),
    'orders'            => __('general_content.orders_trans_key'),
    'page'              => 'Page',
    'ordre'             => __('general_content.ordre_trans_key'),
    'adress_label'      => __('general_content.adress_name_trans_key'),
    'adress'            => __('general_content.adress_trans_key'),
    'postal_code'       => __('general_content.postal_code_trans_key'),
    'city'              => __('general_content.city_trans_key'),
    'country'           => __('general_content.country_trans_key'),
    'phone'             => __('general_content.phone_trans_key'),
    'email'             => __('general_content.email_trans_key'),
    'civility'          => __('general_content.civility_trans_key'),
    'first_name'        => __('general_content.first_name_trans_key'),
    'name'              => __('general_content.name_trans_key'),
    'function'          => __('general_content.function_trans_key'),
    'mobile'            => __('general_content.mobile_phone_trans_key'),
    'new_address'       => __('general_content.new_address_trans_key'),
    'new_contact'       => __('general_content.new_companie_trans_key'),
    'save'              => __('general_content.save_trans_key'),
    'saving'            => __('general_content.saving_trans_key'),
    'cancel'            => __('general_content.cancel_trans_key'),
    'no_results'        => __('general_content.no_results_trans_key'),
    'total'             => __('general_content.total_trans_key'),
    'currency'          => app('Factory')->curency ?? 'EUR',
    'locale'            => str_replace('_', '-', config('app.locale')),
  ];

  $companyFormData = [
    'id'                        => $Companie->id,
    'code'                      => $Companie->code,
    'label'                     => $Companie->label,
    'active'                    => (bool) $Companie->active,
    'client_type'               => $Companie->client_type,
    'civility'                  => $Companie->civility,
    'last_name'                 => $Companie->last_name,
    'user_id'                   => $Companie->user_id,
    'website'                   => $Companie->website,
    'fbsite'                    => $Companie->fbsite,
    'twittersite'               => $Companie->twittersite,
    'lkdsite'                   => $Companie->lkdsite,
    'siren'                     => $Companie->siren,
    'naf_code'                  => $Companie->naf_code,
    'intra_community_vat'       => $Companie->intra_community_vat,
    'electronic_address'        => $Companie->electronic_address,
    'electronic_address_scheme' => $Companie->electronic_address_scheme ?? '0225',
    'statu_customer'            => $Companie->statu_customer,
    'discount'                  => $Companie->discount,
    'account_general_customer'  => $Companie->account_general_customer,
    'account_auxiliary_customer'=> $Companie->account_auxiliary_customer,
    'statu_supplier'            => $Companie->statu_supplier,
    'recept_controle'           => $Companie->recept_controle,
    'account_general_supplier'  => $Companie->account_general_supplier,
    'account_auxiliary_supplier'=> $Companie->account_auxiliary_supplier,
    'latitude'                  => $Companie->latitude,
    'longitude'                 => $Companie->longitude,
    'delivery_constraint'       => $Companie->delivery_constraint,
    'tolerance_days'            => $Companie->tolerance_days,
    'quoted_delivery_note'      => (bool) $Companie->quoted_delivery_note,
    'comment'                   => $Companie->comment,
    'barcode_value'             => $Companie->barcode_value,
  ];

  $companyFormUsers = $userSelect->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values();

  $companyFormTrans = [
    'general_information'    => __('general_content.general_information_trans_key'),
    'external_id'            => __('general_content.external_id_trans_key'),
    'active'                 => __('general_content.active_trans_key'),
    'name_company'           => __('general_content.name_company_trans_key'),
    'civility'               => __('general_content.civility_trans_key'),
    'first_name'             => __('general_content.first_name_trans_key'),
    'contact_name'           => __('general_content.contact_name_trans_key'),
    'user_management'        => __('general_content.user_management_trans_key'),
    'select_user'            => __('general_content.select_user_management_trans_key'),
    'site_link'              => __('general_content.site_link_trans_key'),
    'administrative_information' => __('general_content.administrative_information_trans_key'),
    'reg_number'             => __('general_content.reg_number_trans_key'),
    'naf_code'               => __('general_content.naf_code_trans_key'),
    'vat_number'             => __('general_content.vat_number_trans_key'),
    'status_client'          => __('general_content.status_client_trans_key'),
    'select_status'          => __('general_content.select_status_trans_key'),
    'inactive'               => __('general_content.inactive_trans_key'),
    'prospect'               => __('general_content.prospect_trans_key'),
    'discount'               => __('general_content.discount_trans_key'),
    'general_account'        => __('general_content.general_account_trans_key'),
    'auxiliary_account'      => __('general_content.auxiliary_account_trans_key'),
    'status_supplier'        => __('general_content.status_supplier_trans_key'),
    'reception_control'      => __('general_content.reception_control_trans_key'),
    'select_control'         => __('general_content.select_controle_trans_key'),
    'yes'                    => __('general_content.yes_trans_key'),
    'no'                     => __('general_content.no_trans_key'),
    'latitude'               => __('general_content.latitude_trans_key'),
    'longitude'              => __('general_content.longitude_trans_key'),
    'delivery_constraint'    => __('general_content.delivery_constraint_trans_key'),
    'no_constraints'         => __('general_content.no_constraints_trans_key'),
    'no_tolerance'           => __('general_content.no_tolerance_trans_key'),
    'tolerance_in_days'      => __('general_content.tolerance_in_days_trans_key'),
    'tolerance_days'         => __('general_content.tolerance_days_trans_key'),
    'quoted_delivery_note'   => __('general_content.quoted_delivery_note_trans_key'),
    'comment'                => __('general_content.comment_trans_key'),
    'update'                 => __('general_content.update_trans_key'),
    'saving'                 => __('general_content.saving_trans_key'),
    'save_success'           => __('general_content.success_update_trans_key'),
  ];

  $reactInvoicesEndpoints = [
    'list' => route('invoices.json.list'),
  ];
  $reactInvoicesTrans = [
    'dashboard'        => __('general_content.dashboard_trans_key'),
    'invoices_list'    => __('general_content.invoices_list_trans_key'),
    'in_progress'      => __('general_content.in_progress_trans_key'),
    'send'             => __('general_content.send_trans_key'),
    'pending'          => __('general_content.pending_trans_key'),
    'unpaid'           => __('general_content.unpaid_trans_key'),
    'paid'             => __('general_content.paid_trans_key'),
    'search'           => __('general_content.search_trans_key'),
    'code'             => 'Code',
    'client'           => __('general_content.customer_trans_key'),
    'label'            => __('general_content.label_trans_key'),
    'contact'          => __('general_content.contact_trans_key'),
    'due_date'         => __('general_content.due_date_trans_key'),
    'status'           => __('general_content.status_trans_key'),
    'lines'            => __('general_content.lines_count_trans_key'),
    'created_at'       => __('general_content.created_at_trans_key'),
    'total'            => __('general_content.total_price_trans_key'),
    'no_results'       => __('general_content.no_data_trans_key'),
    'view'             => __('general_content.view_trans_key'),
    'currency'         => app('Factory')->curency ?? 'EUR',
    'locale'           => str_replace('_', '-', config('app.locale')),
  ];

  $reactDeliverysEndpoints = [
    'list'    => route('deliverys.json.list'),
    'request' => route('deliverys-request'),
  ];
  $reactDeliverysTrans = [
    'in_progress'     => __('general_content.in_progress_trans_key'),
    'send'            => __('general_content.send_trans_key'),
    'chargeable'      => __('general_content.chargeable_trans_key'),
    'not_chargeable'  => __('general_content.not_chargeable_trans_key'),
    'partly_invoiced' => __('general_content.partly_invoiced_trans_key'),
    'invoiced'        => __('general_content.invoiced_trans_key'),
    'total'           => __('general_content.total_trans_key'),
    'code'            => __('general_content.id_trans_key'),
    'label'           => __('general_content.label_trans_key'),
    'company'         => __('general_content.customer_trans_key'),
    'lines'           => __('general_content.lines_count_trans_key'),
    'status'          => __('general_content.status_trans_key'),
    'invoice_status'  => __('general_content.invoice_status_trans_key'),
    'user'            => __('general_content.user_trans_key'),
    'created_at'      => __('general_content.created_at_trans_key'),
    'action'          => __('general_content.action_trans_key'),
    'search'          => __('general_content.search_trans_key'),
    'new_delivery'    => __('general_content.new_trans_key'),
    'no_results'      => __('general_content.no_data_trans_key'),
    'statistiques'    => __('general_content.statistiques_trans_key'),
    'monthly_recap'   => __('general_content.monthly_recap_report_trans_key'),
    'view'            => __('general_content.view_trans_key'),
    'columns'         => __('general_content.columns_trans_key'),
    'currency'        => app('Factory')->curency ?? 'EUR',
    'locale'          => str_replace('_', '-', config('app.locale')),
  ];

  $addressesTrans = [
    'addresses'   => __('general_content.adress_trans_key'),
    'new_address' => __('general_content.new_address_trans_key'),
    'sort'        => __('general_content.sort_trans_key'),
    'label'       => __('general_content.label_trans_key'),
    'adress'      => __('general_content.adress_name_trans_key'),
    'zipcode'     => __('general_content.postal_code_trans_key'),
    'city'        => __('general_content.city_trans_key'),
    'province'    => __('general_content.province_trans_key'),
    'country'     => __('general_content.country_trans_key'),
    'phone'       => __('general_content.phone_trans_key'),
    'email'       => __('general_content.email_trans_key'),
    'by_default'  => __('general_content.by_default_trans_key'),
    'edit'        => __('general_content.edit_trans_key'),
    'save'        => __('general_content.save_trans_key'),
    'saving'      => __('general_content.saving_trans_key'),
    'no_data'     => __('general_content.no_data_trans_key'),
  ];

  $contactsTrans = [
    'contacts'    => __('general_content.contacts_trans_key'),
    'new_contact' => __('general_content.new_contact_trans_key'),
    'sort'        => __('general_content.sort_trans_key'),
    'civility'    => __('general_content.civility_trans_key'),
    'first_name'  => __('general_content.first_name_trans_key'),
    'name'        => __('general_content.name_trans_key'),
    'function'    => __('general_content.function_trans_key'),
    'phone'       => __('general_content.phone_trans_key'),
    'mobile'      => __('general_content.mobile_phone_trans_key'),
    'email'       => __('general_content.email_trans_key'),
    'by_default'  => __('general_content.by_default_trans_key'),
    'edit'        => __('general_content.edit_trans_key'),
    'save'        => __('general_content.save_trans_key'),
    'saving'      => __('general_content.saving_trans_key'),
    'no_data'     => __('general_content.no_data_trans_key'),
  ];
@endphp

@php
  $dashboardKpi = [
    'orderAverage'           => $data['orderAverage'],
    'remainingInvoiceOrder'  => $remainingInvoiceOrder,
    'pendingOrdersCount'     => $pendingOrdersCount,
    'customerProcessingCost' => $customerProcessingCost,
    'serviceRate'            => $serviceRate,
    'paidInvoices'           => $paidInvoices,
    'unpaidInvoices'         => $unpaidInvoices,
    'since'                  => $Companie->GetPrettyCreatedAttribute(),
  ];

  $dashboardCharts = [
    'quotesDataRate'       => $data['quotesDataRate'],
    'orderMonthlyRecap'    => $data['orderMonthlyRecap'],
    'fiscalYearStartMonth' => app('Factory')->fiscal_year_start_month ?? 1,
  ];

  $dashboardTrans = [
    'order_average'            => __('general_content.order_average_note_trans_key'),
    'remaining_invoice'        => __('general_content.remaining_invoice_month_trans_key'),
    'pending_orders'           => __('general_content.pending_orders_trans_key'),
    'customer_processing_cost' => __('general_content.customer_processing_cost_trans_key'),
    'service_rate'             => __('general_content.service_rate_trans_key'),
    'bills_paid'               => __('general_content.bills_paid_trans_key'),
    'bills_unpaid'             => __('general_content.bills_unpaid_trans_key'),
    'quote_transformation'     => __('general_content.quote_transformation_trans_key'),
    'monthly_recap'            => __('general_content.monthly_recap_report_trans_key'),
    'sales_period'             => __('general_content.sales_period_trans_key', ['year' => now()->year]),
    'total'                    => __('general_content.total_trans_key'),
    'open'                     => __('general_content.open_trans_key'),
    'send'                     => __('general_content.send_trans_key'),
    'win'                      => __('general_content.win_trans_key'),
    'lost'                     => __('general_content.lost_trans_key'),
    'closed'                   => __('general_content.closed_trans_key'),
    'obsolete'                 => __('general_content.obsolete_trans_key'),
    'currency'                 => app('Factory')->curency ?? 'EUR',
    'locale'                   => str_replace('_', '-', config('app.locale')),
    'jan' => 'Jan', 'feb' => 'Fév', 'mar' => 'Mar',
    'apr' => 'Avr', 'may' => 'Mai', 'jun' => 'Jun',
    'jul' => 'Jul', 'aug' => 'Aoû', 'sep' => 'Sep',
    'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Déc',
  ];
@endphp

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Company" data-toggle="tab">{{ __('general_content.detail_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Adresses" data-toggle="tab">{{ __('general_content.adress_trans_key') }} ({{ $Companie->getAddressesCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Contact" data-toggle="tab">{{ __('general_content.contacts_trans_key') }} ({{ $Companie->getContactsCountAttribute() }})</a></li>
      <li class="nav-item">
        <a class="nav-link" href="#timeline" data-toggle="tab">
          <i class="fas fa-stream mr-1"></i>
          Historique
          @php
            $timelineCount = $Companie->getLeadsCountAttribute()
                           + $Companie->getQuotesCountAttribute()
                           + $Companie->getOrdersCountAttribute()
                           + $Companie->getDeliverysCountAttribute()
                           + $Companie->getInvoicesCountAttribute()
                           + $Companie->getPurchasesCountAttribute();
          @endphp
          ({{ $timelineCount }})
        </a>
      </li>
      
      @if($Companie->statu_supplier == 2 )
      <li class="nav-item"><a class="nav-link" href="#evaluation" data-toggle="tab">{{ __('general_content.supplier_evaluations_trans_key') }}</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Documents" data-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Companie->files) }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Dashboard">
        <div
          id="company-dashboard-app"
          data-kpi='@json($dashboardKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          data-charts='@json($dashboardCharts, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          data-trans='@json($dashboardTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
        </div>
      </div>
      <div class="tab-pane" id="Company">
        <div class="row">
          <div class="col-md-9">
            <div
              id="company-form-app"
              data-company='@json($companyFormData, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
              data-users='@json($companyFormUsers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
              data-endpoint='{{ route('companies.json.update', $Companie->id) }}'
              data-pdp-lookup-url='{{ route('companies.pdp.lookup', $Companie->id) }}'
              data-trans='@json($companyFormTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
            </div>
          </div>

          <div class="col-md-3">
            @if($Companie->barcode_value)
            <x-adminlte-card title="{{ __('BARECODE') }}" theme="orange" maximizable>
              @php echo DNS2D::getBarcodeHTML($Companie->barcode_value, 'QRCODE'); @endphp
            </x-adminlte-card>
            @endif

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="purple" maximizable>
              <p>
                <a class="btn btn-success btn-sm" href="{{ route('companies.store.quote', ['id' => $Companie->id ]) }}">
                  <i class="fas fa-folder"></i>
                  {{ __('general_content.new_quote_trans_key') }}
                </a>
              </p>
            </x-adminlte-card>

            @if($Companie->latitude && $Companie->longitude)
            <x-adminlte-card title="Map" theme="success" maximizable>
              <div id="map" style="width: 100%; height: 400px;"></div>
            </x-adminlte-card>
            @endif
          </div>
        </div>
      </div>  
      <div class="tab-pane " id="Adresses">
        <div
          id="company-addresses-app"
          data-addresses='@json($Companie->Addresses->toArray())'
          data-store-url="{{ route('addresses.json.store') }}"
          data-update-base-url="{{ route('addresses.json.update', ['address' => '__ID__']) }}"
          data-companie-id="{{ $Companie->id }}"
          data-trans='@json($addressesTrans)'
          data-document-defaults='@json($documentDefaults)'
          data-sync-address-base-url="{{ route('companies.document-defaults.sync-address', ['company' => $Companie->id, 'address' => '__ID__']) }}"
        ></div>
      </div>
      
      <div class="tab-pane " id="Contact">
        <div
          id="company-contacts-app"
          data-contacts='@json($Companie->Contacts->toArray())'
          data-store-url="{{ route('contacts.json.store') }}"
          data-update-base-url="{{ route('contacts.json.update', ['contact' => '__ID__']) }}"
          data-companie-id="{{ $Companie->id }}"
          data-trans='@json($contactsTrans)'
          data-document-defaults='@json($documentDefaults)'
          data-sync-contact-base-url="{{ route('companies.document-defaults.sync-contact', ['company' => $Companie->id, 'contact' => '__ID__']) }}"
        ></div>
      </div>
      
      <div class="tab-pane" id="timeline">
        <div
          id="company-timeline-app"
          data-endpoint="{{ route('companies.json.timeline', $Companie->id) }}">
        </div>
      </div>
      <div class="tab-pane" id="evaluation">
        @php
          $newEvaluationLabel       = __('general_content.new_supplier_evaluation_trans_key');
          $latestEvaluationLabel    = __('general_content.latest_supplier_evaluation_trans_key');
          $evaluationHistoryLabel   = __('general_content.supplier_evaluation_history_trans_key');
          $requalificationAlertLabel = __('general_content.supplier_requalification_alert_trans_key');
          $requalificationSoonLabel  = __('general_content.supplier_requalification_soon_trans_key');

          $evaluationScoresLabel = [
              'quality'   => __('general_content.evaluation_quality_score_trans_key'),
              'logistics' => __('general_content.evaluation_logistics_score_trans_key'),
              'service'   => __('general_content.evaluation_service_score_trans_key'),
          ];

          $evaluationStatusLabels = [
              'pending'      => __('general_content.evaluation_status_pending_trans_key'),
              'approved'     => __('general_content.evaluation_status_approved_trans_key'),
              'under_review' => __('general_content.evaluation_status_under_review_trans_key'),
              'rejected'     => __('general_content.evaluation_status_rejected_trans_key'),
          ];
        @endphp

        @if($needsRequalification)
          <x-adminlte-alert theme="danger" title="{{ $requalificationAlertLabel }}">
            {{ __('general_content.supplier_requalification_overdue_trans_key', ['days' => abs($daysUntilNextReview ?? 0)]) }}
          </x-adminlte-alert>
        @elseif($nextReviewSoon && !is_null($daysUntilNextReview))
          <x-adminlte-alert theme="warning" title="{{ $requalificationSoonLabel }}">
            {{ __('general_content.supplier_next_review_days_trans_key', ['days' => $daysUntilNextReview]) }}
          </x-adminlte-alert>
        @endif

        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ $newEvaluationLabel }}" theme="primary" maximizable>
              @if($purchasesForEvaluation->isEmpty())
                <p class="text-muted">{{ __('general_content.no_purchase_order_to_evaluate_trans_key') }}</p>
              @else
                <form method="POST" action="{{ route('companies.ratings.store') }}">
                  @csrf
                  <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
                  <div class="form-group">
                    <label for="purchases_id">{{ __('general_content.purchase_order_trans_key') }}</label>
                    <select name="purchases_id" id="purchases_id" class="form-control">
                      @foreach($purchasesForEvaluation as $purchase)
                        <option value="{{ $purchase->id }}">
                          {{ $purchase->code }} - {{ $purchase->label }} ({{ $purchase->GetPrettyCreatedAttribute() }})
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="rating">{{ __('general_content.evaluation_rating_trans_key') }}</label>
                    <select name="rating" id="rating" class="form-control">
                      @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                      @endfor
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="evaluation_status">{{ __('general_content.evaluation_status_trans_key') }}</label>
                    <select name="evaluation_status" id="evaluation_status" class="form-control">
                      @foreach($evaluationStatusLabels as $statusOption => $statusLabel)
                        <option value="{{ $statusOption }}">{{ $statusLabel }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="approved_at">{{ __('general_content.evaluation_approved_at_trans_key') }}</label>
                      <input type="datetime-local" name="approved_at" id="approved_at" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="next_review_at">{{ __('general_content.evaluation_next_review_at_trans_key') }}</label>
                      <input type="date" name="next_review_at" id="next_review_at" class="form-control">
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="evaluation_score_quality">{{ $evaluationScoresLabel['quality'] }}</label>
                      <input type="number" class="form-control" name="evaluation_score_quality" id="evaluation_score_quality" min="0" max="100" step="1">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="evaluation_score_logistics">{{ $evaluationScoresLabel['logistics'] }}</label>
                      <input type="number" class="form-control" name="evaluation_score_logistics" id="evaluation_score_logistics" min="0" max="100" step="1">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="evaluation_score_service">{{ $evaluationScoresLabel['service'] }}</label>
                      <input type="number" class="form-control" name="evaluation_score_service" id="evaluation_score_service" min="0" max="100" step="1">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
                    <textarea name="comment" id="comment" rows="3" class="form-control"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="action_plan">{{ __('general_content.evaluation_action_plan_trans_key') }}</label>
                    <textarea name="action_plan" id="action_plan" rows="3" class="form-control"></textarea>
                  </div>
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save" />
                </form>
              @endif
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ $latestEvaluationLabel }}" theme="secondary" maximizable>
              @if($latestEvaluation)
                <ul class="list-unstyled mb-0">
                  <li><strong>{{ __('general_content.evaluation_last_updated_trans_key') }}:</strong> {{ optional($latestEvaluation->created_at)->format('d/m/Y H:i') }}</li>
                  <li><strong>{{ __('general_content.evaluation_status_trans_key') }}:</strong> {{ $evaluationStatusLabels[$latestEvaluation->evaluation_status] ?? ucfirst(str_replace('_', ' ', $latestEvaluation->evaluation_status ?? '')) }}</li>
                  <li><strong>{{ __('general_content.evaluation_rating_trans_key') }}:</strong>
                    @for ($i = 1; $i <= 5; $i++)
                      @if ($i <= ($latestEvaluation->rating ?? 0))
                        <span class="badge badge-warning">&#9733;</span>
                      @else
                        <span class="badge badge-info">&#9734;</span>
                      @endif
                    @endfor
                  </li>
                  <li><strong>{{ __('general_content.evaluation_composite_score_trans_key') }}:</strong> {{ $latestEvaluation->composite_score ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ $evaluationScoresLabel['quality'] }}:</strong> {{ $latestEvaluation->evaluation_score_quality ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ $evaluationScoresLabel['logistics'] }}:</strong> {{ $latestEvaluation->evaluation_score_logistics ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ $evaluationScoresLabel['service'] }}:</strong> {{ $latestEvaluation->evaluation_score_service ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ __('general_content.evaluation_next_review_at_trans_key') }}:</strong> {{ optional($latestEvaluation->next_review_at)->format('d/m/Y') ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ __('general_content.evaluation_approved_at_trans_key') }}:</strong> {{ optional($latestEvaluation->approved_at)->format('d/m/Y H:i') ?? __('general_content.not_available_trans_key') }}</li>
                  <li><strong>{{ __('general_content.evaluation_action_plan_trans_key') }}:</strong> {{ $latestEvaluation->action_plan ?? __('general_content.not_available_trans_key') }}</li>
                </ul>
              @else
                <p class="text-muted">{{ __('general_content.no_supplier_evaluation_trans_key') }}</p>
              @endif
              <hr>
              <ul class="list-unstyled mb-0">
                <li><strong>{{ __('general_content.evaluation_average_composite_score_trans_key') }}:</strong> {{ $averageCompositeScore ?? __('general_content.not_available_trans_key') }}</li>
                <li><strong>{{ __('general_content.evaluation_average_rating_trans_key') }}:</strong> {{ $Companie->averageRating() ? number_format($Companie->averageRating(), 2) : __('general_content.not_available_trans_key') }}</li>
              </ul>
            </x-adminlte-card>
          </div>
        </div>

        <x-adminlte-card title="{{ $evaluationHistoryLabel }}" theme="info" maximizable>
          @if($evaluationHistory->isEmpty())
            <p class="text-muted">{{ __('general_content.no_supplier_evaluation_history_trans_key') }}</p>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.date_trans_key') }}</th>
                    <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                    <th>{{ __('general_content.evaluation_rating_trans_key') }}</th>
                    <th>{{ __('general_content.evaluation_composite_score_trans_key') }}</th>
                    <th>{{ __('general_content.evaluation_status_trans_key') }}</th>
                    <th>{{ $evaluationScoresLabel['quality'] }}</th>
                    <th>{{ $evaluationScoresLabel['logistics'] }}</th>
                    <th>{{ $evaluationScoresLabel['service'] }}</th>
                    <th>{{ __('general_content.evaluation_next_review_at_trans_key') }}</th>
                    <th>{{ __('general_content.evaluation_approved_at_trans_key') }}</th>
                    <th>{{ __('general_content.evaluation_action_plan_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($evaluationHistory as $evaluation)
                    <tr>
                      <td>{{ optional($evaluation->created_at)->format('d/m/Y') }}</td>
                      <td>{{ optional($evaluation->purchaseOrder)->code }}</td>
                      <td>{{ $evaluation->rating }}</td>
                      <td>{{ $evaluation->composite_score ?? __('general_content.not_available_trans_key') }}</td>
                      <td>{{ $evaluationStatusLabels[$evaluation->evaluation_status] ?? ucfirst(str_replace('_', ' ', $evaluation->evaluation_status ?? '')) }}</td>
                      <td>{{ $evaluation->evaluation_score_quality ?? '-' }}</td>
                      <td>{{ $evaluation->evaluation_score_logistics ?? '-' }}</td>
                      <td>{{ $evaluation->evaluation_score_service ?? '-' }}</td>
                      <td>{{ optional($evaluation->next_review_at)->format('d/m/Y') ?? '-' }}</td>
                      <td>{{ optional($evaluation->approved_at)->format('d/m/Y H:i') ?? '-' }}</td>
                      <td>{{ $evaluation->action_plan ? \Illuminate\Support\Str::limit($evaluation->action_plan, 60) : '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </x-adminlte-card>
      </div>
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'company',
          'fileableId'   => $Companie->id,
        ])
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
  // Vérifie que les coordonnées sont définies dans la variable PHP
  var latitude = {{ $Companie->latitude ?? '48.8588443' }};  // Remplace par des valeurs par défaut si non disponible
  var longitude = {{ $Companie->longitude ?? '2.2943506' }};  // Exemple: coordonnées de la Tour Eiffel

  // Initialise la carte
  var map = L.map('map').setView([latitude, longitude], 13);

  // Charger les tuiles de la carte (OpenStreetMap)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
  }).addTo(map);

  // Ajouter un marqueur à la position des coordonnées
  L.marker([latitude, longitude]).addTo(map)
      .bindPopup('{{ $Companie->label }}')
      .openPopup();
</script>
@stop
