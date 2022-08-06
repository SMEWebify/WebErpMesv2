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
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Actions">
        <div class="card-body">
          <x-InfocalloutComponent note="Actions are measures taken to prevent a problem from occurring (preventive actions), to solve an existing problem (corrective actions), or to improve a process or product (improvement requests)."  />
          @include('include.alert-result')
          <div class="row">
            <table  class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>External ID</th>
                  <th>Label</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Statu</th>
                  <th>Color</th>
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
                    @if($QualityAction->type  == 1) Internal @endif
                    @if($QualityAction->type  == 2) External @endif
                  </td>
                  <td>
                    @if($QualityAction->type  == 1) In Progess @endif
                    @if($QualityAction->type  == 2) Waiting Customer Data @endif
                    @if($QualityAction->type  == 2) Validate @endif
                    @if($QualityAction->type  == 2) Canceled @endif
                  </td>
                  <td><input type="color" class="form-control"  name="color" id="color" value="{{ $QualityAction->color }}"></td>
                  <td>{{ $QualityAction->GetPrettyCreatedAttribute() }}</td>
                  <td class=" py-0 align-middle">
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                    </div>
                  </td>
                </tr>
                @empty
                <x-EmptyDataLine col="8" text="No line found ..."  />
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
        <div class="card card-body">
          <form method="POST" action="{{ route('quality.action.create')}}" >
            @csrf
            <div class="form-group row">
              <div class="col-2">
                <label for="code">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="External ID" value="ACT-{{ $LastAction->id ?? '0' }}">
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
              <div class="col-2">
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
            <div class="row">
              <div class="form-group row">
                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
              </div>
            <!-- /.row -->
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
            <table  class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>External ID</th>
                  <th>Label</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Statu</th>
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
                    @if($QualityDerogation->type  == 1) Internal @endif
                    @if($QualityDerogation->type  == 2) External @endif
                  </td>
                  <td>
                    @if($QualityDerogation->type  == 1) In Progess @endif
                    @if($QualityDerogation->type  == 2) Waiting Customer Data @endif
                    @if($QualityDerogation->type  == 2) Validate @endif
                    @if($QualityDerogation->type  == 2) Canceled @endif
                  </td><td>{{ $QualityDerogation->GetPrettyCreatedAttribute() }}</td>
                  <td class=" py-0 align-middle">
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                    </div>
                  </td>
                </tr>
                @empty
                <x-EmptyDataLine col="7" text="No line found ..."  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>External ID</th>
                  <th>Label</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Statu</th>
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
        <div class="card card-body">
          <form method="POST" action="{{ route('quality.derogation.create')}}" >
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
                <textarea class="form-control" rows="3" name="pb_descp"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-2">
                <label>Proposal description</label>
                <textarea class="form-control" rows="3" name="proposal"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-2">
                <label>Customer reply description</label>
                <textarea class="form-control" rows="3" name="reply"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-2">
                <label>Decision description</label>
                <textarea class="form-control" rows="3" name="decision"  placeholder="Enter ..."></textarea>
              </div>
              
            <!-- /.row -->
            </div>
            <div class="row">
              <div class="form-group row">
                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
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
            <table  class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>External ID</th>
                  <th>Label</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Statu</th>
                  <th>Color</th>
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
                    @if($QualityNonConformity->type  == 1) Internal @endif
                    @if($QualityNonConformity->type  == 2) External @endif
                  </td>
                  <td>
                    @if($QualityNonConformity->type  == 1) In Progess @endif
                    @if($QualityNonConformity->type  == 2) Waiting Customer Data @endif
                    @if($QualityNonConformity->type  == 2) Validate @endif
                    @if($QualityNonConformity->type  == 2) Canceled @endif
                  </td>
                  <td><input type="color" class="form-control"  name="color" id="color" value="{{ $QualityNonConformity->color }}"></td>
                  <td>{{ $QualityNonConformity->GetPrettyCreatedAttribute() }}</td>
                  <td class=" py-0 align-middle">
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                    </div>
                  </td>
                </tr>
                @empty
                <x-EmptyDataLine col="8" text="No line found ..."  />
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
        <div class="card card-body">
          <form method="POST" action="{{ route('quality.nonConformitie.create')}}" >
            @csrf
            <div class="form-group row">
              <div class="col-2">
                <label for="code">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="External ID" value="NC-{{ $LastNonConformity->id ?? '0' }}  ">
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
              <div class="col-2">
                <label for="failure_id">Failure type</label>
                <select class="form-control" name="failure_id" id="failure_id">
                  @foreach ($FailuresSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-2">
                <label for="causes_id">Cause type</label>
                <select class="form-control" name="causes_id" id="causes_id">
                  @foreach ($CausesSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-2">
                <label for="correction_id">Correction type</label>
                <select class="form-control" name="correction_id" id="correction_id">
                  @foreach ($CorrectionsSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-2">
                <label for="companie_id">Companie concern</label>
                <select class="form-control" name="companie_id" id="companie_id">
                  @foreach ($CompaniesSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-3">
                <label for="color">Color</label>
                <input type="color" class="form-control"  name="color" id="color" >
              </div>
              <div class="col-2">
                <label>Comment for failure</label>
                <textarea class="form-control" rows="3" name="failure_comment"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-2">
                <label>Comment for cause</label>
                <textarea class="form-control" rows="3" name="causes_comment"  placeholder="Enter ..."></textarea>
              </div>
              <div class="col-2">
                <label>Comment for correction</label>
                <textarea class="form-control" rows="3" name="correction_comment"  placeholder="Enter ..."></textarea>
              </div>
            <!-- /.row -->
            </div>
            <div class="row">
              <div class="form-group row">
                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
              </div>
            <!-- /.row -->
            </div>
          </form>
          <!-- /.card-body -->
        </div>
      </div>
      <!-- /.tab-pane -->
      <div class="tab-pane" id="MeasuringDevices">
          <div class="card card-primary">
            <div class="card-body">
              <x-InfocalloutComponent note="The measuring devices used to measure quality."  />
              @include('include.alert-result')
              <div class="row">
                <div class="col-md-7 card-primary">
                  <div class="card-header">
                      <h3 class="card-title">Measuring devices list</h3>
                  </div>
                  <div class="card-body ">
                    <table class="table">
                      <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Label</th>
                        <th>Ressource</th>
                        <th>User</th>
                        <th>Serial number</th>
                        <th>Created</th>
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
                        </tr>
                        @empty
                          <x-EmptyDataLine col="6" text="No line found ..."  />
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
                      <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                    </div>
                  </form>
                <!-- /.card body -->
                </div>
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
                <div class="card-body ">
                  <table class="table">
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
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                        <x-EmptyDataLine col="3" text=" No lines found ..."  />
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
                      <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
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
                  <div class="card-body ">
                    <table class="table">
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
                          <td class="text-right py-0 align-middle">
                            <div class="btn-group btn-group-sm">
                              <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                            </div>
                          </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="3" text="No line found ..."  />
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
                        <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
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
                <div class="card-body ">
                  <table class="table">
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
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                        <x-EmptyDataLine col="3" text="No line found ..."  />
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
                      <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
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
  <!-- /.card-body -->
  </div>
<!-- /.card -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop