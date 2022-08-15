                    <div class="card-header p-2">
                        <!-- Modal -->
                        <div wire:ignore.self class="modal fade" id="ModalCompanie" tabindex="-1" role="dialog" aria-labelledby="ModalCompanieTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ModalCompanieTitle">New companie</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form>
                                        @csrf
                                        <div class="card card-body">
                                            <div class="row">
                                                <div class="col-4">
                                                    <label for="code">External ID</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control @error('code') is-invalid @enderror" wire:model="code" name="code" id="code" placeholder="External ID">
                                                    </div>
                                                    @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-4">
                                                    <label for="label">Name of company</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control @error('label') is-invalid @enderror" wire:model="label" name="label"  id="label" placeholder="Name of company">
                                                    </div>
                                                    @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-4">
                                                    <label for="user_id">Technical manager</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                        </div>
                                                        <select class="form-control" name="user_id" id="user_id" wire:model="user_id">
                                                            <option value="">Select user</option>
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
                                                <label for="InputWebSite">Site link</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="website" id="website" wire:model="website" placeholder="Web site link">
                                                    </div>
                                                    @error('website') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="fbsite" id="fbsite" wire:model="fbsite"  placeholder="Facebook link">
                                                    </div>
                                                    @error('fbsite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="twittersite" id="twittersite" wire:model="twittersite" placeholder="Twitter link">
                                                    </div>
                                                    @error('twittersite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                                        </div>
                                                    <input type="text" class="form-control"  name="lkdsite" id="lkdsite" wire:model="lkdsite" placeholder="Linkedin link">
                                                    </div>
                                                    @error('lkdsite') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-body">
                                            <div class="row">
                                                <label for="siren">Administrative information</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-3">
                                                    <input type="text" class="form-control" name="siren" id="siren" wire:model="siren" placeholder="Siren">
                                                    @error('siren') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" class="form-control" name="naf_code" id="naf_code" wire:model="naf_code" placeholder="Naf code">
                                                    @error('naf_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" class="form-control" name="intra_community_vat" id="intra_community_vat" wire:model="intra_community_vat" placeholder="VAT number">
                                                    @error('intra_community_vat') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-body">
                                            <div class="row">
                                                <label for="siren">Client information</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-3">
                                                    <label for="discount">Discount :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                        </div>
                                                        <input type="number" class="form-control" name="discount" id="discount" wire:model="discount" placeholder="Discount">
                                                    </div>
                                                    @error('discount') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <label for="account_general_customer">General Account</label>
                                                    <input type="number" class="form-control" name="account_general_customer" id="account_general_customer" wire:model="account_general_customer" placeholder="General Account">
                                                    @error('account_general_customer') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <label for="account_auxiliary_customer">Auxiliary Account</label>
                                                    <input type="number" class="form-control" name="account_auxiliary_customer" id="account_auxiliary_customer" wire:model="account_auxiliary_customer" placeholder="Auxiliary account">
                                                    @error('account_auxiliary_customer') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-body">
                                            <div class="row">
                                                <label for="siren">Supplier information</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-3">
                                                    <label for="recept_controle">Reception control</label>
                                                    <select class="form-control" name="recept_controle" id="recept_controle" wire:model="recept_controle">
                                                        <option value="">Select controle type</option>
                                                        <option value="1">Yes</option>
                                                        <option value="2">No</option>
                                                    </select>
                                                    @error('recept_controle') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <label for="account_general_supplier">General Account</label>
                                                    <input type="number" class="form-control" id="account_general_supplier" name="account_general_supplier"  wire:model="account_general_supplier" placeholder="General Account">
                                                    @error('account_general_supplier') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                                <div class="col-3">
                                                    <label for="account_auxiliary_supplier">Auxiliary Account</label>
                                                    <input type="number" class="form-control" id="account_auxiliary_supplier" name="account_auxiliary_supplier"  wire:model="account_auxiliary_supplier" placeholder="Auxiliary account">
                                                    @error('account_auxiliary_supplier') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label>Comment</label>
                                                    <textarea class="form-control" rows="3" name="comment"  wire:model="comment" placeholder="Enter ..."></textarea>
                                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="button" wire:click.prevent="storeCompany()" class="btn btn-success">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Modal -->

                    <div class="card-body">
                        <div class="table-responsive">
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                                    </div>
                                    <input type="text" class="form-control" wire:model="search" placeholder="Search company">
                                </div>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th >
                                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'code'])</a>
                                            </th>
                                            <th>
                                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                                            </th>
                                            <th>
                                                <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">Created At @include('include.sort-icon', ['field' => 'created_at'])</a>
                                            </th>
                                            <th>Statu client</th>
                                            <th>Statu supplier</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($Companieslist as $Companie)
                                    <tr>
                                        <td>{{ $Companie->code }}</td>
                                        <td>{{ $Companie->label }}</td>
                                        <td>{{ $Companie->GetPrettyCreatedAttribute() }}</td>
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
                                            <th>Code</th>
                                            <th>Label</th>
                                            <th>Created At</th>
                                            <th>Statu client</th>
                                            <th>Statu supplier</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- /.row -->
                        {{ $Companieslist->links() }}
                    </div>
                </div>
                <!-- /.card-body -->