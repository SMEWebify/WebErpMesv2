@extends('adminlte::page')

@section('title', 'Methods')

@section('content_header')
    <h1>Methods</h1>
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
      <li class="nav-item"><a class="nav-link active" href="#Services" data-toggle="tab">Services</a></li>
      <li class="nav-item"><a class="nav-link" href="#Ressources" data-toggle="tab">Ressources</a></li>
      <li class="nav-item"><a class="nav-link" href="#Section" data-toggle="tab">Section</a></li>
      <li class="nav-item"><a class="nav-link" href="#Location" data-toggle="tab">Location in workshop</a></li>
      <li class="nav-item"><a class="nav-link" href="#Units" data-toggle="tab">Units</a></li>
      <li class="nav-item"><a class="nav-link" href="#Families" data-toggle="tab">Families</a></li>
      <li class="nav-item"><a class="nav-link" href="#Tools" data-toggle="tab">Tools</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">

      <div class="tab-pane active" id="Services">
        <div class="card card-primary">
          <div class="card-body">
            <x-infocalloutComponent note="In the Type scrolling list, indicate whether the service is productive (manufacturing operation) or actually involves the procurement of raw materials or supplies, is a service completed externally, i.e. by a subcontractor, or is a composed component. The service type is then used to filter data when creating a list of tasks, a bill of materials, etc. 
            For raw materials, a distinction is also made between bars, plates and blocks. The screen used to detail raw materials may look different, depending on the raw material type."  />
            <div class="row">
              <div class="col-md-8 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Service type list</h3>
                </div>
                <div class="card-body p-0">
                  <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                      <th>Picture</th>
                      <th>Sort</th>
                      <th>External ID</th>
                      <th>Desciption</th>
                      <th>Type</th>
                      <th>Hourly rate</th>
                      <th>Margin</th>
                      <th>Color</th>
                      <th></th>
                    </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsServices as $MethodsService)
                      <tr>
                      
                        <td> 
                          @if($MethodsService->PICTURE )
                          <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('storage/'.$MethodsService->PICTURE) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsService->ORDRE }}</td>
                        <td>{{ $MethodsService->CODE }}</td>
                        <td>{{ $MethodsService->LABEL }}</td>
                        <td>
                          @if($MethodsService->TYPE  == 1) Productive @endif
                          @if($MethodsService->TYPE  == 2)Raw material @endif
                          @if($MethodsService->TYPE  == 3)Raw material (Sheet) @endif
                          @if($MethodsService->TYPE  == 4)Raw material (Profil) @endif
                          @if($MethodsService->TYPE  == 5)Raw material (block) @endif
                          @if($MethodsService->TYPE  == 6)Supplies @endif
                          @if($MethodsService->TYPE  == 7)Sub-contracting @endif
                          @if($MethodsService->TYPE  == 8)Composed component @endif
                        </td>
                        <td>{{ $MethodsService->HOURLY_RATE }}</td>
                        <td>{{ $MethodsService->MARGIN }}</td>
                        <td><input type="color" class="form-control"  name="COLOR" id="COLOR" value="{{ $MethodsService->COLOR }}"></td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td></td>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td>
                        <td></td>
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th></th>
                        <th>Order</th>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Type</th>
                        <th>Hourly rate</th>
                        <th>Margin</th>
                        <th>Color</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                  <div class="row">
                    <div class="col-5">
                    {{ $MethodsServices->links() }}
                    </div>
                  <!-- /.row -->
                  </div>
                </div>
              <!-- /.card secondary -->
              </div>
  
              <div class="col-md-4 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Service</h3>
                  </div>
                  <form method="POST" action="{{ route('methods.service.create')}}" enctype="multipart/form-data">
                    @csrf
                      <div class="form-group">
                        <label for="CODE">External ID</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="CODE" id="CODE" placeholder="External ID">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="ORDRE">Sort order:</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                          </div>
                          <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="LABEL">Label</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                         <input type="text" class="form-control"  name="LABEL" id="LABEL" placeholder="Label">
                        </div>
                      </div>
                      <div class="form-group">
                          <label for="TYPE">Type</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="TYPE" id="TYPE">
                                <option value="1">Productive</option>
                                <option value="2">Raw material</option>
                                <option value="3">Raw material (Sheet)</option>
                                <option value="4">Raw material (Profil)</option>
                                <option value="5">Raw material (block)</option>
                                <option value="6">Purchase</option>
                                <option value="7">Sub-contracting</option>
                                <option value="8">Composed component</option>
                            </select>
                          </div>
                      </div>
                      <div class="form-group">
                        <label for="HOURLY_RATE">Hourly rate</label>
                        <input type="number" class="form-control" name="HOURLY_RATE" id="HOURLY_RATE" placeholder="110 €/H" step=".001">
                      </div>
                      <div class="form-group">
                        <label for="MARGIN">Margin :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                          </div>
                          <input type="number" class="form-control" name="MARGIN" id="MARGIN" placeholder="10%" step=".001">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="COLOR">Color</label>
                        <input type="color" class="form-control"  name="COLOR" id="COLOR" >
                      </div>
                      <div class="form-group">
                        <label for="PICTURE">Logo file</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-image"></i></span>
                          </div>
                          <div class="custom-file">
                            <input type="file" class="custom-file-input" name="PICTURE"  id="PICTURE">
                            <label class="custom-file-label" for="PICTURE">Choose file</label>
                          </div>
                          <div class="input-group-append">
                            <span class="input-group-text">Upload</span>
                          </div>
                      </div>
                      <div class="form-group">
                        <label for="compannie_id">Supplier</label>
                          <select class="form-control" name="compannie_id" id="compannie_id">
                            <option value="NULL">None</option>
                            @foreach ($CompaniesSelect as $item)
                            <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                            @endforeach
                          </select>
                      </div>
                    <!-- /.form-group -->
                    </div>
                    <div class="card-footer">
                      <button type="submit" class="btn btn-danger">Submit</button>
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


      <div class="tab-pane" id="Ressources">
        <div class="card card-primary">
          <div class="card-body">
            <x-infocalloutComponent note="Depending on your working method, resources may be employees or machines or a mixture of both. In any case, you must indicate for each of them the maximum number of working hours per day as well as the services that they can complete."  />
            <div class="row">
              <div class="col-md-8 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Ressources type list</h3>
                </div>
                <div class="card-body p-0">
                  <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                      <th>Picture</th>
                      <th>Sort</th>
                      <th>External ID</th>
                      <th>Desciption</th>
                      <th>Mask time</th>
                      <th>Capacity</th>
                      <th>Section</th>
                      <th>Color</th>
                      <th>Service</th>
                    </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsRessources as $MethodsRessource)
                      <tr>
                      
                        <td> 
                          @if($MethodsRessource->PICTURE )
                          <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('storage/'.$MethodsRessource->PICTURE) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsRessource->ORDRE }}</td>
                        <td>{{ $MethodsRessource->CODE }}</td>
                        <td>{{ $MethodsRessource->LABEL }}</td>
                        <td>{{ $MethodsRessource->MASK_TIME }}</td>
                        <td>{{ $MethodsRessource->CAPACITY }} h/w</td>
                        <td>{{ $MethodsRessource->section['LABEL'] }}</td>
                        <td><input type="color" class="form-control"  name="COLOR" id="COLOR" value="{{ $MethodsRessource->COLOR }}"></td>
                        <td>{{ $MethodsRessource->service['LABEL'] }}</td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td></td>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td>
                        <td></td>
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Picture</th>
                        <th>Sort</th>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Mask time</th>
                        <th>Capacity</th>
                        <th>Section</th>
                        <th>Color</th>
                        <th>Service</th>
                      </tr>
                    </tfoot>
                  </table>
                  <div class="row">
                    <div class="col-5">
                    {{ $MethodsServices->links() }}
                    </div>
                  <!-- /.row -->
                  </div>
              </div>
            <!-- /.card secondary -->
            </div>

            <div class="col-md-4 card-secondary">
              <div class="card-header">
                <h3 class="card-title">New Ressource</h3>
              </div>
              <form method="POST" action="{{ route('methods.ressource.create')}}" enctype="multipart/form-data">
                @csrf
                  <div class="form-group">
                    <label for="CODE">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="CODE" id="CODE" placeholder="External ID">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="ORDRE">Sort order:</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                      </div>
                      <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="LABEL">Label</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="LABEL" id="LABEL" placeholder="Label">
                    </div>
                  </div>
                  <div class="form-group">
                      <label for="MASK_TIME">Mask time ?</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                        </div>
                        <select class="form-control" name="MASK_TIME" id="MASK_TIME">
                            <option value="2">No</option>
                            <option value="1">Yes</option>
                        </select>
                      </div>
                  </div>
                  <div class="form-group">
                    <label for="CAPACITY">Hour capacity by week</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                      </div>
                      <input type="number" class="form-control" name="CAPACITY" id="CAPACITY" placeholder="110 h/week">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="COLOR">Color</label>
                    <input type="color" class="form-control"  name="COLOR" id="COLOR" >
                  </div>
                  <div class="form-group">
                    <label for="PICTURE">Logo file</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-image"></i></span>
                      </div>
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" name="PICTURE"  id="PICTURE">
                        <label class="custom-file-label" for="PICTURE">Choose file</label>
                      </div>
                      <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
                      </div>
                  </div>
                  <div class="form-group">
                    <label for="section_id">Section</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-industry"></i></span>
                      </div>
                      <select class="form-control" name="section_id" id="section_id">
                        @foreach ($SectionsSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="service_id">Services</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="form-control" name="service_id" id="service_id">
                        @forelse ($ServicesSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                        @empty
                        <option value="">No service</option>
                        @endforelse
                      </select>
                    </div>
                  </div>
                <!-- /.form-group -->
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-danger">Submit</button>
                </div>
            </form>
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

      <div class="tab-pane" id="Section">
        <div class="card card-primary">
          <div class="card-body">
            <x-infocalloutComponent note="The Sections function allows you to define all the sections making up your company, i.e. the physical zones where work stations and operators are grouped together according to their job and cost."  />
            <div class="row">
              <div class="col-md-8 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Section type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Sort</th>
                        <th>External ID</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>Color</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsSections as $MethodsSection)
                      <tr>
                        <td>{{ $MethodsSection->ORDRE }}</td>
                        <td>{{ $MethodsSection->CODE }}</td>
                        <td>{{ $MethodsSection->LABEL }}</td>
                        <td>{{ $MethodsSection->UserManagement['name'] }}</td>
                        <td><input type="color" class="form-control"  name="COLOR" id="COLOR" value="{{ $MethodsSection->COLOR }}"></td>
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
                        <td></td> 
                        <td></td>
                        <td></td>
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-4 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Section</h3>
                  </div>
                  <form  method="POST" action="{{ route('methods.section.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="ORDRE">Sort order:</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                        </div>
                        <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="10">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="COLOR">Color</label>
                      <input type="color" class="form-control"  name="COLOR" id="COLOR" >
                    </div>
                    
                    <div class="form-group">
                      <label for="user_id">User management</label>
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

      <div class="tab-pane" id="Location">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Location in workshop list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Ressource</th>
                        <th>Color</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsLocations as $MethodsLocation)
                      <tr>
                        <td>{{ $MethodsLocation->CODE }}</td>
                        <td>{{ $MethodsLocation->LABEL }}</td>
                        <td>{{ $MethodsLocation->ressources['LABEL'] }}</td>
                        <td><input type="color" class="form-control"  name="COLOR" id="COLOR" value="{{ $MethodsLocation->COLOR }}"></td>
                        
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
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Ressource</th>
                        <th>Color</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New location</h3>
                  </div>
                  <form  method="POST" action="{{ route('methods.location.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="COLOR">Color</label>
                      <input type="color" class="form-control"  name="COLOR" id="COLOR" >
                    </div>
                    <div class="form-group">
                      <label for="ressource_id">Ressource</label>
                      <select class="form-control" name="ressource_id" id="ressource_id">
                        @foreach ($RessourcesSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                        @endforeach
                      </select>
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

      <div class="tab-pane" id="Units">
        <div class="card card-primary">
          <div class="card-body">
            <x-infocalloutComponent note="You must enter all units you may have to work with in your business. This may be Parts, Meters, Kilograms, etc."  />
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Units type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsUnits as $MethodsUnit)
                      <tr>
                        <td>{{ $MethodsUnit->CODE }}</td>
                        <td>{{ $MethodsUnit->LABEL }}</td>
                        <td>
                          @if($MethodsUnit->TYPE  == 1) Mass @endif
                          @if($MethodsUnit->TYPE  == 2) Length @endif
                          @if($MethodsUnit->TYPE  == 3) Aera @endif
                          @if($MethodsUnit->TYPE  == 4) Volume @endif
                          @if($MethodsUnit->TYPE  == 5) Other @endif
                        </td>
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
                    <h3 class="card-title">New Units</h3>
                  </div>
                  <form  method="POST" action="{{ route('methods.unit.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                        <label for="TYPE">Type</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                          </div>
                          <select class="form-control" name="TYPE" id="TYPE">
                              <option value="1">Mass</option>
                              <option value="2">Length</option>
                              <option value="3">Aera</option>
                              <option value="4">Volume</option>
                              <option value="5">Other</option>
                          </select>
                        </div>
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

      <div class="tab-pane" id="Families">
        <div class="card card-primary">
          <div class="card-body">
            <x-infocalloutComponent note="Defining subgroups per service allows filtering components at later stages."  />
            
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Famillies type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Service</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsFamilies as $MethodsFamilie)
                      <tr>
                        <td>{{ $MethodsFamilie->CODE }}</td>
                        <td>{{ $MethodsFamilie->LABEL }}</td>
                        <td>{{ $MethodsFamilie->service['LABEL'] }}</td>
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
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Service</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New family</h3>
                  </div>
                  <form  method="POST" action="{{ route('methods.family.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                       <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="service_id">Services</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-list"></i></span>
                        </div>
                        <select class="form-control" name="service_id" id="service_id">
                          @forelse ($ServicesSelect as $item)
                          <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                          @empty
                          <option value="">No service, please add one before</option>
                           @endforelse
                        </select>
                      </div>
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

      <div class="tab-pane" id="Tools">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Tools list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Picture</th>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Etat</th>
                        <th>Cost</th>
                        <th>End Date</th>
                        <th>Qty</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($MethodsTools as $MethodsTool)
                      <tr>
                        <td> 
                          @if($MethodsTool->PICTURE )
                          <img alt="Tool" class="profile-user-img img-fluid img-circle" src="{{ asset('storage/'.$MethodsTool->PICTURE) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsTool->CODE }}</td>
                        <td>{{ $MethodsTool->LABEL }}</td>
                        <td>
                          @if($MethodsTool->ETAT  == 1)Unsed @endif
                          @if($MethodsTool->ETAT  == 2)Used @endif
                        </td>
                        <td>{{ $MethodsTool->COST }}</td>
                        <td>{{ $MethodsTool->END_DATE }}</td>
                        <td>{{ $MethodsTool->QTY }}</td>
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
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Picture</th>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Etat</th>
                        <th>Cost</th>
                        <th>End Date</th>
                        <th>Qty</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New tool</h3>
                  </div>
                  <form  method="POST" action="{{ route('methods.tool.create') }}" class="form-horizontal" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                       <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="ETAT">Statu</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                        </div>
                        <select class="form-control" name="ETAT" id="ETAT">
                          <option value="1">Unused</option>
                          <option value="2">Used</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="COST">Cost</label>
                      <input type="number" class="form-control" name="COST"  id="COST" placeholder="Cost" step=".001">
                    </div>
                    <div class="form-group">
                      <label for="QTY">Quantity</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                        </div>
                        <input type="numer" class="form-control" name="QTY"  id="QTY" placeholder="Qty" >
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="END_DATE">End date</label>
                      <input type="date" class="form-control" name="END_DATE"  id="END_DATE" placeholder="Qty" >
                    </div>
                    <div class="form-group">
                      <label for="PICTURE">Logo file</label>
                      <div class="input-group">
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="far fa-image"></i></span>
                          </div>
                          <div class="custom-file">
                            <input type="file" class="custom-file-input" name="PICTURE"  id="PICTURE">
                            <label class="custom-file-label" for="PICTURE">Choose file</label>
                          </div>
                          <div class="input-group-append">
                            <span class="input-group-text">Upload</span>
                          </div>
                        </div>
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
    
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop