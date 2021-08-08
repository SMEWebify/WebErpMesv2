@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Quality settings</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @if(session('success'))
  <div class="alert alert-success">
      {{ session('success')}}
  </div>
  @endif

  @if($errors->count())
    <div class="alert alert-danger">
      <ul>
      @foreach ( $errors->all() as $message)
       <li> {{ $message }}</li>
      @endforeach
      </ul>
    </div>
  @endif

  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Action" data-toggle="tab">Action</a></li>
      <li class="nav-item"><a class="nav-link" href="#Derogations" data-toggle="tab">Derogations</a></li>
      <li class="nav-item"><a class="nav-link" href="#NonConformities" data-toggle="tab">Non conformities</a></li>
      <li class="nav-item"><a class="nav-link" href="#MeasuringDevices" data-toggle="tab">Measuring devices</a></li>
      <li class="nav-item"><a class="nav-link" href="#Settings" data-toggle="tab">Settings</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">

      <div class="tab-pane" id="Action">
        <form class="form-horizontal">
          <div class="form-group row">
            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputName" placeholder="Name">
            </div>
          </div>
          <div class="form-group row">
            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-sm-2 col-sm-10">
              <button type="submit" class="btn btn-danger">Submit</button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="Derogations">
        <form class="form-horizontal">
          <div class="form-group row">
            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputName" placeholder="Name">
            </div>
          </div>
          <div class="form-group row">
            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-sm-2 col-sm-10">
              <button type="submit" class="btn btn-danger">Submit</button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="NonConformities">
        <div class="card-body">
          <div class="row">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>External ID</th>
                <th>Label</th>
                <th>User</th>
                <th>Type</th>
                <th>Statu</th>
                <th>Created</th>
              </tr>
              </thead>
              <tbody>
                @foreach ($QualityActions as $QualityAction)
                <tr>
                  <td>{{ $QualityAction->CODE }}</td>
                  <td>{{ $QualityAction->LABEL }}</td>
                  <td>{{ $QualityAction->user_id }}</td>
                  <td>{{ $QualityAction->TYPE }}</td>
                  <td>{{ $QualityAction->ETAT }}</td>
                  <td>{{ $QualityAction->GetPrettyCreatedAttribute() }}</td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th>External ID</th>
                  <th>Label</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Statu</th>
                  <th>Created</th>
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
        <div class="card-body">
          <form method="POST" action="{{ route('quality.action.create')}}" >
            <div class="form-group row">
              <div class="col-2">
                <label for="CODE">External ID</label>
                <input type="text" class="form-control"  name="CODE" id="CODE" placeholder="External ID">
              </div>
              <div class="col-2">
                <label for="LABEL">Label</label>
                <input type="text" class="form-control"  name="LABEL" id="LABEL" placeholder="Label">
              </div>
              <div class="col-2">
                <label for="TYPE">Type</label>
                <select class="form-control" name="TYPE" id="TYPE">
                  <option value="Preventive">Preventive</option>
                  <option value="Corrective">Corrective</option>
                  <option value="Improvement">Improvement</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="col-2">
                <label for="STATU">Statu</label>
                <select class="form-control" name="STATU" id="STATU">
                  <option value="In Progess">In Progess</option>
                  <option value="Waiting Customer Data">Waiting Customer Data</option>
                  <option value="Validate">Validate</option>
                  <option value="Canceled">Canceled</option>
                </select>
              </div>
              <div class="col-2">
                <label for="COLOR">Color</label>
                <input type="color" class="form-control"  name="COLOR" id="COLOR" >
              </div>
            <!-- /.row -->
            </div>
            <div class="row">
              <div class="form-group row">
                <div class="offset-sm-2 col-sm-10">
                  <button type="submit" class="btn btn-danger">Submit</button>
                </div>
              </div>
            <!-- /.row -->
            </div>
          </form>
          <!-- /.card-body -->
        </div>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="MeasuringDevices">
        <div class="card-body">
          <div class="row">
            <table id="example1" class="table table-bordered table-striped">
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
                @foreach ($QualityControlDevices as $QualityControlDevice)
                <tr>
                  <td>{{ $QualityControlDevice->CODE }}</td>
                  <td>{{ $QualityControlDevice->LABEL }}</td>
                  <td>{{ $QualityControlDevice->RESSOURCE_ID }}</td>
                  <td>{{ $QualityControlDevice->USER_ID }}</td>
                  <td>{{ $QualityControlDevice->SERIAL_NUMBER }}</td>
                  <td>{{ $QualityControlDevice->GetPrettyCreatedAttribute() }}</td>
                </tr>
                @endforeach
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
          <!-- /.row -->
          </div>
          
          <div class="row">
            <div class="col-5">
            {{ $QualityControlDevices->links() }}
            </div>
          <!-- /.row -->
          </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card-body">
        <form method="POST" action="{{ route('quality.device.create')}}" enctype="multipart/form-data">
          <div class="row">
            <div class="col-2">
              <label for="CODE">External ID</label>
              <input type="text" class="form-control"  name="CODE" id="CODE" placeholder="External ID">
            </div>
            <div class="col-2">
              <label for="LABEL">Label</label>
              <input type="text" class="form-control"  name="LABEL" id="LABEL" placeholder="Label">
            </div>
            <div class="col-2">
                <label for="user_id">Ressource</label>
                <select class="form-control" name="user_id" id="user_id">
                </select>
            </div>
            <div class="col-2">
              <label for="exampleInputFile">User</label>
              <select class="form-control" name="user_id" id="user_id">
                @foreach ($userSelect as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-2">
              <label for="LABEL">Serial number</label>
              <input type="text" class="form-control"  name="SERIAL_NUMBER" id="SERIAL_NUMBER" placeholder="Serial number">
            </div>
          <!-- /.row -->
          </div>
          <div class="form-group">
            <div class="col-md-6">
              <label for="exampleInputFile">Logo file</label>
              <div class="input-group">
                <div class="custom-file">
                  <input type="file" class="custom-file-input" id="LOGO">
                  <label class="custom-file-label" for="LOGO">Choose file</label>
                </div>
                <div class="input-group-append">
                  <span class="input-group-text">Upload</span>
                </div>
              </div>
            </div>
          <!-- /.form-group -->
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-danger">Submit</button>
          </div>
        </form>
      <!-- /.card-body -->
       </div>
      <!-- /.tab-pane -->
       </div>
      <div class="tab-pane" id="Settings">

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
                    <h3 class="card-title">Failling type list</h3>
                </div>
                <div class="card-body p-0">
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
                        <td>{{ $QualityFailure->CODE }}
                        <td>{{ $QualityFailure->LABEL }}</td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Failling</h3>
                  </div>
                  <form  method="POST" action="{{ route('quality.failling.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                    </div>
                    <div class="card-footer">
                      <div class="offset-sm-2 col-sm-10">
                        <button type="submit" class="btn btn-danger">Submit New</button>
                      </div>
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
                  <div class="card-body p-0">
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
                          <td>{{ $QualityCause->CODE }}
                          <td>{{ $QualityCause->LABEL }}</td>
                          <td class="text-right py-0 align-middle">
                            <div class="btn-group btn-group-sm">
                              <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                            </div>
                          </td>
                        </tr>
                        @empty
                        <tr>
                          <td>No Data</td>
                          <td></td> 
                          <td></td> 
                        </tr>
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
                        <label for="CODE">External ID</label>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                      <div class="form-group">
                        <label for="LABEL">Label</label>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                      <div class="card-footer">
                        <div class="offset-sm-2 col-sm-10">
                          <button type="submit" class="btn btn-danger">Submit New</button>
                        </div>
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
                    <div class="card-body p-0">
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
                            <td>{{ $QualityCorrection->CODE }}
                            <td>{{ $QualityCorrection->LABEL }}</td>
                            <td class="text-right py-0 align-middle">
                              <div class="btn-group btn-group-sm">
                                <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                              </div>
                            </td>
                          </tr>
                          @empty
                          <tr>
                            <td>No Data</td>
                            <td></td> 
                            <td></td> 
                          </tr>
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
                          <label for="CODE">External ID</label>
                          <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                        </div>
                        <div class="form-group">
                          <label for="LABEL">Label</label>
                          <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                        </div>
                        <div class="card-footer">
                          <div class="offset-sm-2 col-sm-10">
                            <button type="submit" class="btn btn-danger">Submit New</button>
                          </div>
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
   <link rel="stylesheet" href="/css/admin_custom.css">
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop