@extends('adminlte::page')

@section('title', 'Methods')

@section('content_header')
    <h1>Methods</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @include('include.alert-result')

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
            <x-InfocalloutComponent note="In the Type scrolling list, indicate whether the service is productive (manufacturing operation) or actually involves the procurement of raw materials or supplies, is a service completed externally, i.e. by a subcontractor, or is a composed component. The service type is then used to filter data when creating a list of tasks, a bill of materials, etc. 
            For raw materials, a distinction is also made between bars, plates and blocks. The screen used to detail raw materials may look different, depending on the raw material type."  />
            <div class="row">
              <div class="col-md-8 card-primary">
                <div class="card-header">
                    <h3 class="card-title">Service type list</h3>
                </div>
                <div class="card-body p-0">
                  <table  class="table">
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
                          @if($MethodsService->picture )
                          <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'.$MethodsService->picture) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsService->ordre }}</td>
                        <td>{{ $MethodsService->code }}</td>
                        <td>{{ $MethodsService->label }}</td>
                        <td>
                          @if($MethodsService->type  == 1)Productive @endif
                          @if($MethodsService->type  == 2)Raw material @endif
                          @if($MethodsService->type  == 3)Raw material (Sheet) @endif
                          @if($MethodsService->type  == 4)Raw material (Profil) @endif
                          @if($MethodsService->type  == 5)Raw material (block) @endif
                          @if($MethodsService->type  == 6)Supplies @endif
                          @if($MethodsService->type  == 7)Sub-contracting @endif
                          @if($MethodsService->type  == 8)Composed component @endif
                        </td>
                        <td>{{ $MethodsService->hourly_rate }}</td>
                        <td>{{ $MethodsService->margin }}</td>
                        <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsService->color }}"></td>
                        <td class="py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalService{{ $MethodsService->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsService->id }} -->
                          <div class="modal fade" id="ModalService{{ $MethodsService->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalServiceTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsService->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.service.update', ['id' => $MethodsService->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="ordre">Sort order:</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="ordre" id="ordre" placeholder="Order" min="0" value="{{ $MethodsService->ordre }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="label" id="label" placeholder="Label" value="{{ $MethodsService->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="type">Type</label>
                                        <div class="input-group">
                                          <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                          </div>
                                          <select class="form-control" name="type" id="type">
                                              <option value="1" @if($MethodsService->type == 1 ) Selected @endif>Productive</option>
                                              <option value="2" @if($MethodsService->type == 2 ) Selected @endif>Raw material</option>
                                              <option value="3" @if($MethodsService->type == 3 ) Selected @endif>Raw material (Sheet)</option>
                                              <option value="4" @if($MethodsService->type == 4 ) Selected @endif>Raw material (Profil)</option>
                                              <option value="5" @if($MethodsService->type == 5 ) Selected @endif>Raw material (block)</option>
                                              <option value="6" @if($MethodsService->type == 6 ) Selected @endif>Purchase</option>
                                              <option value="7" @if($MethodsService->type == 7 ) Selected @endif>Sub-contracting</option>
                                              <option value="8" @if($MethodsService->type == 8 ) Selected @endif>Composed component</option>
                                          </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="hourly_rate">Hourly rate</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}/H</span>
                                        </div>
                                        <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 €/H" step=".001" value="{{ $MethodsService->hourly_rate }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="margin">Margin :</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="margin" id="margin" placeholder="10%" step=".001" value="{{ $MethodsService->hourly_rate }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="color">Color</label>
                                      <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsService->color }}">
                                    </div>
                                    <div class="form-group">
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
                                    <div class="form-group">
                                      <label for="compannie_id">Supplier</label>
                                        <select class="form-control" name="compannie_id" id="compannie_id">
                                          <option value="NULL">None</option>
                                          @foreach ($CompaniesSelect as $item)
                                          <option value="{{ $item->id }}"  @if($MethodsService->compannie_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                          @endforeach
                                        </select>
                                    </div>
                                    <!-- /.form-group -->
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                        <x-EmptyDataLine col="9" text="No lines found ..."  />
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Picture</th>
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
                  <form method="POST" action="{{ route('methods.service.create') }}" enctype="multipart/form-data">
                    @csrf
                      <div class="form-group">
                        <label for="code">External ID</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="code" id="code" placeholder="External ID">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="ordre">Sort order:</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                          </div>
                          <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="Order">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="label">Label</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                        </div>
                      </div>
                      <div class="form-group">
                          <label for="type">Type</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="type" id="type">
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
                        <label for="hourly_rate">Hourly rate</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text">{{ $Factory->curency }}/H</span>
                          </div>
                          <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 €/H" step=".001">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="margin">Margin :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                          </div>
                          <input type="number" class="form-control" name="margin" id="margin" placeholder="10%" step=".001">
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="color">Color</label>
                        <input type="color" class="form-control"  name="color" id="color" >
                      </div>
                      <div class="form-group">
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
                      <div class="form-group">
                        <label for="compannie_id">Supplier</label>
                          <select class="form-control" name="compannie_id" id="compannie_id">
                            <option value="NULL">None</option>
                            @foreach ($CompaniesSelect as $item)
                            <option value="{{ $item->id }}">{{ $item->label }}</option>
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
            <x-InfocalloutComponent note="Depending on your working method, resources may be employees or machines or a mixture of both. In any case, you must indicate for each of them the maximum number of working hours per day as well as the services that they can complete."  />
            <div class="row">
              <div class="col-md-8 card-primary">
                <div class="card-header">
                    <h3 class="card-title">Ressources type list</h3>
                </div>
                <div class="card-body p-0">
                  <table  class="table">
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
                          @if($MethodsRessource->picture )
                          <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/ressources/'.$MethodsRessource->picture) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsRessource->ordre }}</td>
                        <td>{{ $MethodsRessource->code }}</td>
                        <td>{{ $MethodsRessource->label }}</td>
                        <td>
                          @if($MethodsRessource->mask_time == 1  ) Yes @endif
                          @if($MethodsRessource->mask_time == 2  ) No @endif
                        </td>
                        <td>{{ $MethodsRessource->capacity }} h/w</td>
                        <td>{{ $MethodsRessource->section['label'] }}</td>
                        <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsRessource->color }}"></td>
                        <td>{{ $MethodsRessource->service['label'] }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalRessource{{ $MethodsRessource->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsRessource->id }} -->
                          <div class="modal fade" id="ModalRessource{{ $MethodsRessource->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalRessourceTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsRessource->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.ressource.update', ['id' => $MethodsRessource->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="ordre">Sort order:</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="ordre" id="ordre" placeholder="Order" min="0" value="{{ $MethodsRessource->ordre }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="label" id="label" placeholder="Label" value="{{ $MethodsRessource->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="mask_time">Mask time ?</label>
                                        <div class="input-group">
                                          <div class="input-group-prepend">
                                              <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                                          </div>
                                          <select class="form-control" name="mask_time" id="mask_time">
                                              <option value="2" @if($MethodsRessource->mask_time == 2  ) Selected @endif>No</option>
                                              <option value="1" @if($MethodsRessource->mask_time == 1  ) Selected @endif>Yes</option>
                                          </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="capacity">Hour capacity by week</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="capacity" id="capacity" placeholder="110 h/week" value="{{ $MethodsRessource->capacity }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="color">Color</label>
                                      <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsRessource->color }}">
                                    </div>
                                    <div class="form-group">
                                      
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
                                    <div class="form-group">
                                      <label for="section_id">Section</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-industry"></i></span>
                                        </div>
                                        <select class="form-control" name="section_id" id="section_id">
                                          @forelse ($SectionsSelect as $item)
                                          <option value="{{ $item->id }}" @if($MethodsRessource->section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                          @empty
                                          <option value="">No section, please add before</option>
                                          @endforelse
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
                                          <option value="{{ $item->id }}" @if($MethodsRessource->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                          @empty
                                          <option value="">No service</option>
                                          @endforelse
                                        </select>
                                      </div>
                                    </div>
                                    <!-- /.form-group -->
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                        <x-EmptyDataLine col="10" text="No lines found ..."  />
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
                    <label for="code">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="code" id="code" placeholder="External ID">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="ordre">Sort order:</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                      </div>
                      <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="Order">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">Label</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="label" id="label" placeholder="Label">
                    </div>
                  </div>
                  <div class="form-group">
                      <label for="mask_time">Mask time ?</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                        </div>
                        <select class="form-control" name="mask_time" id="mask_time">
                            <option value="2">No</option>
                            <option value="1">Yes</option>
                        </select>
                      </div>
                  </div>
                  <div class="form-group">
                    <label for="capacity">Hour capacity by week</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                      </div>
                      <input type="number" class="form-control" name="capacity" id="capacity" placeholder="110 h/week">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="color">Color</label>
                    <input type="color" class="form-control"  name="color" id="color" >
                  </div>
                  <div class="form-group">
                    
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
                  <div class="form-group">
                    <label for="section_id">Section</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-industry"></i></span>
                      </div>
                      <select class="form-control" name="section_id" id="section_id">
                        @forelse ($SectionsSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                        @empty
                        <option value="">No section, please add before</option>
                        @endforelse
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
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
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
            <x-InfocalloutComponent note="The Sections function allows you to define all the sections making up your company, i.e. the physical zones where work stations and operators are grouped together according to their job and cost."  />
            <div class="row">
              <div class="col-md-8 card-primary">
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
                        <td>{{ $MethodsSection->ordre }}</td>
                        <td>{{ $MethodsSection->code }}</td>
                        <td>{{ $MethodsSection->label }}</td>
                        <td>{{ $MethodsSection->UserManagement['name'] }}</td>
                        <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsSection->color }}"></td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalSection{{ $MethodsSection->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsSection->id }} -->
                          <div class="modal fade" id="ModalSection{{ $MethodsSection->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalSectionTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsSection->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.section.update', ['id' => $MethodsSection->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="ordre">Sort order:</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10" min="0" value="{{ $MethodsSection->ordre }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $MethodsSection->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="color">Color</label>
                                      <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsSection->color }}">
                                    </div>
                                    
                                    <div class="form-group">
                                      <label for="user_id">User management</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" name="user_id" id="user_id">
                                          @foreach ($userSelect as $item)
                                          <option value="{{ $item->id }}" @if($MethodsSection->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                          @endforeach
                                        </select>
                                      </div>
                                    </div>
                                    <!-- /.form-group -->
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="6" text="No lines found ..."  />
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Sort</th>
                        <th>External ID</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>Color</th>
                        <th></th>
                      </tr>
                    </tfoot>
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
                      <label for="ordre">Sort order:</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                        </div>
                        <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="10">
                      </div>
                    </div>
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
                    <div class="form-group">
                      <label for="color">Color</label>
                      <input type="color" class="form-control"  name="color" id="color" >
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
              <div class="col-md-6 card-primary">
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
                        <td>{{ $MethodsLocation->code }}</td>
                        <td>{{ $MethodsLocation->label }}</td>
                        <td>{{ $MethodsLocation->ressources['label'] }}</td>
                        <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsLocation->color }}"></td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalLocation{{ $MethodsLocation->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsLocation->id }} -->
                          <div class="modal fade" id="ModalLocation{{ $MethodsLocation->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalLocationTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsLocation->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.location.update', ['id' => $MethodsLocation->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $MethodsLocation->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="color">Color</label>
                                      <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsLocation->color }}">
                                    </div>
                                    <div class="form-group">
                                      <label for="ressource_id">Ressource</label>
                                      <select class="form-control" name="ressource_id" id="ressource_id">
                                        @foreach ($RessourcesSelect as $item)
                                        <option value="{{ $item->id }}" @if($MethodsLocation->ressource_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                    <!-- /.form-group -->
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="4">
                          <div class="flex justify-center items-center">
                              <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No Location found ...</span>
                          </div>
                        </td>
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
                    <div class="form-group">
                      <label for="color">Color</label>
                      <input type="color" class="form-control"  name="color" id="color" >
                    </div>
                    <div class="form-group">
                      <label for="ressource_id">Ressource</label>
                      <select class="form-control" name="ressource_id" id="ressource_id">
                        @foreach ($RessourcesSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
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
            <x-InfocalloutComponent note="You must enter all units you may have to work with in your business. This may be Parts, Meters, Kilograms, etc."  />
            <div class="row">
              <div class="col-md-6 card-primary">
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
                        <td>{{ $MethodsUnit->code }}</td>
                        <td>{{ $MethodsUnit->label }}</td>
                        <td>
                          @if($MethodsUnit->type  == 1) Mass @endif
                          @if($MethodsUnit->type  == 2) Length @endif
                          @if($MethodsUnit->type  == 3) Aera @endif
                          @if($MethodsUnit->type  == 4) Volume @endif
                          @if($MethodsUnit->type  == 5) Other @endif
                        </td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalUnit{{ $MethodsUnit->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsUnit->id }} -->
                          <div class="modal fade" id="ModalUnit{{ $MethodsUnit->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalUnitTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsUnit->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.unit.update', ['id' => $MethodsUnit->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $MethodsUnit->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="type">Type</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                                        </div>
                                        <select class="form-control" name="type" id="type">
                                            <option value="1" @if($MethodsUnit->type == 1  ) Selected @endif>Mass</option>
                                            <option value="2" @if($MethodsUnit->type == 2  ) Selected @endif>Length</option>
                                            <option value="3" @if($MethodsUnit->type == 3  ) Selected @endif>Aera</option>
                                            <option value="4" @if($MethodsUnit->type == 4  ) Selected @endif>Volume</option>
                                            <option value="5" @if($MethodsUnit->type == 5  ) Selected @endif>Other</option>
                                        </select>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="9" text="No lines found ..."  />
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>External ID</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th></th>
                      </tr>
                    </tfoot>
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
                    <div class="form-group">
                        <label for="type">Type</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                          </div>
                          <select class="form-control" name="type" id="type">
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
            <x-InfocalloutComponent note="Defining subgroups per service allows filtering components at later stages."  />
            
            <div class="row">
              <div class="col-md-6 card-primary">
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
                        <td>{{ $MethodsFamilie->code }}</td>
                        <td>{{ $MethodsFamilie->label }}</td>
                        <td>{{ $MethodsFamilie->service['label'] }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalFamilie{{ $MethodsFamilie->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsFamilie->id }} -->
                          <div class="modal fade" id="ModalFamilie{{ $MethodsFamilie->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalFamilieTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsFamilie->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.family.update', ['id' => $MethodsFamilie->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $MethodsFamilie->label }}">
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
                                          <option value="{{ $item->id }}" @if($MethodsFamilie->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                          @empty
                                          <option value="">No service, please add one before</option>
                                          @endforelse
                                        </select>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="4" text="No lines found ..."  />
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
                    <div class="form-group">
                      <label for="service_id">Services</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-list"></i></span>
                        </div>
                        <select class="form-control" name="service_id" id="service_id">
                          @forelse ($ServicesSelect as $item)
                          <option value="{{ $item->id }}">{{ $item->label }}</option>
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
              <div class="col-md-6 card-primary">
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
                          @if($MethodsTool->picture )
                          <img alt="Tool" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/tools/'. $MethodsTool->picture) }}">
                          @endif
                        </td>
                        <td>{{ $MethodsTool->code }}</td>
                        <td>{{ $MethodsTool->label }}</td>
                        <td>
                          @if($MethodsTool->ETAT  == 1)Unsed @endif
                          @if($MethodsTool->ETAT  == 2)Used @endif
                        </td>
                        <td>{{ $MethodsTool->cost }}</td>
                        <td>{{ $MethodsTool->end_date }}</td>
                        <td>{{ $MethodsTool->qty }}</td>
                        <td class="py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalTool{{ $MethodsTool->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $MethodsTool->id }} -->
                          <div class="modal fade" id="ModalTool{{ $MethodsTool->id }}" tabindex="-1" role="dialog" aria-labelledby="ModalToolTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $MethodsTool->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('methods.tool.update', ['id' => $MethodsTool->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $MethodsTool->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="ETAT">Statu</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                        </div>
                                        <select class="form-control" name="ETAT" id="ETAT">
                                          <option value="1" @if($MethodsTool->ETAT == 1 ) Selected @endif>Unused</option>
                                          <option value="2" @if($MethodsTool->ETAT == 2  ) Selected @endif>Used</option>
                                        </select>
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="cost">Cost</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control" name="cost"  id="cost" placeholder="Cost" step=".001" value="{{ $MethodsTool->cost }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="qty">Quantity</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                                        </div>
                                        <input type="numer" class="form-control" name="qty"  id="qty" placeholder="Qty" value="{{ $MethodsTool->qty }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="end_date">End date</label>
                                      <input type="date" class="form-control" name="end_date"  id="end_date" placeholder="Qty" value="{{ $MethodsTool->end_date }}" >
                                    </div>
                                    <div class="form-group">
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
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="8" text="No lines found ..."  />
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
                      <label for="cost">Cost</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">{{ $Factory->curency }}</span>
                        </div>
                        <input type="number" class="form-control" name="cost"  id="cost" placeholder="Cost" step=".001">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="qty">Quantity</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                        </div>
                        <input type="numer" class="form-control" name="qty"  id="qty" placeholder="Qty" >
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="end_date">End date</label>
                      <input type="date" class="form-control" name="end_date"  id="end_date" placeholder="Qty" >
                    </div>
                    <div class="form-group">
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
@stop