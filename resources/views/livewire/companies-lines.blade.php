            <div>
                <!-- Modal -->
                <div wire:ignore.self class="modal fade" id="ModalCompanie" tabindex="-1" role="dialog" aria-labelledby="ModalCompanieTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title" id="ModalCompanieTitle">{{ __('general_content.new_companie_trans_key') }}</h5>
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
                                                    <input type="text" class="form-control @error('code') is-invalid @enderror"  wire:model.live="code" name="code" id="code" placeholder="AAA000">
                                                </div>
                                                @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label> 
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    </div>
                                                    <select class="form-control" name="user_id" id="user_id"  wire:model.live="user_id">
                                                        <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                                    @foreach ($userSelect as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                    </select>
                                                </div>
                                                @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                                <div class="form-group col-md-4">
                                                    <label for="client_type">{{ __('general_content.customer_type_trans_key') }}</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                                        </div>
                                                        <select class="form-control" id="client_type" wire:click.prevent="toggleClientType()" wire:model.live="client_type">
                                                            <option value="1">{{ __('general_content.legal_entity_trans_key') }}</option>
                                                            <option value="2">{{ __('general_content.individual_trans_key') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="form-row">
                                            @if ($client_type === 2)
                                            <div class="form-group col-md-4">
                                                <label for="label">{{ __('general_content.civility_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="civility" wire:model.live="civility" placeholder="{{ __('general_content.civility_trans_key') }}"> 
                                                </div>
                                                @error('civility') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="label">{{ __('general_content.first_name_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="label" wire:model.live="label" placeholder="{{ __('general_content.first_name_trans_key') }}"> 
                                                </div>
                                                @error('label') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="last_name">{{ __('general_content.contact_name_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control" id="last_name" wire:model.live="last_name" placeholder="{{ __('general_content.contact_name_trans_key') }}">
                                                </div>
                                                @error('last_name') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            @else
                                            <div class="form-group col-md-4">
                                                <label for="label">{{ __('general_content.name_company_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control @error('label') is-invalid @enderror"  wire:model.live="label" name="label"  id="label" placeholder="{{ __('general_content.name_company_trans_key') }}">
                                                </div>
                                                @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card card-body">
                                        <div class="row">
                                            <label for="InputWebSite">{{ __('general_content.site_link_trans_key') }}</label>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="website" id="website"  wire:model.live="website" placeholder="Web site">
                                                </div>
                                                @error('website') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="fbsite" id="fbsite"  wire:model.live="fbsite"  placeholder="Facebook">
                                                </div>
                                                @error('fbsite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="twittersite" id="twittersite"  wire:model.live="twittersite" placeholder="X">
                                                </div>
                                                @error('twittersite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                                    </div>
                                                <input type="text" class="form-control"  name="lkdsite" id="lkdsite"  wire:model.live="lkdsite" placeholder="Linkedin">
                                                </div>
                                                @error('lkdsite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card card-body">
                                        <div class="row">
                                            <label for="siren">{{ __('general_content.administrative_information_trans_key') }}</label>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <input type="text" class="form-control" name="siren" id="siren"  wire:model.live="siren" placeholder="{{ __('general_content.reg_number_trans_key') }}" @if($client_type == 2) disabled @endif>
                                                @error('siren') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <input type="text" class="form-control" name="naf_code" id="naf_code"  wire:model.live="naf_code" placeholder="{{ __('general_content.naf_code_trans_key') }}" @if($client_type == 2) disabled @endif>
                                                @error('naf_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <input type="text" class="form-control" name="intra_community_vat" id="intra_community_vat"  wire:model.live="intra_community_vat" placeholder="{{ __('general_content.vat_number_trans_key') }}" @if($client_type == 2) disabled @endif>
                                                @error('intra_community_vat') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card card-body">
                                        <div class="row">
                                            <label for="siren">{{ __('general_content.client_information_trans_key') }}</label>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="discount">{{ __('general_content.discount_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                    </div>
                                                    <input type="number" class="form-control" name="discount" id="discount"  wire:model.live="discount" placeholder="{{ __('general_content.discount_trans_key') }}">
                                                </div>
                                                @error('discount') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="account_general_customer">{{ __('general_content.general_account_trans_key') }}</label>
                                                <input type="number" class="form-control" name="account_general_customer" id="account_general_customer"  wire:model.live="account_general_customer" placeholder="{{ __('general_content.general_account_trans_key') }}">
                                                @error('account_general_customer') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="account_auxiliary_customer">{{ __('general_content.auxiliary_account_trans_key') }}</label>
                                                <input type="number" class="form-control" name="account_auxiliary_customer" id="account_auxiliary_customer"  wire:model.live="account_auxiliary_customer" placeholder="{{ __('general_content.auxiliary_account_trans_key') }}">
                                                @error('account_auxiliary_customer') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card card-body">
                                        <div class="row">
                                            <label for="siren">{{ __('general_content.supplier_info_trans_key') }}</label>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="recept_controle">{{ __('general_content.reception_control_trans_key') }}</label>
                                                <select class="form-control" name="recept_controle" id="recept_controle"  wire:model.live="recept_controle">
                                                    <option value="">{{ __('general_content.select_control_type_trans_key') }}</option>
                                                    <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                                                    <option value="2">{{ __('general_content.no_trans_key') }}</option>
                                                </select>
                                                @error('recept_controle') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="account_general_supplier">{{ __('general_content.general_account_trans_key') }}</label>
                                                <input type="number" class="form-control" id="account_general_supplier" name="account_general_supplier"   wire:model.live="account_general_supplier" placeholder="{{ __('general_content.general_account_trans_key') }}">
                                                @error('account_general_supplier') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="account_auxiliary_supplier">{{ __('general_content.auxiliary_account_trans_key') }}</label>
                                                <input type="number" class="form-control" id="account_auxiliary_supplier" name="account_auxiliary_supplier"   wire:model.live="account_auxiliary_supplier" placeholder="{{ __('general_content.auxiliary_account_trans_key') }}">
                                                @error('account_auxiliary_supplier') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <label>{{ __('general_content.comment_trans_key') }}</label>
                                                <textarea class="form-control" rows="3" name="comment"   wire:model.live="comment" placeholder="..."></textarea>
                                                @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                                        <button type="button" wire:click.prevent="storeCompany()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
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
                            <div class="col-md-10">
                                @include('include.search-card')
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
                                    {{ __('general_content.new_companie_trans_key') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th >
                                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                                        </th>
                                        <th>
                                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.customer_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                                        </th>
                                        <th>
                                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }} @include('include.sort-icon', ['field' => 'created_at'])</a>
                                        </th>
                                        <th>{{__('general_content.active_trans_key') }}</th>
                                        <th>{{ __('general_content.status_client_trans_key') }}</th>
                                        <th>{{ __('general_content.status_supplier_trans_key') }}</th>
                                        <th>{{__('general_content.action_trans_key') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse ($Companieslist as $Companie)
                                <tr>
                                    <td>{{ $Companie->code }}</td>
                                    <td>{{ $Companie->label }}</td>
                                    <td>{{ $Companie->GetPrettyCreatedAttribute() }}</td>
                                    <td>
                                        @if($Companie->active == 1 )
                                        <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                                        @else
                                        <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($Companie->statu_customer == 2 )
                                        <span class="badge badge-warning"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                                        @elseif($Companie->statu_customer == 3 )
                                        <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check-double"></i></span>
                                        @else
                                        <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($Companie->statu_supplier == 2 )
                                        <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                                        @else
                                        <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-ButtonTextView route="{{ route('companies.show', ['id' => $Companie->id])}}" />
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="flex justify-center items-center">
                                            <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No companies found ...</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>{{__('general_content.id_trans_key') }} </th>
                                        <th>{{__('general_content.customer_trans_key') }}</th>
                                        <th>{{__('general_content.created_at_trans_key') }}</th>
                                        <th>{{__('general_content.active_trans_key') }}</th>
                                        <th>{{ __('general_content.status_client_trans_key') }}</th>
                                        <th>{{ __('general_content.status_supplier_trans_key') }}</th>
                                        <th>{{__('general_content.action_trans_key') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        {{ $Companieslist->links() }}
                    </div>
                </div>
            </div>