
<div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalQuote" tabindex="-1" role="dialog" aria-labelledby="ModalQuoteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="ModalQuoteTitle">{{ __('general_content.new_quote_trans_key') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                    @csrf
                        <div class="card card-body">
                            @include('include.alert-result')
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  wire:model.live="code"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" >
                                    </div>
                                    @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="label">{{ __('general_content.name_quote_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  wire:model.live="label" name="label"  id="label"  placeholder="{{ __('general_content.name_quote_trans_key') }}" required>
                                    </div>
                                    @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control"  wire:model.live="user_id" name="user_id" id="user_id">
                                            <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                            @foreach ($userSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">{{ __('general_content.customer_info_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="companies_id">{{ __('general_content.companie_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        </div>
                                        <select class="form-control"  wire:model.live="companies_id" name="companies_id" id="companies_id">
                                            <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                                            @forelse ($CompanieSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="customer_reference">{{ __('general_content.customer_reference_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  wire:model.live="customer_reference" name="customer_reference"  id="customer_reference" placeholder="{{ __('general_content.customer_reference_trans_key') }}">
                                    </div>
                                    @error('customer_reference') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="companies_addresses_id">{{ __('general_content.adress_name_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            @if($companies_id)
                                            <!-- Button Modal -->
                                            <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#CutomerAddresses">
                                                <i class="fas fa-map-marked-alt"></i>
                                            </button>
                                            <!-- Modal Cutomer addresses -->
                                            <x-adminlte-modal id="CutomerAddresses" title="{{ __('general_content.new_address_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                <form>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                                                    </div>
                                                                <input type="number" class="form-control" wire:model="CustomerAddressesOrdre" placeholder="{{ __('general_content.sort_trans_key') }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                    </div>
                                                                    <input type="text" class="form-control" wire:model="CustomerAddressesLabel" placeholder="{{__('general_content.label_trans_key') }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="adress">{{ __('general_content.adress_name_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerAdress" placeholder="{{ __('general_content.adress_name_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="zipcode">{{ __('general_content.postal_code_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerZipcode" placeholder="{{ __('general_content.postal_code_trans_key') }}">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="city">{{ __('general_content.city_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerCity" placeholder="{{ __('general_content.city_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="country">{{ __('general_content.country_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerCountry" placeholder="{{ __('general_content.country_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerAddressesNumber" placeholder="{{ __('general_content.phone_trans_key') }}">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                                                                <input type="email" class="form-control" wire:model="CustomerAddressesCity" placeholder="{{ __('general_content.email_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" wire:click.prevent="saveCutomerAddresses()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </x-adminlte-modal>
                                            @else
                                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                            @endif
                                        </div>
                                        <select class="form-control"  wire:model.live="companies_addresses_id"  name="companies_addresses_id" id="companies_addresses_id">
                                            <option value="">{{ __('general_content.select_address_trans_key') }}</option>
                                            @forelse ($AddressSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->label }} - {{ $item->adress }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_address_trans_key') }}</option>
                                        @endforelse
                                        </select>
                                    </div>
                                    @error('companies_addresses_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="companies_contacts_id">{{ __('general_content.contact_name_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            @if($companies_id)
                                            <!-- Button Modal -->
                                            <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#CutomerContact">
                                                <i class="fas fa-user"></i>
                                            </button>
                                            <!-- Modal Cutomer Contact -->
                                            <x-adminlte-modal id="CutomerContact" title="{{ __('general_content.new_contact_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                <form>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                                                    </div>
                                                                    <input type="number" class="form-control" wire:model="CustomerContactOrdre" placeholder="{{ __('general_content.sort_trans_key') }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="civility">{{ __('general_content.civility_trans_key') }}</label>
                                                                <select class="form-control" wire:model="CustomerCivility">
                                                                    <option value="{{ __('general_content.miss_trans_key') }}">{{ __('general_content.miss_trans_key') }}</option>
                                                                    <option value="{{ __('general_content.ms_trans_key') }}">{{ __('general_content.ms_trans_key') }}</option>
                                                                    <option value="{{ __('general_content.mr_trans_key') }}">{{ __('general_content.mr_trans_key') }}</option>
                                                                    <option value="{{ __('general_content.mrs_trans_key') }}">{{ __('general_content.mrs_trans_key') }}</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="first_name">{{ __('general_content.first_name_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerFirstName" placeholder="{{ __('general_content.first_name_trans_key') }}">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="name">{{ __('general_content.name_trans_key') }}</label>
                                                                <input type="text" class="form-control"wire:model="CustomerName" placeholder="{{ __('general_content.name_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="form-group">
                                                            <label for="function">{{ __('general_content.function_trans_key') }}</label>
                                                            <input type="text" class="form-control" wire:model="CustomerFunction" placeholder="{{ __('general_content.function_trans_key') }}">
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerContactNumber" placeholder="{{ __('general_content.phone_trans_key') }}">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="mobile">{{ __('general_content.mobile_phone_trans_key') }}</label>
                                                                <input type="text" class="form-control" wire:model="CustomerMobile" placeholder="{{ __('general_content.mobile_phone_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                                                            <input type="email" class="form-control" wire:model="CustomerContactMail" placeholder="{{ __('general_content.email_trans_key') }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" wire:click.prevent="saveCutomerContact()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                                                    </div>
                                                </form>
                                            </x-adminlte-modal>
                                            @else
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            @endif
                                        </div>
                                        <select class="form-control"  wire:model.live="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id">
                                            <option value="">{{ __('general_content.select_contact_trans_key') }}</option>
                                            @forelse ($ContactSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->first_name }} - {{ $item->name }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_contact_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('companies_contacts_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="accounting_payment_conditions_id">{{ __('general_content.payment_conditions_trans_key') }}</label>
                                    <select class="form-control"  wire:model.live="accounting_payment_conditions_id" name="accounting_payment_conditions_id" id="accounting_payment_conditions_id">
                                        <option value="">{{ __('general_content.select_payement_condition_trans_key') }}</option>
                                        @forelse ($AccountingConditionSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_payment_conditions_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                    @error('accounting_payment_conditions_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="accounting_payment_methods_id">{{ __('general_content.select_payement_methods_trans_key') }}</label>
                                    <select class="form-control"  wire:model.live="accounting_payment_methods_id" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                                        <option value="">{{ __('general_content.select_payement_methods_trans_key') }}</option>
                                        @forelse ($AccountingMethodsSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_payment_methods_trans_key') }}</option>
                                        @endforelse
                                    </select>
                                    @error('accounting_payment_methods_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="accounting_deliveries_id">{{ __('general_content.delevery_method_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                        </div>
                                        <select class="form-control"  wire:model.live="accounting_deliveries_id" name="accounting_deliveries_id" id="accounting_deliveries_id">
                                            <option value="">{{ __('general_content.select_delivery_trans_key') }}</option>
                                        @forelse ($AccountingDeleveriesSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_delivery_trans_key') }}</option>
                                        @endforelse
                                        </select>
                                    </div>
                                    @error('accounting_deliveries_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="label">{{ __('general_content.validity_date_trans_key') }}</label>
                                    <input type="date" class="form-control"  wire:model.live="validity_date" name="validity_date"  id="validity_date">
                                    @error('validity_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label>{{ __('general_content.comment_trans_key') }}</label>
                                    <textarea class="form-control" rows="3"  wire:model.live="comment" name="comment"  placeholder="..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                            <button type="Submit" wire:click.prevent="storeQuote()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    @include('include.search-card')
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus">
                                <option value="" selected>{{ __('general_content.select_statu_trans_key') }}</option>
                                <option value="1">{{ __('general_content.open_trans_key') }}</option>
                                <option value="2">{{ __('general_content.send_trans_key') }}</option>
                                <option value="3">{{ __('general_content.win_trans_key') }}</option>
                                <option value="4">{{ __('general_content.lost_trans_key') }}</option>
                                <option value="5">{{ __('general_content.closed_trans_key') }}</option>
                                <option value="6">{{ __('general_content.obsolete_trans_key') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalQuote">
                        {{ __('general_content.new_quote_trans_key') }}
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')" role="button" href="#">{{__('general_content.customer_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                            </th>
                            <th>{{__('general_content.code_trans_key') }}</th>
                            <th>{{__('general_content.lines_count_trans_key') }}</th>
                            <th>{{__('general_content.total_price_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }}@include('include.sort-icon', ['field' => 'created_at'])</a>
                            </th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Quoteslist as $Quote)
                        <tr>
                            <td>{{ $Quote->code }}</td>
                            <td>{{ $Quote->label }}</td>
                            <td>
                                <x-CompanieButton id="{{ $Quote->companies_id }}" label="{{ $Quote->companie['label'] }}"  />
                            </td>
                            <td>{{ $Quote->customer_reference }}</td>
                            <td>{{ $Quote->quote_lines_count }}</td>
                            <td>{{ $Quote->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                            <td>
                                @if(1 == $Quote->statu )   <span class="badge badge-info"> {{ __('general_content.open_trans_key') }}</span>@endif
                                @if(2 == $Quote->statu )  <span class="badge badge-warning">{{ __('general_content.send_trans_key') }}</span>@endif
                                @if(3 == $Quote->statu )  <span class="badge badge-success">{{ __('general_content.win_trans_key') }}</span>@endif
                                @if(4 == $Quote->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif
                                @if(5 == $Quote->statu )  <span class="badge badge-secondary">{{ __('general_content.closed_trans_key') }}</span>@endif
                                @if(6 == $Quote->statu )   <span class="badge badge-secondary">{{ __('general_content.obsolete_trans_key') }}</span>@endif
                            </td>
                            <td><img src="{{ Avatar::create($Quote->UserManagement['name'])->toBase64() }}" /></td>
                            <td>{{ $Quote->GetPrettyCreatedAttribute() }}</td>
                            <td>
                                <x-ButtonTextView route="{{ route('quotes.show', ['id' => $Quote->id])}}" />
                                <x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" />
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{__('general_content.id_trans_key') }} </th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{__('general_content.code_trans_key') }}</th>
                            <th>{{__('general_content.lines_count_trans_key') }}</th>
                            <th>{{__('general_content.total_price_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>{{__('general_content.created_at_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{ $Quoteslist->links() }}
        <!-- /.card-body -->
        </div>
    <!-- /.card -->
    </div>
<!-- /.div -->
</div>
