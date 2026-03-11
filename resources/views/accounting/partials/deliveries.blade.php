      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.delivery_notes_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($Deleverys as $Delevery)
                  <tr>
                    <td>{{ $Delevery->code }}</td>
                    <td>{{ $Delevery->label }}</td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioDelevery{{ $Delevery->id }}" name="customRadioDelevery"  @if( $Delevery->default == 1 ) checked @endif disabled>
                        <label for="customRadioDelevery{{ $Delevery->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $Delevery->id }} -->
                      <x-ButtonTextEdit :modalTarget="'Delevery' . $Delevery->id" />
                      <!-- Modal {{ $Delevery->id }} -->
                      <x-adminlte-modal id="Delevery{{ $Delevery->id }}" title="Update {{ $Delevery->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('accounting.delivery.update', ['id' => $Delevery->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Delevery->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($Delevery->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($Delevery->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
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
          <form  method="POST" action="{{ route('accounting.delivery.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_delevery_method_trans_key') }}" theme="secondary" maximizable>
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
              <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
