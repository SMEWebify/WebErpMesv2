    <div class="card-body">
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
                        <div class="row">
                            <div class="col-4">
                                <label for="CODE">External ID</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                    </div>
                                    <input type="text" class="form-control @error('CODE') is-invalid @enderror" wire:model="CODE" name="CODE" id="CODE" placeholder="External ID">
                                </div>
                                @error('CODE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-4">
                                <label for="LABEL">Name of company</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    </div>
                                    <input type="text" class="form-control @error('LABEL') is-invalid @enderror" wire:model="LABEL" name="LABEL"  id="LABEL" placeholder="Name of company">
                                </div>
                                @error('LABEL') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-4">
                                <label for="user_id">Technical manager</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <select class="form-control" name="user_id" id="user_id" wire:model="user_id">
                                    @foreach ($userSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                    </select>
                                </div>
                                @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <label for="InputWebSite">Site link</label>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                    </div>
                                    <input type="text" class="form-control"  name="WEBSITE" id="WEBSITE" wire:model="WEBSITE" placeholder="Web site link">
                                </div>
                                @error('WEBSITE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                                    </div>
                                    <input type="text" class="form-control"  name="FBSITE" id="FBSITE" wire:model="FBSITE"  placeholder="Facebook link">
                                </div>
                                @error('FBSITE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                                    </div>
                                    <input type="text" class="form-control"  name="TWITTERSITE" id="TWITTERSITE" wire:model="TWITTERSITE" placeholder="Twitter link">
                                </div>
                                @error('TWITTERSITE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                    </div>
                                <input type="text" class="form-control"  name="LKDSITE" id="LKDSITE" wire:model="LKDSITE" placeholder="Linkedin link">
                                </div>
                                @error('LKDSITE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <label for="SIREN">Administrative information</label>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <input type="text" class="form-control" name="SIREN" id="SIREN" wire:model="SIREN" placeholder="Siren">
                                @error('SIREN') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="APE" id="APE" wire:model="APE" placeholder="APE code">
                                @error('APE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="TVA_INTRA" id="TVA_INTRA" wire:model="TVA_INTRA" placeholder="VAT number">
                                @error('TVA_INTRA') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <label for="PICTURE">Logo file</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-image"></i></span>
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="PICTURE">
                                    <label class="custom-file-label" for="PICTURE">Choose file</label>
                                </div>
                                <div class="input-group-append">
                                    <span class="input-group-text">Upload</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-3">
                                <label for="statu_CLIENT">Statu client</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                    </div>
                                    <select class="form-control" name="statu_CLIENT" id="statu_CLIENT" wire:model="statu_CLIENT">
                                        <option value="1" selected>Inactive</option>
                                        <option value="2">Active</option>
                                        <option value="3">Prospect</option>
                                    </select>
                                    @error('statu_CLIENT') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="col-3">
                                <label for="discount">Discount :</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                    </div>
                                    <input type="number" class="form-control" name="DISCOUNT" id="DISCOUNT" wire:model="DISCOUNT" placeholder="Discount">
                                </div>
                                @error('DISCOUNT') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <label for="COMPTE_GEN_CLIENT">General Account</label>
                                <input type="number" class="form-control" name="COMPTE_GEN_CLIENT" id="COMPTE_GEN_CLIENT" wire:model="COMPTE_GEN_CLIENT" placeholder="General Account">
                                @error('COMPTE_GEN_CLIENT') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <label for="COMPTE_AUX_CLIENT">Auxiliary Account</label>
                                <input type="number" class="form-control" name="COMPTE_AUX_CLIENT" id="COMPTE_AUX_CLIENT" wire:model="COMPTE_AUX_CLIENT" placeholder="Auxiliary account">
                                @error('COMPTE_AUX_CLIENT') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-3">
                                <label for="statu_FOUR">Statu supplier</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                    </div>
                                    <select class="form-control" name="statu_FOUR" id="statu_FOUR"  wire:model="statu_FOUR">
                                        <option value="1" selected>Inactive</option>
                                        <option value="2">Active</option>
                                    </select>
                                @error('statu_FOUR') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="col-3">
                                <label for="RECEPT_CONTROLE">Reception control</label>
                                <select class="form-control" name="RECEPT_CONTROLE" id="RECEPT_CONTROLE" wire:model="RECEPT_CONTROLE">
                                    <option value="1">Yes</option>
                                    <option value="2">No</option>
                                </select>
                                @error('RECEPT_CONTROLE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <label for="COMPTE_GEN_FOUR">General Account</label>
                                <input type="number" class="form-control" id="COMPTE_GEN_FOUR" name="COMPTE_GEN_FOUR"  wire:model="COMPTE_GEN_FOUR" placeholder="General Account">
                                @error('COMPTE_GEN_FOUR') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-3">
                                <label for="COMPTE_AUX_FOUR">Auxiliary Account</label>
                                <input type="number" class="form-control" id="COMPTE_AUX_FOUR" name="COMPTE_AUX_FOUR"  wire:model="COMPTE_AUX_FOUR" placeholder="Auxiliary account">
                                @error('COMPTE_AUX_FOUR') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <label>Comment</label>
                                <textarea class="form-control" rows="3" name="COMMENT"  wire:model="COMMENT" placeholder="Enter ..."></textarea>
                                @error('COMMENT') <span class="text-danger">{{ $message }}<br/></span>@enderror
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

    <div class="card">
        <div class="table-responsive">
            <div class="card-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                    </div>
                    <input type="text" class="form-control" wire:model="search" placeholder="Search company">
                </div>
            </div>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th >
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('CODE')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'CODE'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('LABEL')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'LABEL'])</a>
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
                    <td>{{ $Companie->CODE }}</td>
                    <td>{{ $Companie->LABEL }}</td>
                    <td>{{ $Companie->GetPrettyCreatedAttribute() }}</td>
                    <td>
                        @if($Companie->statu_CLIENT == 2 )
                        <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check-double"></i></span>
                        @elseif($Companie->statu_CLIENT == 3 )
                        <span class="badge badge-warning"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                        @else
                        <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                        @endif
                    </td>
                    <td>
                        @if($Companie->statu_FOUR == 2 )
                        <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                        @else
                        <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                        @endif
                    </td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="{{ route('companies.show', ['id' => $Companie->id])}}">
                            <i class="fas fa-folder"></i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="#">
                            <i class="fas fa-pencil-alt"></i>
                            Edit
                        </a>
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
        <!-- /.row -->
        {{ $Companieslist->links() }}
    </div>
</div>
<!-- /.card-body -->