      <x-InfocalloutComponent note="You can define as many tax rates as you want, depending on the types of the quoted or sold products / components."  />
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.vat_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.rate_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($VATs as $VAT)
                  <tr>
                    <td>{{ $VAT->code }}</td>
                    <td>{{ $VAT->label }}</td>
                    <td>{{ $VAT->rate }}</td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioVAT{{ $VAT->id }}" name="customRadioVAT"  @if( $VAT->default == 1 ) checked @endif disabled>
                        <label for="customRadioVAT{{ $VAT->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $VAT->id }} -->
                      <x-ButtonTextEdit :modalTarget="'VAT' . $VAT->id" />
                      <!-- Modal {{ $VAT->id }} -->
                        <form method="POST" action="{{ route('accounting.vat.update', ['id' => $VAT->id]) }}" enctype="multipart/form-data">
                          <x-adminlte-modal id="VAT{{ $VAT->id }}" title="Update {{ $VAT->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $VAT->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="rate">rate</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01" value="{{ $VAT->rate }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($VAT->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($VAT->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                            <hr>
                            <p class="text-muted mb-2"><small>{{ __('vat.en16931_help') }}</small></p>
                            <div class="form-group">
                              <label>{{ __('vat.en16931_category') }}</label>
                              <select class="form-control" name="en16931_category">
                                <option value="" @if(!$VAT->en16931_category) selected @endif>{{ __('vat.category_fallback') }}</option>
                                @foreach (__('vat.categories') as $code => $lib)
                                  <option value="{{ $code }}" @if($VAT->en16931_category === $code) selected @endif>{{ $lib }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group">
                              <label>{{ __('vat.exemption_reason_code') }}</label>
                              <input type="text" class="form-control" name="exemption_reason_code" value="{{ $VAT->exemption_reason_code }}" placeholder="VATEX-EU-IC">
                            </div>
                            <div class="form-group">
                              <label>{{ __('vat.exemption_reason_text') }}</label>
                              <input type="text" class="form-control" name="exemption_reason_text" value="{{ $VAT->exemption_reason_text }}" placeholder="{{ __('vat.exemption_text_example') }}">
                            </div>
                            <div class="form-group">
                              <label>{{ __('vat.legal_mention') }}</label>
                              <textarea class="form-control" name="legal_mention" rows="2" placeholder="{{ __('vat.legal_mention_hint') }}">{{ $VAT->legal_mention }}</textarea>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                        </x-adminlte-modal>
                      </form>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.rate_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.vat.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_vat_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                </div>
              </div>
              <div class="form-group">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                </div>
              </div>
              <div class="form-group">
                <label for="rate">rate</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                  </div>
                  <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01">
                </div>
              </div>
              <div class="form-group">
                <label>{{ __('vat.en16931_category') }}</label>
                <select class="form-control" name="en16931_category">
                  <option value="">{{ __('vat.category_fallback') }}</option>
                  @foreach (__('vat.categories') as $code => $lib)
                    <option value="{{ $code }}">{{ $lib }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label>{{ __('vat.exemption_reason_code') }}</label>
                <input type="text" class="form-control" name="exemption_reason_code" placeholder="VATEX-EU-IC">
              </div>
              <div class="form-group">
                <label>{{ __('vat.exemption_reason_text') }}</label>
                <input type="text" class="form-control" name="exemption_reason_text" placeholder="{{ __('vat.exemption_text_example') }}">
              </div>
              <div class="form-group">
                <label>{{ __('vat.legal_mention') }}</label>
                <textarea class="form-control" name="legal_mention" rows="2"></textarea>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
          <!-- /.card body -->
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
