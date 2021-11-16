@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
    

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Companies list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
          New companie
        </button>
      </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="ModalCompanie" tabindex="-1" role="dialog" aria-labelledby="ModalCompanieTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ModalCompanieTitle">New companie</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('companies.store')}}" enctype="multipart/form-data">
              @csrf
                <div class="row">
                  <div class="col-4">
                    <label for="CODE">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                    </div>
                  </div>
                  <div class="col-4">
                    <label for="LABEL">Name of company</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-building"></i></span>
                      </div>
                     <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Name of company">
                    </div>
                  </div>
                  <div class="col-4">
                    <label for="user_id">Technical manager</label>
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
                      <input type="text" class="form-control"  name="WEBSITE" id="WEBSITE" placeholder="Web site link">
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="FBSITE" id="FBSITE" placeholder="Facebook link">
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                      </div>
                      <input type="text" class="form-control"  name="TWITTERSITE" id="TWITTERSITE" placeholder="Twitter link">
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                      </div>
                     <input type="text" class="form-control"  name="LKDSITE" id="LKDSITE" placeholder="Linkedin link">
                    </div>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <label for="SIREN">Administrative information</label>
                </div>
                <div class="row">
                  <div class="col-3">
                    <input type="text" class="form-control" name="SIREN" id="SIREN" placeholder="Siren">
                  </div>
                  <div class="col-3">
                    <input type="text" class="form-control" name="APE" id="APE" placeholder="APE code">
                  </div>
                  <div class="col-3">
                    <input type="text" class="form-control" name="TVA_INTRA" id="TVA_INTRA" placeholder="VAT number">
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
                      <select class="form-control" name="statu_CLIENT" id="statu_CLIENT">
                        <option value="1">Inactive</option>
                        <option value="2">Active</option>
                        <option value="3">Prospect</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-3">
                    <label for="discount">Discount :</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                      </div>
                      <input type="number" class="form-control" name="DISCOUNT" id="DISCOUNT" placeholder="Discount">
                    </div>
                  </div>
                  <div class="col-3">
                    <label for="COMPTE_GEN_CLIENT">General Account</label>
                    <input type="number" class="form-control" name="COMPTE_GEN_CLIENT" id="COMPTE_GEN_CLIENT" placeholder="General Account">
                  </div>
                  <div class="col-3">
                    <label for="COMPTE_AUX_CLIENT">Auxiliary Account</label>
                    <input type="number" class="form-control" name="COMPTE_AUX_CLIENT" id="COMPTE_AUX_CLIENT" placeholder="Auxiliary account">
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
                      <select class="form-control" name="statu_FOUR" id="statu_FOUR">
                        <option value="1">Inactive</option>
                        <option value="2">Active</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-3">
                    <label for="statu_FOUR">Reception control</label>
                    <select class="form-control" name="RECEPT_CONTROLE" id="RECEPT_CONTROLE">
                      <option value="1">Yes</option>
                      <option value="2">No</option>
                    </select>
                  </div>
                  <div class="col-3">
                    <label for="COMPTE_GEN_FOUR">General Account</label>
                    <input type="number" class="form-control" id="COMPTE_GEN_FOUR" name="COMPTE_GEN_FOUR" placeholder="General Account">
                  </div>
                  <div class="col-3">
                    <label for="COMPTE_AUX_FOUR">Auxiliary Account</label>
                    <input type="number" class="form-control" id="COMPTE_AUX_FOUR" name="COMPTE_AUX_FOUR"  placeholder="Auxiliary account">
                  </div>
                  
                </div>
                <hr>
                <div class="row">
                  <div class="col-12">
                    <label>Comment</label>
                    <textarea class="form-control" rows="3" name="COMMENT"  placeholder="Enter ..."></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  <button type="Submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- End Modal -->

@stop

@section('right-sidebar')

@section('content')

                <div class="card">
                  <div class="card-body">
                    @if($errors->count())
                    <div class="alert alert-danger">
                      <ul>
                      @foreach ( $errors->all() as $message)
                       <li> {{ $message }}</li>
                      @endforeach
                      </ul>
                    </div>
                    @endif
                    <div  id="companies_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        <div class="col-sm-12">
                          <table id="companies" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                            <tr>
                              <th>Code</th>
                              <th>Label</th>
                              <th>Created At</th>
                              <th>Statu client</th>
                              <th>Statu supplier</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                              @foreach ($Companieslist as $Companie)
                              <tr>
                                <td>{{ $Companie->CODE }}</td>
                                <td>{{ $Companie->LABEL }}</td>
                                <td>{{ $Companie->GetPrettyCreatedAttribute() }}</td>
                                <td>
                                  @if($Companie->statu_CLIENT == 2 )
                                  <i class="fas fa-check-double"></i>
                                  @elseif($Companie->statu_CLIENT == 3 )
                                  <i class="fas fa-check"></i>
                                  @else
                                  <i class="fas fa-times"></i>
                                  @endif
                                </td>
                                <td>
                                  @if($Companie->statu_FOUR == 2 )
                                  <i class="fas fa-check"></i>
                                  @else
                                  <i class="fas fa-times"></i>
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
                              @endforeach
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
                      </div>
                      <!-- /.row -->
                    </div>
                  </div>
                  <!-- /.card-body -->
                </div>
                <!-- /.card -->

@stop
                  
 @section('css')
 @stop
                  
@section('js')

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.11.0/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.html5.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.print.min.js"></script>
  <script> 

  $(document).ready( function () {
    $("#companies").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#companies_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop