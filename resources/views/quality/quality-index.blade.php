@extends('adminlte::page')

@section('title', 'Quality')

@section('content_header')
    <h1>Quality</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Actions" data-toggle="tab">Actions</a></li>
      <li class="nav-item"><a class="nav-link" href="#Derogations" data-toggle="tab">Derogations</a></li>
      <li class="nav-item"><a class="nav-link" href="#NonConformities" data-toggle="tab">Non conformities</a></li>
      <li class="nav-item"><a class="nav-link" href="#MeasuringDevices" data-toggle="tab">Measuring devices</a></li>
      <li class="nav-item"><a class="nav-link" href="#Settings" data-toggle="tab">Settings</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Actions">
      <div class="card-body">
        <x-InfocalloutComponent note="Actions are measures taken to prevent a problem from occurring (preventive actions), to solve an existing problem (corrective actions), or to improve a process or product (improvement requests)."  />
        @include('include.alert-result')
        <div class="row">
          <table  class="table  table-striped">
            <thead>
            <tr>
              <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>Color</th>
                <th>NC</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($QualityActions as $QualityAction)
              <tr>
                <td>{{ $QualityAction->code }}</td>
                <td>{{ $QualityAction->label }}</td>
                <td>{{ $QualityAction->UserManagement['name'] }}</td>
                <td>
                  @if($QualityAction->type  == 1) <span class="badge badge-warning">Internal</span> @endif
                  @if($QualityAction->type  == 2) <span class="badge badge-danger">External</span> @endif
                </td>
                <td>
                  @if($QualityAction->statu  == 1) <span class="badge badge-info">In Progess</span> @endif
                  @if($QualityAction->statu  == 2) <span class="badge badge-warning">Waiting Customer Data</span> @endif
                  @if($QualityAction->statu  == 3) <span class="badge badge-success">Validate</span> @endif
                  @if($QualityAction->statu  == 4) <span class="badge badge-danger">Canceled</span> @endif
                </td>
                <td><input type="color" class="form-control"  name="color" id="color" value="{{ $QualityAction->color }}"></td>
                <td>
                  @if($QualityAction->quality_non_conformitie_id) 
                    {{ $QualityAction->QualityNonConformity->code }} 
                  @else
                    No NC link
                  @endif
                </td>
                <td>{{ $QualityAction->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityActionView{{ $QualityAction->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityAction->id }} -->
                  <x-adminlte-modal id="QualityActionView{{ $QualityAction->id }}" title="Info {{ $QualityAction->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                      <strong>Problem description : </strong> 
                      {{ $QualityAction->pb_descp }}
                    </div>
                    <div class="row">
                      <strong >Cause description :</strong> 
                      {{ $QualityAction->cause }}
                    </div>
                    <div class="row">
                      <strong >Action description :</strong> 
                      {{ $QualityAction->action }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityAction{{ $QualityAction->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityAction->id }} -->
                  <x-adminlte-modal id="QualityAction{{ $QualityAction->id }}" title="Update {{ $QualityAction->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.action.update', ['id' => $QualityAction->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">Label</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityAction->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">Statu</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityAction->statu == 1  ) Selected @endif>In Progess</option>
                              <option value="2"  @if($QualityAction->statu == 2  ) Selected @endif>Waiting Customer Data</option>
                              <option value="3"  @if($QualityAction->statu == 3  ) Selected @endif>Validate</option>
                              <option value="4"  @if($QualityAction->statu == 4  ) Selected @endif>Canceled</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="type">Type</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="type" id="type">
                              <option value="1" @if($QualityAction->type == 1  ) Selected @endif>Internal</option>
                              <option value="2" @if($QualityAction->type == 2  ) Selected @endif>External</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="user_id">User</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id">
                              @foreach ($userSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityAction->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="color">Color</label>
                          <input type="color" class="form-control"  name="color" id="color" value="{{ $QualityAction->color }}">
                        </div>
                        <div class="form-group">
                          <label for="quality_non_conformitie_id">Non conformitie link</label>
                          <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                            <option value="null">None</option>
                            @foreach ($NonConformitysSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityAction->quality_non_conformitie_id == $item->id  ) Selected @endif>{{ $item->code }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Problem description</label>
                          <textarea class="form-control" rows="3" name="pb_descp"  placeholder="Enter ..." required>{{ $QualityAction->pb_descp }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>Cause description</label>
                          <textarea class="form-control" rows="3" name="cause"  placeholder="Enter ..." required>{{ $QualityAction->cause }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>Action description</label>
                          <textarea class="form-control" rows="3" name="action"  placeholder="Enter ..." required>{{ $QualityAction->action }}</textarea>
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="8" text="No data available in table"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>Color</th>
                <th>NC</th>
                <th>Created</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="col-5">
          {{ $QualityActions->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">New action</h3>
        </div>
        <form method="POST" action="{{ route('quality.action.create')}}" >
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-4">
                <label for="code">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="External ID" value="ACT-{{ $LastAction->id ?? '0' }}">
                </div>
              </div>
              <div class="col-4">
                <label for="label">Label</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                </div>
              </div>
              <div class="col-4">
                <label for="statu">Statu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="statu" id="statu">
                    <option value="1">In Progess</option>
                    <option value="2">Waiting Customer Data</option>
                    <option value="3">Validate</option>
                    <option value="4">Canceled</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-3">
                <label for="type">Type</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="type" id="type">
                    <option value="1">Internal</option>
                    <option value="2">External</option>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <label for="user_id">User</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                  </div>
                  <select class="form-control" name="user_id" id="user_id">
                    @foreach ($userSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-3">
                <label for="color">Color</label>
                <input type="color" class="form-control"  name="color" id="color" >
              </div>
              <div class="col-3">
                <label for="quality_non_conformitie_id">Non conformitie link</label>
                <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                  <option value="null">None</option>
                  @foreach ($NonConformitysSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->code }}</option>
                  @endforeach
                </select>
              </div>
              <!-- /.row -->
            </div>
            <div class="row">
              <div class="col-3">
                <label>Problem description</label>
                <textarea class="form-control" rows="3" name="pb_descp"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-3">
                <label>Cause description</label>
                <textarea class="form-control" rows="3" name="cause"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-3">
                <label>Action description</label>
                <textarea class="form-control" rows="3" name="action"  placeholder="Enter ..."></textarea>
              </div>
              
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
              <div class="form-group row">
                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
          </div>
        </form>
      <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Derogations">
      @include('include.alert-result')
      <div class="card-body">
        <div class="row">
          <table  class="table  table-striped">
            <thead>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>NC</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($QualityDerogations as $QualityDerogation)
              <tr>
                <td>{{ $QualityDerogation->code }}</td>
                <td>{{ $QualityDerogation->label }}</td>
                <td>{{ $QualityDerogation->UserManagement['name'] }}</td>
                <td>
                  @if($QualityDerogation->type  == 1) <span class="badge badge-warning">Internal</span> @endif
                  @if($QualityDerogation->type  == 2) <span class="badge badge-danger">External</span> @endif
                </td>
                <td>
                  @if($QualityDerogation->statu  == 1) <span class="badge badge-info">In Progess</span> @endif
                  @if($QualityDerogation->statu  == 2) <span class="badge badge-warning">Waiting Customer Data</span> @endif
                  @if($QualityDerogation->statu  == 3) <span class="badge badge-success">Validate</span> @endif
                  @if($QualityDerogation->statu  == 4) <span class="badge badge-danger">Canceled</span> @endif
                </td>
                <td>
                  @if($QualityDerogation->quality_non_conformitie_id) 
                    {{ $QualityDerogation->QualityNonConformity->code }} 
                  @else
                    No NC link
                  @endif
                </td>
                <td>{{ $QualityDerogation->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityDerogationView{{ $QualityDerogation->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityDerogation->id }} -->
                  <x-adminlte-modal id="QualityDerogationView{{ $QualityDerogation->id }}" title="Info {{ $QualityDerogation->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                      <strong>Problem description : </strong> 
                      {{ $QualityDerogation->pb_descp }}
                    </div>
                    <div class="row">
                      <strong >Proposal description :</strong> 
                      {{ $QualityDerogation->proposal }}
                    </div>
                    <div class="row">
                      <strong >Customer reply description :</strong> 
                      {{ $QualityDerogation->reply }}
                    </div>
                    <div class="row">
                      <strong >Decision description :</strong> 
                      {{ $QualityDerogation->decision }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityDerogation{{ $QualityDerogation->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityDerogation->id }} -->
                  <x-adminlte-modal id="QualityDerogation{{ $QualityDerogation->id }}" title="Update {{ $QualityDerogation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.derogation.update', ['id' => $QualityDerogation->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">Label</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityDerogation->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">Statu</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityDerogation->statu == 1  ) Selected @endif>In Progess</option>
                              <option value="2"  @if($QualityDerogation->statu == 2  ) Selected @endif>Waiting Customer Data</option>
                              <option value="3"  @if($QualityDerogation->statu == 3  ) Selected @endif>Validate</option>
                              <option value="4"  @if($QualityDerogation->statu == 4  ) Selected @endif>Canceled</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="type">Type</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="type" id="type">
                              <option value="1" @if($QualityDerogation->type == 1  ) Selected @endif>Internal</option>
                              <option value="2" @if($QualityDerogation->type == 2  ) Selected @endif>External</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="user_id">User</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id">
                              @foreach ($userSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityDerogation->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="quality_non_conformitie_id">Non conformitie link</label>
                          <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                            <option value="null">None</option>
                            @foreach ($NonConformitysSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityDerogation->quality_non_conformitie_id == $item->id  ) Selected @endif>{{ $item->code }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Problem description</label>
                          <textarea class="form-control" rows="3" name="pb_descp"  placeholder="Enter ..." required>{{ $QualityDerogation->pb_descp }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>Proposal description</label>
                          <textarea class="form-control" rows="3" name="proposal"  placeholder="Enter ..." required>{{ $QualityDerogation->proposal }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>Customer reply description</label>
                          <textarea class="form-control" rows="3" name="reply"  placeholder="Enter ..." required>{{ $QualityDerogation->reply }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>Decision description</label>
                          <textarea class="form-control" rows="3" name="decision"  placeholder="Enter ..." required>{{ $QualityDerogation->decision }}</textarea>
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="7" text="No data available in table"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>NC</th>
                <th>Created</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="col-5">
          {{ $QualityDerogations->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">New derogation</h3>
        </div>
        <form method="POST" action="{{ route('quality.derogation.create')}}" >  
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-2">
                <label for="code">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="External ID" value="DER-{{ $LastDerogation->id ?? '0' }}  ">
                </div>
              </div>
              <div class="col-2">
                <label for="label">Label</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                </div>
              </div>
              <div class="col-2">
                <label for="type">Type</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="type" id="type">
                    <option value="1">Internal</option>
                    <option value="2">External</option>
                  </select>
                </div>
              </div>
              <div class="col-2">
                <label for="statu">Statu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="statu" id="statu">
                    <option value="1">In Progess</option>
                    <option value="2">Waiting Customer Data</option>
                    <option value="3">Validate</option>
                    <option value="4">Canceled</option>
                  </select>
                </div>
              </div>
              <div class="col-2">
                <label for="user_id">User</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                  </div>
                  <select class="form-control" name="user_id" id="user_id">
                    @foreach ($userSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-2">
                <label for="quality_non_conformitie_id">Non conformitie link</label>
                <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                  <option value="null">None</option>
                  @foreach ($NonConformitysSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->code }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-2">
                <label>Problem description</label>
                <textarea class="form-control" rows="3" name="pb_descp"  placeholder="Enter ..." required></textarea>
              </div>
              <div class="col-2">
                <label>Proposal description</label>
                <textarea class="form-control" rows="3" name="proposal"  placeholder="Enter ..." required></textarea>
              </div>
              <div class="col-2">
                <label>Customer reply description</label>
                <textarea class="form-control" rows="3" name="reply"  placeholder="Enter ..." required></textarea>
              </div>
              <div class="col-2">
                <label>Decision description</label>
                <textarea class="form-control" rows="3" name="decision"  placeholder="Enter ..." required></textarea>
              </div>
              
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
            <div class="form-group row">
              <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          <!-- /.row -->
          </div>
        </form>
        <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="NonConformities">
      <div class="card-body">
        <x-InfocalloutComponent note="Non-conformity sheets are documents summing up data related to a quality issue that arose within your company, with a customer, or with a supplier and the extra costs it generated.."  />
        @include('include.alert-result')
        <div class="row">
          <table  class="table  table-striped">
            <thead>
            <tr>
              <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>Companie</th>
                <th>Service</th>
                <th>Failure</th>
                <th>Cause</th>
                <th>Correction</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($QualityNonConformitys as $QualityNonConformity)
              <tr>
                <td>{{ $QualityNonConformity->code }}</td>
                <td>{{ $QualityNonConformity->label }}</td>
                <td>{{ $QualityNonConformity->UserManagement['name'] }}</td>
                <td>
                  @if($QualityNonConformity->type  == 1) <span class="badge badge-warning">Internal</span> @endif
                  @if($QualityNonConformity->type  == 2) <span class="badge badge-danger">External</span> @endif
                </td>
                <td>
                  @if($QualityNonConformity->statu  == 1) <span class="badge badge-info">In Progess</span> @endif
                  @if($QualityNonConformity->statu  == 2) <span class="badge badge-warning">Waiting Customer Data</span> @endif
                  @if($QualityNonConformity->statu  == 3) <span class="badge badge-success">Validate</span> @endif
                  @if($QualityNonConformity->statu  == 4) <span class="badge badge-danger">Canceled</span> @endif
                </td>
                <td><x-CompanieButton id="{{ $QualityNonConformity->companie_id }}" label="{{ $QualityNonConformity->companie->label }}"  /></td>
                <td>{{ $QualityNonConformity->service->label }}</td>
                <td>{{ $QualityNonConformity->Failure->label }}</td>
                <td>{{ $QualityNonConformity->Cause->label }}</td>
                <td>{{ $QualityNonConformity->Correction->label }}</td>
                
                <td>{{ $QualityNonConformity->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityNonConformityView{{ $QualityNonConformity->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityNonConformity->id }} -->
                  <x-adminlte-modal id="QualityNonConformityView{{ $QualityNonConformity->id }}" title="Info {{ $QualityNonConformity->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                        <strong>Faillure comment : </strong> 
                        {{ $QualityNonConformity->failure_comment }}
                    </div>
                    <div class="row">
                      <strong >Cause comment :</strong> 
                      {{ $QualityNonConformity->causes_comment }}
                    </div>
                    <div class="row">
                        <strong >Correction comment :</strong> 
                        {{ $QualityNonConformity->correction_comment }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityNonConformity{{ $QualityNonConformity->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityNonConformity->id }} -->
                  <x-adminlte-modal id="QualityNonConformity{{ $QualityNonConformity->id }}" title="Update {{ $QualityNonConformity->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.nonConformitie.update', ['id' => $QualityNonConformity->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">Label</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityNonConformity->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">Statu</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityNonConformity->statu == 1  ) Selected @endif>In Progess</option>
                              <option value="2"  @if($QualityNonConformity->statu == 2  ) Selected @endif>Waiting Customer Data</option>
                              <option value="3"  @if($QualityNonConformity->statu == 3  ) Selected @endif>Validate</option>
                              <option value="4"  @if($QualityNonConformity->statu == 4  ) Selected @endif>Canceled</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="type">Type</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="type" id="type">
                              <option value="1" @if($QualityNonConformity->type == 1  ) Selected @endif>Internal</option>
                              <option value="2" @if($QualityNonConformity->type == 2  ) Selected @endif>External</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="user_id">User</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id">
                              @foreach ($userSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityNonConformity->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="service_id">Service</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="service_id" id="service_id">
                              @foreach ($ServicesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="companie_id">Companie concern</label>
                          <select class="form-control" name="companie_id" id="companie_id">
                            @foreach ($CompaniesSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityNonConformity->companie_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="row">
                          <div class="col-4">
                            <label for="failure_id">Failure type</label>
                            <select class="form-control" name="failure_id" id="failure_id">
                              @foreach ($FailuresSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->failure_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-4">
                            <label for="causes_id">Cause type</label>
                            <select class="form-control" name="causes_id" id="causes_id">
                              @foreach ($CausesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->causes_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-4">
                            <label for="correction_id">Correction type</label>
                            <select class="form-control" name="correction_id" id="correction_id">
                              @foreach ($CorrectionsSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityNonConformity->correction_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-4">
                            <label>Comment for failure</label>
                            <textarea class="form-control" rows="3" name="failure_comment"  placeholder="Enter ..." required>{{ $QualityNonConformity->failure_comment}}</textarea>
                          </div>
                          <div class="col-4">
                              <label>Comment for cause</label>
                              <textarea class="form-control" rows="3" name="causes_comment"  placeholder="Enter ..." required>{{ $QualityNonConformity->causes_comment}}</textarea>
                          </div>
                          <div class="col-4">
                            <label>Comment for correction</label>
                            <textarea class="form-control" rows="3" name="correction_comment"  placeholder="Enter ..." required>{{ $QualityNonConformity->correction_comment}}</textarea>
                          </div>
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="8" text="No data available in table"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>Companie</th>
                <th>Service</th>
                <th>Failure</th>
                <th>Cause</th>
                <th>Correction</th>
                <th>Created</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="col-5">
          {{ $QualityNonConformitys->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">New non conformitie</h3>
        </div>
        <form method="POST" action="{{ route('quality.nonConformitie.create')}}" > 
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-4">
                <label for="code">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="External ID" value="NC-{{ $LastNonConformity->id ?? '0' }}  ">
                </div>    
              </div>
              <div class="col-4">
                <label for="label">Label</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                </div>
              </div>
              <div class="col-4">
                <label for="type">Type</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="type" id="type">
                    <option value="1">Internal</option>
                    <option value="2">External</option>
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="statu">Statu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="statu" id="statu">
                    <option value="1">In Progess</option>
                    <option value="2">Waiting Customer Data</option>
                    <option value="3">Validate</option>
                    <option value="4">Canceled</option>
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="user_id">User</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                  </div>
                  <select class="form-control" name="user_id" id="user_id">
                    @foreach ($userSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="service_id">Service</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                  </div>
                  <select class="form-control" name="service_id" id="service_id">
                    @foreach ($ServicesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-12">
                <label for="companie_id">Companie concern</label>
                <select class="form-control" name="companie_id" id="companie_id">
                  @foreach ($CompaniesSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <div class="row">
                  <div class="col-4">
                    <label for="failure_id">Failure type</label>
                    <select class="form-control" name="failure_id" id="failure_id">
                      @foreach ($FailuresSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-4">
                    <label for="causes_id">Cause type</label>
                    <select class="form-control" name="causes_id" id="causes_id">
                      @foreach ($CausesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-4">
                    <label for="correction_id">Correction type</label>
                    <select class="form-control" name="correction_id" id="correction_id">
                      @foreach ($CorrectionsSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="row">
                  <div class="col-4">
                    <label>Comment for failure</label>
                    <textarea class="form-control" rows="3" name="failure_comment"  placeholder="Enter ..." required></textarea>
                  </div>
                  <div class="col-4">
                    <label>Comment for cause</label>
                    <textarea class="form-control" rows="3" name="causes_comment"  placeholder="Enter ..." required></textarea>
                  </div>
                  <div class="col-4">
                    <label>Comment for correction</label>
                    <textarea class="form-control" rows="3" name="correction_comment"  placeholder="Enter ..." required></textarea>
                  </div>
                </div>
              </div>
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
            <div class="form-group row">
              <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          <!-- /.row -->
          </div>
        </form>
        <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="MeasuringDevices">
      <x-InfocalloutComponent note="The measuring devices used to measure quality."  />
      @include('include.alert-result')
      <div class="row">
        <div class="col-md-7 card-primary">
          <div class="card-header">
              <h3 class="card-title">Measuring devices list</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>Ressource</th>
                <th>User</th>
                <th>Serial number</th>
                <th>Created</th>
                <th></th>
              </tr>
              </thead>
              <tbody>
                @forelse ($QualityControlDevices as $QualityControlDevice)
                <tr>
                  <td>{{ $QualityControlDevice->code }}</td>
                  <td>{{ $QualityControlDevice->label }}</td>
                  <td>{{ $QualityControlDevice->service['label'] }}</td>
                  <td>{{ $QualityControlDevice->UserManagement['name'] }}</td>
                  <td>{{ $QualityControlDevice->serial_number }}</td>
                  <td>{{ $QualityControlDevice->GetPrettyCreatedAttribute() }}</td>
                  <td>
                    <!-- Button Modal -->
                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityControlDevice{{ $QualityControlDevice->id }}">
                      <i class="fa fa-lg fa-fw  fa-edit"></i>
                    </button>
                    <!-- Modal {{ $QualityControlDevice->id }} -->
                    <x-adminlte-modal id="QualityControlDevice{{ $QualityControlDevice->id }}" title="Update {{ $QualityControlDevice->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                      <form method="POST" action="{{ route('quality.device.update', ['id' => $QualityControlDevice->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                          <div class="col-6">
                            <label for="label">Label</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control"  name="label" id="label" placeholder="Label" value="{{ $QualityControlDevice->label }}">
                            </div>
                          </div>
                          <div class="col-6">
                            <label for="service_id">Service</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                              </div>
                              <select class="form-control" name="service_id" id="service_id">
                                @foreach ($ServicesSelect as $item)
                                <option value="{{ $item->id }}"  @if($QualityControlDevice->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-6">
                            <label for="user_id">User</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                              </div>
                              <select class="form-control" name="user_id" id="user_id">
                                @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}" @if($QualityControlDevice->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="col-6">
                            <label for="label">Serial number</label>
                            <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="Serial number" value="{{ $QualityControlDevice->serial_number }}">
                          </div>
                        <!-- /.row -->
                        </div>
                        <div class="form-group">
                          <div class="col-md-12">
                            <label for="picture">Logo file</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-image"></i></span>
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="picture" id="picture">
                                    <label class="custom-file-label" for="picture">Choose file</label>
                                </div>
                            </div>
                          </div>
                        <!-- /.form-group -->
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                </tr>
                @empty
                  <x-EmptyDataLine col="6" text="No data available in table"  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>External ID</th>
                  <th>Label</th>
                  <th>Ressource</th>
                  <th>User</th>
                  <th>Serial number</th>
                  <th>Created</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          <!-- /.card-body -->
          </div>
          <div class="row">
            <div class="col-5">
            {{ $QualityControlDevices->links() }}
            </div>
          <!-- /.row -->
          </div>
        <!-- /.col-md-7 card-primary -->
        </div>
        <div class="col-md-5 card-secondary">
          <div class="card-header">
            <h3 class="card-title">New measuring devices</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('quality.device.create')}}" enctype="multipart/form-data">
              @csrf
              <div class="row">
                <div class="col-4">
                  <label for="code">External ID</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="code" id="code" placeholder="External ID">
                  </div>
                </div>
                <div class="col-4">
                  <label for="label">Label</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                  </div>
                </div>
                <div class="col-4">
                  <label for="service_id">Service</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-list"></i></span>
                    </div>
                    <select class="form-control" name="service_id" id="service_id">
                      @foreach ($ServicesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-4">
                  <label for="user_id">User</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <select class="form-control" name="user_id" id="user_id">
                      @foreach ($userSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-4">
                  <label for="label">Serial number</label>
                  <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="Serial number">
                </div>
              <!-- /.row -->
              </div>
              <div class="form-group">
                <div class="col-md-8">
                  <label for="picture">Logo file</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                  <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="far fa-image"></i></span>
                      </div>
                      <div class="custom-file">
                          <input type="file" class="custom-file-input" name="picture" id="picture">
                          <label class="custom-file-label" for="picture">Choose file</label>
                      </div>
                  </div>
                </div>
              <!-- /.form-group -->
              </div>
              <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
            </form>
          </div>
          <!-- /.card body -->
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Settings">
      <x-InfocalloutComponent note="To avoid any waste of time, you have the possibility to predefine the defects, causes and/or correction measures that you will use the most. You will then simply have to make the relevant selection when creating a non-conformity sheet or the maintenance interventions of a resource."  />
      @include('include.alert-result')
      <div class="card card-primary collapsed-card">
        <div class="card-header">
          <h3 class="card-title">Failing list</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="card-body" style="display: none;">
          <div class="row">
            <div class="col-md-6 card-secondary">
              <div class="card-header">
                  <h3 class="card-title">Failure type list</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>External ID</th>
                      <th>Label</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($QualityFailures as $QualityFailure)
                    <tr>
                      <td>{{ $QualityFailure->code }}</td>
                      <td>{{ $QualityFailure->label }}</td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityFailure{{ $QualityFailure->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $QualityFailure->id }} -->
                        <x-adminlte-modal id="QualityFailure{{ $QualityFailure->id }}" title="Update {{ $QualityFailure->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('quality.failure.update', ['id' => $QualityFailure->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">Label</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityFailure->label }}">
                                </div>
                              </div>
                            </div>
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="3" text=" No data available in table"  />
                    @endforelse
                  </tbody>
                </table>
              </div>
            <!-- /.card secondary -->
            </div>
            <div class="col-md-6 card-secondary">
                <div class="card-header">
                  <h3 class="card-title">New Failure</h3>
                </div>
                <form  method="POST" action="{{ route('quality.failure.create') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="code">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="External ID">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">Label</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
            <!-- /.card secondary -->
            </div>
          <!-- /.row -->
          </div>
        <!-- /.card body -->
        </div>
      <!-- /.card primary -->
      </div>
      <div class="card card-primary collapsed-card">
          <div class="card-header">
            <h3 class="card-title">Causes list</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-plus"></i>
              </button>
              <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="card-body" style="display: none;">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Cause type list</h3>
                </div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Label</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($QualityCauses as $QualityCause)
                      <tr>
                        <td>{{ $QualityCause->code }}</td>
                        <td>{{ $QualityCause->label }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button Modal -->
                          <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityCause{{ $QualityCause->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $QualityCause->id }} -->
                          <x-adminlte-modal id="QualityCause{{ $QualityCause->id }}" title="Update {{ $QualityCause->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('quality.cause.update', ['id' => $QualityCause->id]) }}" enctype="multipart/form-data">
                              @csrf
                              <div class="card-body">
                                <div class="form-group">
                                  <label for="label">Label</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityCause->label }}">
                                  </div>
                                </div>
                              </div>
                              <div class="card-footer">
                                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                              </div>
                            </form>
                          </x-adminlte-modal>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="3" text="No data available in table"  />
                      @endforelse
                    </tbody>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>
              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Cause</h3>
                  </div>
                  <form  method="POST" action="{{ route('quality.cause.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="code">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="code" id="code" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="label">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                      </div>
                    </div>
                    <div class="card-footer">
                      <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                  </form>
              <!-- /.card secondary -->
              </div>
            <!-- /.row -->
            </div>
          <!-- /.card body -->
          </div>
          <!-- /.card primary -->
      </div>
      <div class="card card-primary collapsed-card">
        <div class="card-header">
          <h3 class="card-title">Correction list</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="card-body" style="display: none;">
          <div class="row">
            <div class="col-md-6 card-secondary">
              <div class="card-header">
                  <h3 class="card-title">Correction type list</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>External ID</th>
                      <th>Label</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($QualityCorrections as $QualityCorrection)
                    <tr>
                      <td>{{ $QualityCorrection->code }}</td>
                      <td>{{ $QualityCorrection->label }}</td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityCorrection{{ $QualityCorrection->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $QualityCorrection->id }} -->
                        <x-adminlte-modal id="QualityCorrection{{ $QualityCorrection->id }}" title="Update {{ $QualityCorrection->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('quality.correction.update', ['id' => $QualityCorrection->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">Label</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $QualityCorrection->label }}">
                                </div>
                              </div>
                            </div>
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="3" text="No data available in table"  />
                    @endforelse
                  </tbody>
                </table>
              </div>
            <!-- /.card secondary -->
            </div>
            <div class="col-md-6 card-secondary">
                <div class="card-header">
                  <h3 class="card-title">New Correction</h3>
                </div>
                <form  method="POST" action="{{ route('quality.correction.create') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="code">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="External ID">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">Label</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
            <!-- /.card secondary -->
            </div>
          <!-- /.row -->
          </div>
        <!-- /.card body -->
        </div>
      <!-- /.card primary -->
      </div>
    <!-- /.tab-pane -->
    </div>
  <!-- /.tab-content -->
  </div>
<!-- /.card -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop