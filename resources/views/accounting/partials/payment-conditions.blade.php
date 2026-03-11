      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.payment_conditions_trans_key') }}" theme="primary" maximizable>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.number_of_month_trans_key') }}</th>
                    <th>{{__('general_content.number_of_day_trans_key') }}</th>
                    <th>{{__('general_content.end_of_month_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($PaymentConditions as $PaymentCondition)
                  <tr>
                    <td>{{ $PaymentCondition->code }}</td>
                    <td>{{ $PaymentCondition->label }}</td>
                    <td>{{ $PaymentCondition->number_of_month }}</td>
                    <td>{{ $PaymentCondition->number_of_day }}</td>
                    <td>
                      @if($PaymentCondition->month_end  == 1) Yes @endif
                      @if($PaymentCondition->month_end  == 2) No @endif
                    </td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioPaymentCondition{{ $PaymentCondition->id }}" name="customRadioPaymentCondition"  @if( $PaymentCondition->default == 1 ) checked @endif disabled>
                        <label for="customRadioPaymentCondition{{ $PaymentCondition->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $PaymentCondition->id }} -->
                      <x-ButtonTextEdit :modalTarget="'PaymentCondition' . $PaymentCondition->id" />
                      <!-- Modal {{ $PaymentCondition->id }} -->
                      <form method="POST" action="{{ route('accounting.paymentCondition.update', ['id' => $PaymentCondition->id]) }}" enctype="multipart/form-data">
                        <x-adminlte-modal id="PaymentCondition{{ $PaymentCondition->id }}" title="Update {{ $PaymentCondition->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $PaymentCondition->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="number_of_month">{{__('general_content.number_of_month_trans_key') }}</label>
                              <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="{{__('general_content.number_of_month_trans_key') }}" value="{{ $PaymentCondition->number_of_month }}">
                            </div>
                            <div class="form-group">
                              <label for="number_of_day">{{__('general_content.number_of_day_trans_key') }}</label>
                              <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="{{__('general_content.number_of_day_trans_key') }}" value="{{ $PaymentCondition->number_of_day }}">
                            </div>
                            <div class="form-group">
                              <div class="col-4 text-left"><label for="month_end_update{{ $PaymentCondition->id }}" class="col-form-label">{{ __('general_content.end_of_month_trans_key') }}</label></div>
                              <div class="col-8">
                                                          
                                  @if($PaymentCondition->month_end == 1)  
                                  <x-adminlte-input-switch id="month_end_update{{ $PaymentCondition->id }}" name="month_end_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true"/>
                                  @else
                                  <x-adminlte-input-switch id="month_end_update{{ $PaymentCondition->id }}" name="month_end_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                  @endif
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="default">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($PaymentCondition->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($PaymentCondition->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </x-slot>
                        </x-adminlte-modal>
                      </form>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.number_of_month_trans_key') }}</th>
                    <th>{{__('general_content.number_of_day_trans_key') }}</th>
                    <th>{{__('general_content.end_of_month_trans_key') }}</th>
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
          <form  method="POST" action="{{ route('accounting.paymentCondition.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_payment_conditions_trans_key') }}" theme="secondary" maximizable>
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
                <label for="number_of_month">{{__('general_content.number_of_month_trans_key') }}</label>
                <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="{{__('general_content.number_of_month_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="number_of_day">{{__('general_content.number_of_day_trans_key') }}</label>
                <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="{{__('general_content.number_of_day_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="month_end" class="col-form-label">{{ __('general_content.end_of_month_trans_key') }}</label>
                <x-adminlte-input-switch name="month_end" id="month_end" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}"
                data-on-color="teal" is-checked="true"/>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
