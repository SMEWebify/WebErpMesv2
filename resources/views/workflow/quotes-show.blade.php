@extends('adminlte::page')

@section('title', __('general_content.quote_trans_key')  . ' - ' . $Quote->code)

@php
$arrowSteps = json_encode([
    ['value' => 1, 'label' => __('general_content.open_trans_key')],
    ['value' => 2, 'label' => __('general_content.send_trans_key')],
    ['value' => 3, 'label' => __('general_content.win_trans_key')],
    ['value' => 4, 'label' => __('general_content.lost_trans_key')],
    ['value' => 5, 'label' => __('general_content.closed_trans_key')],
    ['value' => 6, 'label' => __('general_content.obsolete_trans_key')],
]);
$lossDialog = app(\App\Services\Workflow\LossReasonRecorder::class)->dialog($Quote, 'quote')
    + ['endpoint' => route('quotes.json.loss-reason', $Quote->id)];
@endphp

@section('content_header')
    <script rel="stylesheet" src="{{ asset('js/switchtabNav.js') }}"></script>
    @if($Quote->is_template)
    {{-- Une trame n'a pas de cycle de vie : ni statut, ni navigation entre devis. --}}
    <x-document-header :h1="__('general_content.quote_template_trans_key') . ' : ' . $Quote->label"
                       list="{{ route('quotes', ['tab' => 'templates']) }}"/>
    @else
    <x-document-header h1="{{ __('general_content.quote_trans_key') }} : {{  $Quote->code }}"
                       previous="{{ $previousUrl }}" list="{{ route('quotes') }}" next="{{ $nextUrl }}"
                       :steps="$arrowSteps" statu="{{ $Quote->statu }}"
                       endpoint="{{ route('quotes.json.statu', $Quote->id) }}"
                       redirect="{{ route('quotes.show', $Quote->id) }}"
                       :loss="$lossDialog"/>
    @endif
@stop

@section('right-sidebar')

@section('content')


@if($Quote->is_template)
<div class="callout callout-info d-flex flex-wrap align-items-center justify-content-between" style="gap:.5rem">
  <div>
    <i class="fas fa-layer-group me-2 text-info"></i>{{ __('general_content.quote_template_banner_trans_key') }}
  </div>
  <div class="d-flex flex-wrap" style="gap:.5rem">
    <a href="{{ route('quotes', ['tab' => 'list', 'template' => $Quote->id]) }}" class="btn btn-sm btn-success">
      <i class="fas fa-plus me-1"></i>{{ __('general_content.new_quote_from_template_trans_key') }}
    </a>
    <form method="POST" action="{{ route('quotes.template.destroy', ['id' => $Quote->id]) }}"
          onsubmit="return confirm(@js(__('general_content.delete_template_confirm_trans_key')))">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-outline-danger">
        <i class="fas fa-trash me-1"></i>{{ __('general_content.delete_template_trans_key') }}
      </button>
    </form>
  </div>
</div>
@endif

<div class="card mb-3">
  <div class="card-header p-2">
    <ul class="nav nav-pills"  id="DocumentTabs">
      <li class="nav-item"><a class="nav-link " href="#Quote" data-bs-toggle="tab" title="{{ __('general_content.quote_info_trans_key') }}" aria-label="{{ __('general_content.quote_info_trans_key') }}"><i class="fas fa-info-circle"></i></a></li>
      @php $quoteReviewBadge = ['approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'][$Quote->review_decision] ?? null; @endphp
      <li class="nav-item">
        <a class="nav-link" href="#Review" data-bs-toggle="tab">
          <i class="fas fa-clipboard-check"></i> {{ __('contract_review.quote_tab') }}
          @if($quoteReviewBadge)
            <span class="badge {{ $quoteReviewBadge }} ms-1">{{ __('general_content.' . $Quote->review_decision . '_trans_key') }}</span>
          @endif
          @if($Quote->change_requested_by && ! $Quote->change_approved_at)
            <span class="badge text-bg-warning ms-1" title="{{ __('contract_review.change.pending') }}"><i class="fas fa-exchange-alt"></i></span>
          @endif
        </a>
      </li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-bs-toggle="tab">{{ __('general_content.quote_line_trans_key') }} ({{ $Quote->QuoteLines->filter->isArticle()->count() }})</a></li>
      @if($Factory->enable_construction_site)
      <li class="nav-item"><a class="nav-link" href="#Construction" data-bs-toggle="tab">{{ __('general_content.construction_site_trans_key') }} <span class="badge text-bg-danger right">{{ __('general_content.beta_trans_key') }}</span></a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Charts" data-bs-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Views" data-bs-toggle="tab">{{ __('general_content.guest_page_trans_key') }} ( {{  $Quote->visitsCount() }} )</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-bs-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-bs-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Documents" data-bs-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Quote->files) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-bs-toggle="tab">{{ __('general_content.logs_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane " id="Quote">
        <x-relational-breadcrumb :entity="$Quote" />
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            @if($OpenSiblings->isNotEmpty())
            {{-- Devis gagné : les autres indices / variantes encore ouverts de la famille peuvent être clos. --}}
            <div class="callout callout-warning d-flex flex-wrap align-items-center justify-content-between" style="gap:.5rem">
              <div>
                <i class="fas fa-code-branch me-2 text-warning"></i>
                {{ __('quote_revisions.close_family_prompt', ['codes' => $OpenSiblings->pluck('code')->implode(', ')]) }}
              </div>
              <form method="POST" action="{{ route('quotes.family.close', ['id' => $Quote->id]) }}"
                    onsubmit="return confirm(@js(__('quote_revisions.close_family_confirm')))">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                  <i class="fas fa-lock me-1"></i>{{ __('quote_revisions.close_family') }}
                </button>
              </form>
            </div>
            @endif
            <form method="POST" action="{{ route('quotes.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="teal" theme-mode="outline" maximizable>
                @csrf 
                <div class="row">
                  <div class="mb-3 col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Quote->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Quote->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.label_trans_key'), 'Value' =>  $Quote->label])
                    @include('include.form.form-select-affair-category',['affairCategoryId' => $Quote->affair_category_id])
                  </div>
                </div>
                  @if($Quote->companie['active'] == 1)
                  <div class="row">
                    <label for="companies_id" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="mb-3 col-md-6">
                        @if($Quote->opportunities_id or $Quote->statu != 1)
                        {{ __('general_content.companie_trans_key') }} :  <x-CompanieButton id="{{ $Quote->companie['id'] }}" :label="$Quote->companie['label']"  />
                          <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                          @else
                              @include('include.form.form-select-companie',['companiesId' =>  $Quote->companies_id])
                        @endif
                    </div>
                    <div class="mb-3 col-md-6">
                      @include('include.form.form-input-customerInfo',['customerReference' =>  $Quote->customer_reference])
                    </div>
                  </div>
                  <div class="row">
                    <div class="mb-3 col-md-6">
                      @include('include.form.form-select-adress',['adressId' =>   $Quote->companies_addresses_id])
                    </div>
                    <div class="mb-3 col-md-6">
                      @include('include.form.form-select-contact',['contactId' =>   $Quote->companies_contacts_id])
                    </div>
                  </div>
                @else
                <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                <input type="hidden" name="customer_reference" value="{{ $Quote->customer_reference }}">
                <input type="hidden" name="companies_addresses_id" value="{{ $Quote->companies_addresses_id }}">
                <input type="hidden" name="companies_contacts_id" value="{{ $Quote->companies_contacts_id }}">
                @php
                  $customerButton = view('components.companie-button', [
                    'id' => $Quote->companie['id'],
                    'label' => $Quote->companie['label'],
                  ])->render();
                @endphp
                <x-adminlte-alert theme="info" title="{{ __('general_content.info_trans_key') }}">
                  {!! __('general_content.customer_disabled_warning_trans_key', ['customer' => $customerButton]) !!}
                </x-adminlte-alert>
                @endif
                  <div class="row">
                    <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="mb-3 col-md-6">
                      @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Quote->accounting_payment_conditions_id])
                    </div>
                    <div class="mb-3 col-md-6">
                        @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Quote->accounting_payment_methods_id])
                    </div>
                  </div>
                  <div class="row">
                    <div class="mb-3 col-md-6">
                        @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Quote->accounting_deliveries_id])
                    </div>
                    <div class="mb-3 col-md-6">
                      <label for="validity_date">{{ __('general_content.validity_date_trans_key') }}</label>
                      <div class="input-group">
                        <div class="input-group-text text-bg-secondary bg-gradient">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                      </div>
                    </div>
                  </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Quote->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" theme-mode="outline" collapsible maximizable>
              @include('include.sub-total-price')
            </x-adminlte-card>

            @include('include.quote-margin-card')

            @can('scheduling-menu')
            <x-adminlte-card title="{{ __('general_content.delivery_simulation_trans_key') }}" theme="info" theme-mode="outline" collapsible maximizable>
              <form action="{{ route('quotes.delivery.simulation', ['id' => $Quote->id]) }}" method="POST">
                @csrf
                <div class="mb-3">
                  <label for="requested_delivery_date">{{ __('general_content.requested_delivery_date_trans_key') }}</label>
                  <input type="date" class="form-control" id="requested_delivery_date" name="requested_delivery_date" value="{{ old('requested_delivery_date') }}" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-play-circle"></i> {{ __('general_content.run_simulation_trans_key') }}
                </button>
              </form>

              @if(session('delivery_simulation'))
              @php
                $simulation = session('delivery_simulation');
              @endphp
                <div class="mt-3">
                  <h6 class="text-muted mb-2">{{ __('general_content.simulation_results_trans_key') }}</h6>
                  @if($simulation['is_possible'])
                    <span class="badge text-bg-success">{{ __('general_content.simulation_possible_trans_key') }}</span>
                  @else
                    <span class="badge text-bg-danger">{{ __('general_content.simulation_not_possible_trans_key') }}</span>
                  @endif

                  <p class="mt-2 mb-1">
                    <strong>{{ __('general_content.requested_delivery_date_trans_key') }}:</strong>
                    {{ $simulation['requested_date'] }}
                  </p>

                  @if(!empty($simulation['internal_delay_days']))
                    <p class="mb-1">
                      <strong>{{ __('general_content.simulation_internal_delay_trans_key') }}:</strong>
                      {{ $simulation['internal_delay_days'] }} {{ __('general_content.day_trans_key') }}
                    </p>
                    <p class="mb-1">
                      <strong>{{ __('general_content.simulation_internal_deadline_trans_key') }}:</strong>
                      {{ $simulation['internal_deadline'] }}
                    </p>
                  @endif

                  @if(!empty($simulation['earliest_date']))
                    <p class="mb-2">
                      <strong>{{ __('general_content.earliest_possible_date_trans_key') }}:</strong>
                      {{ $simulation['earliest_date'] }}
                    </p>
                  @endif

                  <p class="text-muted mb-2">
                    {{ __('general_content.simulation_capacity_per_day_trans_key') }} ({{ $simulation['capacity_per_day'] }} h)
                  </p>

                  <table class="table table-sm table-striped mb-0">
                    <thead>
                      <tr>
                        <th>{{ __('general_content.simulation_service_trans_key') }}</th>
                        <th class="text-end">{{ __('general_content.simulation_required_hours_trans_key') }}</th>
                        @if(!$simulation['is_possible'])
                          <th class="text-end">{{ __('general_content.simulation_remaining_hours_trans_key') }}</th>
                        @endif
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($simulation['required_by_service'] as $serviceId => $requiredHours)
                        <tr>
                          <td>{{ $simulation['service_labels'][$serviceId] ?? $serviceId }}</td>
                          <td class="text-end">{{ $requiredHours }}</td>
                          @if(!$simulation['is_possible'])
                            <td class="text-end">{{ $simulation['missing_by_service'][$serviceId] ?? 0 }}</td>
                          @endif
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </x-adminlte-card>
            @endcan


            @if($QuoteFamily->count() > 1)
            @php
              $quoteStatuLabels = [
                1 => ['general_content.open_trans_key', 'text-bg-info'],
                2 => ['general_content.send_trans_key', 'text-bg-primary'],
                3 => ['general_content.win_trans_key', 'text-bg-success'],
                4 => ['general_content.lost_trans_key', 'text-bg-danger'],
                5 => ['general_content.closed_trans_key', 'text-bg-secondary'],
                6 => ['general_content.obsolete_trans_key', 'text-bg-secondary'],
              ];
            @endphp
            <x-adminlte-card title="{{ __('quote_revisions.history') }}" theme="secondary" theme-mode="outline" icon="fas fa-code-branch" collapsible>
              <ul class="list-unstyled mb-0">
                @foreach($QuoteFamily as $member)
                <li class="d-flex justify-content-between align-items-start py-1 @if(!$loop->last) border-bottom @endif">
                  <div>
                    @if($member->id === $Quote->id)
                      <strong>{{ $member->code }}</strong>
                    @else
                      <a href="{{ route('quotes.show', ['id' => $member->id]) }}">{{ $member->code }}</a>
                    @endif
                    <div class="small text-body-secondary">
                      @if(!$member->parent_quote_id)
                        {{ __('quote_revisions.referent') }}
                      @else
                        @if($member->revision){{ __('quote_revisions.revision_short', ['revision' => $member->revision]) }}@endif
                        @if($member->revision && $member->variant_label) · @endif
                        @if($member->variant_label){{ __('quote_revisions.variant_short', ['label' => $member->variant_label]) }}@endif
                      @endif
                      · {{ $member->created_at?->format('d/m/Y') }}
                    </div>
                  </div>
                  @php
                    [$statuKey, $statuClass] = $quoteStatuLabels[(int) $member->statu] ?? ['general_content.undefined_trans_key', 'text-bg-secondary'];
                  @endphp
                  <span class="badge {{ $statuClass }}">{{ __($statuKey) }}</span>
                </li>
                @endforeach
              </ul>
            </x-adminlte-card>
            @endif

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="secondary" theme-mode="outline" collapsible maximizable>
              <table class="table table-hover">
                <tr>
                  <td style="width:50%">{{ __('general_content.quote_trans_key') }}</td>
                  <td><x-pdf-print-menu :route="route('pdf.quote', ['Document' => $Quote->id])" /></td>
                </tr>
                @unless($Quote->is_template)
                {{-- Copies et versions regroupées : dupliquer, nouvel indice, variante, trame. --}}
                <tr>
                  <td style="width:50%">{{ __('quote_revisions.copies') }}</td>
                  <td>
                    <div class="dropdown">
                      <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-copy me-1"></i>{{ __('quote_revisions.copies_button') }}
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end" style="width: 19rem;">
                        <li>
                          <form method="POST" action="{{ route('quotes.duplicate', ['id' => $Quote->id]) }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-wrap">
                              <i class="fas fa-copy fa-fw me-1"></i>{{ __('general_content.duplicate_quote_trans_key') }}
                              <div class="small text-body-secondary ms-4">{{ __('general_content.duplicate_quote_help_trans_key') }}</div>
                            </button>
                          </form>
                        </li>
                        <li>
                          @if($NewerRevision)
                            <div class="dropdown-item-text text-wrap text-body-secondary">
                              <i class="fas fa-code-branch fa-fw me-1"></i>{{ __('quote_revisions.new_revision') }}
                              <div class="small ms-4">
                                {{ __('quote_revisions.superseded_by') }}
                                <a href="{{ route('quotes.show', ['id' => $NewerRevision->id]) }}">{{ $NewerRevision->code }}</a>
                              </div>
                            </div>
                          @else
                          <form method="POST" action="{{ route('quotes.revision', ['id' => $Quote->id]) }}"
                                onsubmit="return confirm(@js(__('quote_revisions.new_revision_confirm')))">
                            @csrf
                            <button type="submit" class="dropdown-item text-wrap">
                              <i class="fas fa-code-branch fa-fw me-1"></i>{{ __('quote_revisions.new_revision') }}
                              <div class="small text-body-secondary ms-4">{{ __('quote_revisions.new_revision_help') }}</div>
                            </button>
                          </form>
                          @endif
                        </li>
                        <li>
                          <button type="button" class="dropdown-item text-wrap" data-bs-toggle="modal" data-bs-target="#quoteVariantModal">
                            <i class="fas fa-clone fa-fw me-1"></i>{{ __('quote_revisions.new_variant') }}…
                            <div class="small text-body-secondary ms-4">{{ __('quote_revisions.new_variant_help') }}</div>
                          </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <button type="button" class="dropdown-item text-wrap" data-bs-toggle="modal" data-bs-target="#quoteTemplateModal">
                            <i class="fas fa-layer-group fa-fw me-1"></i>{{ __('general_content.save_as_template_trans_key') }}…
                          </button>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
                @endunless
                @if(!$Quote->is_template && config('mail.default') && config('mail.from.address'))
                <tr>
                  <td style="width:50%">{{ __('general_content.email_trans_key') }} </td>
                  <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'quote', 'id' => $Quote->id]) }}" /></td>
                </tr>
                @endif
                @if($Quote->uuid && !$Quote->is_template)
                <tr>
                  <td style="width:50%">{{ __('general_content.public_link_trans_key') }}</td>
                  <td>
                    <button class="btn btn-info btn-sm" onclick="copyToClipboard('{{ Request::root() }}/guest/quote/{{ $Quote->uuid }}')">
                      <i class="fas fa-copy"></i> {{ __('general_content.copy_trans_key') }} 
                    </button>
                  </td>
                </tr>
                @endif
                
                @forelse($Quote->Orders as $Order)
                <tr>
                    <td style="width:50%"><x-OrderButton id="{{ $Order->id }}" code="{{ $Order->code }}"  /></td>
                    <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                </tr>
                @empty
                <!--<tr>
                  <td colspan="2">
                      {{ __('general_content.no_data_trans_key') }}
                  </td>
                </tr>-->
                @endforelse
              </table>
            </x-adminlte-card>
            @unless($Quote->is_template)
            <div class="modal fade" id="quoteVariantModal" tabindex="-1" aria-labelledby="quoteVariantModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" action="{{ route('quotes.variant', ['id' => $Quote->id]) }}" class="modal-content">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title" id="quoteVariantModalLabel"><i class="fas fa-clone me-1"></i>{{ __('quote_revisions.new_variant') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('general_content.close_trans_key') }}"></button>
                  </div>
                  <div class="modal-body">
                    <p class="small text-body-secondary">{{ __('quote_revisions.new_variant_help') }}</p>
                    <label for="variant_label" class="form-label">{{ __('quote_revisions.variant_label') }}</label>
                    <input type="text" name="variant_label" id="variant_label" class="form-control" required maxlength="100">
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general_content.cancel_trans_key') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-clone me-1"></i>{{ __('quote_revisions.new_variant') }}</button>
                  </div>
                </form>
              </div>
            </div>
            <div class="modal fade" id="quoteTemplateModal" tabindex="-1" aria-labelledby="quoteTemplateModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" action="{{ route('quotes.save-template', ['id' => $Quote->id]) }}" class="modal-content">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title" id="quoteTemplateModalLabel"><i class="fas fa-layer-group me-1"></i>{{ __('general_content.save_as_template_trans_key') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('general_content.close_trans_key') }}"></button>
                  </div>
                  <div class="modal-body">
                    <label for="template_label" class="form-label">{{ __('general_content.template_label_trans_key') }}</label>
                    <input type="text" name="template_label" id="template_label" class="form-control" required maxlength="255" value="{{ $Quote->label }}">
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general_content.cancel_trans_key') }}</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-layer-group me-1"></i>{{ __('general_content.save_as_template_trans_key') }}</button>
                  </div>
                </form>
              </div>
            </div>
            @endunless
            @include('include.email-list', ['mailsList'=> $Quote->emailLogs,])
          </div>
        </div>
      </div>   
      {{-- Revue et demande de changement du devis : formulaire à part, mêmes champs qu'avant. --}}
      <div class="tab-pane" id="Review">
        @include('include.alert-result')
        <form method="POST" action="{{ route('quotes.review.update', ['id' => $Quote->id]) }}">
          @csrf
          <x-adminlte-card title="{{ __('general_content.review_change_tracking_trans_key') }}" theme="info" theme-mode="outline" icon="fas fa-clipboard-check">
                    <div class="row">
                      <div class="mb-3 col-md-6">
                        <label for="reviewed_by">{{ __('general_content.reviewed_by_trans_key') }}</label>
                        <select class="form-select" name="reviewed_by" id="reviewed_by">
                          <option value="">{{ __('general_content.select_user_trans_key') }}</option>
                          @foreach($Reviewers as $user)
                            <option value="{{ $user->id }}" @selected(old('reviewed_by', $Quote->reviewed_by) == $user->id)>{{ $user->name }}</option>
                          @endforeach
                        </select>
                        @error('reviewed_by')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                      <div class="mb-3 col-md-6">
                        <label for="reviewed_at">{{ __('general_content.review_date_trans_key') }}</label>
                        <input type="datetime-local" class="form-control" name="reviewed_at" id="reviewed_at" value="{{ old('reviewed_at', optional($Quote->reviewed_at)->format('Y-m-d\\TH:i')) }}">
                        @error('reviewed_at')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                    </div>
                    <div class="row">
                      <div class="mb-3 col-md-6">
                        <label for="review_decision">{{ __('general_content.decision_trans_key') }}</label>
                        <select class="form-select" name="review_decision" id="review_decision">
                          <option value="">{{ __('general_content.undefined_trans_key') }}</option>
                          <option value="pending" @selected(old('review_decision', $Quote->review_decision) === 'pending')>{{ __('general_content.pending_trans_key') }}</option>
                          <option value="approved" @selected(old('review_decision', $Quote->review_decision) === 'approved')>{{ __('general_content.approved_trans_key') }}</option>
                          <option value="rejected" @selected(old('review_decision', $Quote->review_decision) === 'rejected')>{{ __('general_content.rejected_trans_key') }}</option>
                        </select>
                        @error('review_decision')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                      <div class="mb-3 col-md-6">
                        <label for="change_requested_by">{{ __('general_content.change_requested_by_trans_key') }}</label>
                        <select class="form-select" name="change_requested_by" id="change_requested_by">
                          <option value="">{{ __('general_content.select_user_trans_key') }}</option>
                          @foreach($Reviewers as $user)
                            <option value="{{ $user->id }}" @selected(old('change_requested_by', $Quote->change_requested_by) == $user->id)>{{ $user->name }}</option>
                          @endforeach
                        </select>
                        @error('change_requested_by')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                    </div>
                    <div class="row">
                      <div class="mb-3 col-md-12">
                        <label for="change_reason">{{ __('general_content.change_reason_trans_key') }}</label>
                        <textarea class="form-control" name="change_reason" id="change_reason" rows="3">{{ old('change_reason', $Quote->change_reason) }}</textarea>
                        @error('change_reason')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                    </div>
                    <div class="row">
                      <div class="mb-3 col-md-6">
                        <label for="change_approved_at">{{ __('general_content.change_approved_at_trans_key') }}</label>
                        <input type="datetime-local" class="form-control" name="change_approved_at" id="change_approved_at" value="{{ old('change_approved_at', optional($Quote->change_approved_at)->format('Y-m-d\\TH:i')) }}">
                        @error('change_approved_at')
                          <span class="text-danger">{{ $message }}</span>
                        @enderror
                      </div>
                    </div>
            <x-slot name="footerSlot">
              <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </x-slot>
          </x-adminlte-card>
        </form>
        @include('workflow.partials.review-timeline')
      </div>
      <div class="tab-pane " id="Lines">
        @php
          $quoteLineEndpoints = [
            'lines'           => route('quotes.lines.json.for-quote',       ['quoteId' => $Quote->id]),
            'selectData'      => route('quotes.lines.json.select-data',     ['quoteId' => $Quote->id]),
            'priceList'       => route('quotes.lines.json.price-list',      ['quoteId' => $Quote->id, 'productId' => '__PRODUCT__']),
            'productSearch'   => route('products.json.search'),
            'store'           => route('quotes.lines.json.store',           ['quoteId' => $Quote->id]),
            'update'          => route('quotes.lines.json.update',          ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'destroy'         => route('quotes.lines.json.destroy',         ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'duplicate'       => route('quotes.lines.json.duplicate',       ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'breakdown'       => route('quotes.lines.json.breakdown',       ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'move'            => route('quotes.lines.json.move',            ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'reorder'         => route('quotes.lines.json.reorder',         ['quoteId' => $Quote->id]),
            'createProduct'   => route('quotes.lines.json.create-product',  ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'createProducts'  => route('quotes.lines.json.create-products', ['quoteId' => $Quote->id]),
            'tasks'           => route('quotes.lines.json.tasks',           ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'calculatedPrice' => route('quotes.lines.json.calculated-price',['quoteId' => $Quote->id, 'id' => '__ID__']),
            'presentation'    => route('quotes.lines.json.presentation',['quoteId' => $Quote->id, 'id' => '__ID__']),
            'storeOrder'      => route('quotes.lines.json.store-order',     ['quoteId' => $Quote->id]),
            'requiredOperations' => route('quotes.lines.json.required-operations', ['quoteId' => $Quote->id]),
            'priceIncrease'   => route('quotes.lines.json.price-increase',  ['quoteId' => $Quote->id]),
            'importSources'   => route('quotes.lines.json.import-sources',  ['quoteId' => $Quote->id]),
            'importFrom'      => route('quotes.lines.json.import-from',     ['quoteId' => $Quote->id]),
          ];
          if ($Quote->is_template) {
            // Une trame ne se convertit pas en commande.
            unset($quoteLineEndpoints['storeOrder']);
          }
          if (config('cad.line_import')) {
            $quoteLineEndpoints['importCad'] = route('quotes.lines.json.import-cad', ['quoteId' => $Quote->id]);
          }
        @endphp
        <div class="card mb-3">
          <div class="card-body">
            <div
              id="quote-lines-page-app"
              data-quote-id="{{ $Quote->id }}"
              data-quote-statu="{{ $Quote->statu }}"
              data-endpoints="{{ json_encode($quoteLineEndpoints) }}"
              data-trans="{{ json_encode(['equipment_tags' => __('equipment_tags'), 'quote_margin' => __('quote_margin')]) }}"
            >
              <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin me-2"></i> Chargement des lignes...
              </div>
            </div>
          </div>
        </div>
      </div>
      @if($Factory->enable_construction_site)
      <div class="tab-pane " id="Construction">
        <div
          id="construction-site-app"
          data-quote-id="{{ $Quote->id }}"
          data-save-url="{{ route('quotes.project.estimates', ['id' => $Quote->id]) }}"
          data-currency="{{ $Factory->curency }}"
          data-initial-data="{{ json_encode($projectEstimate) }}"
        ></div>
      </div>
      @endif
      <div class="tab-pane" id="Charts">
        <div
          id="quote-charts-tab-app"
          data-product-time="{{ json_encode(array_values($TotalServiceProductTime)) }}"
          data-setting-time="{{ json_encode(array_values($TotalServiceSettingTime)) }}"
          data-cost="{{ json_encode(array_values($TotalServiceCost)) }}"
          data-price="{{ json_encode(array_values($TotalServicePrice)) }}"
          data-currency="{{ $Factory->curency }}"
          data-trans="{{ json_encode([
            'productTime' => __('general_content.total_product_time_by_service'),
            'settingTime' => __('general_content.total_setting_time_by_service'),
            'cost'        => __('general_content.total_cost_by_service'),
            'price'       => __('general_content.total_price_by_service'),
          ]) }}"
        ></div>
      </div>  
      <div class="tab-pane " id="Views">
        <x-adminlte-card title="{{ __('general_content.view_count_trans_key') }}" theme="primary" maximizable>
          @forelse($Quote->guestVisits as $visit)
          <p>{{ __('general_content.date_trans_key') }}: {{ $visit->GetPrettyCreatedAttribute() }}</p>
          @empty
          <p>{{ __('general_content.no_data_trans_key') }}</p>
          @endforelse
        </x-adminlte-card>
        <div
          data-react="chat-live"
          data-endpoints="{{ json_encode([
            'list'        => route('chats.index', ['related_id' => $Quote->id, 'related_type' => 'Quotes']),
            'store'       => route('chats.store'),
            'relatedId'   => $Quote->id,
            'relatedType' => 'Quotes',
          ]) }}"
          data-trans="{{ json_encode([
            'add_trans_key'     => __('general_content.add_trans_key'),
            'no_data_trans_key' => __('general_content.no_data_trans_key'),
          ]) }}"
        ></div>
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Quote->id, 'type' => 'quote'])
      </div>
      @endif
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Quote->statu == 1)
        <x-InfocalloutComponent :note="__('general_content.csv_quote_info_trans_key')"  />

        <form method="POST" action="{{ route('quotes.lines.import', ['idQuote'=>  $Quote->id]) }}" enctype="multipart/form-data">
          <x-adminlte-card title="{{ __('general_content.choose_file_trans_key') }}" theme="primary" maximizable>
              @csrf
              <div class="card-body">
                  {{-- Placeholder, sm size and prepend icon --}}
                  <x-adminlte-input-file name="import_file" igroup-size="sm" placeholder="{{ __('general_content.choose_csv_trans_key') }}">
                      <x-slot name="prependSlot">
                          <div class="input-group-text text-bg-sky">
                              <i class="fas fa-upload"></i>
                          </div>
                      </x-slot>
                  </x-adminlte-input-file>
              </div>
              <div class="card-body">
                  <div class="row">
                      <div class="col-4 text-end"><label class="col-form-label"> {{ __('general_content.header_line_ask_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input-switch name="header" is-checked="true" />
                      </div>
                  </div>
                  
                  @php
                    $fields = [
                        ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'text-bg-danger', 'required' => true],
                        ['name' => 'label', 'label' => __('general_content.label_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'text-bg-danger', 'required' => true],
                        ['name' => 'qty', 'label' => __('general_content.qty_trans_key'), 'icon' => 'fas fa-circle', 'color' => 'text-bg-primary', 'type' => 'number', 'required' => false],
                        ['name' => 'selling_price', 'label' => __('general_content.price_trans_key'), 'icon' => 'fas fa-cash-register', 'color' => 'text-bg-violet', 'required' => true],
                        ['name' => 'discount', 'label' => __('general_content.discount_trans_key'), 'icon' => 'fas fa-percentage', 'color' => 'text-bg-warning', 'required' => false],
                        ['name' => 'delivery_date', 'label' => __('general_content.delivery_date_trans_key'), 'icon' => 'fas fa-calendar-alt', 'color' => 'text-bg-secondary', 'required' => false],
                    ];
                  @endphp

                  @foreach ($fields as $field)
                  <div class="row">
                      <div class="col-4 text-end">
                          <label class="col-form-label">{{ $field['label'] }}</label>
                      </div>
                      <div class="col-8">
                          @if($field['required'] == true)
                              <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                  <x-slot name="appendSlot">
                                      <div class="input-group-text {{ $field['color'] }}">
                                          <i class="{{ $field['icon'] }}"></i>
                                      </div>
                                  </x-slot>
                              </x-adminlte-input>
                          @else
                              <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                  <x-slot name="appendSlot">
                                      <div class="input-group-text {{ $field['color'] }}">
                                          <i class="{{ $field['icon'] }}"></i>
                                      </div>
                                  </x-slot>
                              </x-adminlte-input>
                          @endif
                      </div>
                  </div>
                  @endforeach

              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
          </x-adminlte-card>
        </form>
        @else
        <x-adminlte-alert theme="info" title="{{ __('general_content.info_trans_key') }}">
            {{ __('general_content.info_statu_trans_key') }}
        </x-adminlte-alert>
        @endif
      </div>
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'quote',
          'fileableId'   => $Quote->id,
        ])
      </div>
      <div class="tab-pane " id="Logs">
        @include('include.logs-viewer-mount', ['logsSubjectType' => 'App\Models\Workflow\Quotes', 'logsSubjectId' => $Quote->id])
      </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->

@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')

  <script>
    function copyToClipboard(text) {
        // Create a temporary textarea element
        var tempTextarea = document.createElement("textarea");
        tempTextarea.value = text;
        
        // Add it to the document body
        document.body.appendChild(tempTextarea);
        
        // Select the text in the textarea
        tempTextarea.select();
        tempTextarea.setSelectionRange(0, 99999); // For mobile devices
        
        // Copy the text inside the textarea to clipboard
        document.execCommand("copy");
        
        // Remove the temporary textarea
        document.body.removeChild(tempTextarea);
        
        // Optionally, you can show a message indicating that the text has been copied
        // alert("Lien copié dans le presse-papier !");
    }
  </script>
@stop
