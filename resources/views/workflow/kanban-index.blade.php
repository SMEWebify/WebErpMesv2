@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Kanban board</h1>
      </div>
    </div>

@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-body">


  <div class="row">
      <div class="col-12 col-lg-6 col-xl-3">
          <div class="card card-row card-secondary">
              <div class="card-header">
                  <div class="card-actions float-right">
                      <div class="dropdown show">
                          <a href="#" data-toggle="dropdown" data-display="static">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal align-middle">
                                  <circle cx="12" cy="12" r="1"></circle>
                                  <circle cx="19" cy="12" r="1"></circle>
                                  <circle cx="5" cy="12" r="1"></circle>
                              </svg>
                          </a>

                          <div class="dropdown-menu dropdown-menu-right">
                              <a class="dropdown-item" href="#">Action</a>
                              <a class="dropdown-item" href="#">Another action</a>
                              <a class="dropdown-item" href="#">Something else here</a>
                          </div>
                      </div>
                  </div>
                  <h5 class="card-title">Backlog</h5>
              </div>
              <div class="card-body p-3">

                  <div class="card mb-5 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input" checked="">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar6.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-5 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input" checked="">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar1.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-5 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar2.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-5 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>In hac habitasse platea dictumst. Curabitur at lacus ac velit ornare lobortis. Curabitur a felis tristique.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar3.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-5 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar4.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-lg-6 col-xl-3">
          <div class="card card-row card-primary">
              <div class="card-header">
                  <div class="card-actions float-right">
                      <div class="dropdown show">
                          <a href="#" data-toggle="dropdown" data-display="static">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal align-middle">
                                  <circle cx="12" cy="12" r="1"></circle>
                                  <circle cx="19" cy="12" r="1"></circle>
                                  <circle cx="5" cy="12" r="1"></circle>
                              </svg>
                          </a>

                          <div class="dropdown-menu dropdown-menu-right">
                              <a class="dropdown-item" href="#">Action</a>
                              <a class="dropdown-item" href="#">Another action</a>
                              <a class="dropdown-item" href="#">Something else here</a>
                          </div>
                      </div>
                  </div>
                  <h5 class="card-title">In Progress</h5>
              </div>
              <div class="card-body">

                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar6.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar7.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar8.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>In hac habitasse platea dictumst. Curabitur at lacus ac velit ornare lobortis. Curabitur a felis tristique.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar1.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-lg-6 col-xl-3">
          <div class="card card-row card-warning">
              <div class="card-header">
                  <div class="card-actions float-right">
                      <div class="dropdown show">
                          <a href="#" data-toggle="dropdown" data-display="static">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal align-middle">
                                  <circle cx="12" cy="12" r="1"></circle>
                                  <circle cx="19" cy="12" r="1"></circle>
                                  <circle cx="5" cy="12" r="1"></circle>
                              </svg>
                          </a>

                          <div class="dropdown-menu dropdown-menu-right">
                              <a class="dropdown-item" href="#">Action</a>
                              <a class="dropdown-item" href="#">Another action</a>
                              <a class="dropdown-item" href="#">Something else here</a>
                          </div>
                      </div>
                  </div>
                  <h5 class="card-title">On hold</h5>
              </div>
              <div class="card-body">

                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>In hac habitasse platea dictumst. Curabitur at lacus ac velit ornare lobortis. Curabitur a felis tristique.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar2.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar3.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar4.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-lg-3 col-xl-3">
          <div class="card card-row card-success">
              <div class="card-header">
                  <div class="card-actions float-right">
                      <div class="dropdown show">
                          <a href="#" data-toggle="dropdown" data-display="static">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal align-middle">
                                  <circle cx="12" cy="12" r="1"></circle>
                                  <circle cx="19" cy="12" r="1"></circle>
                                  <circle cx="5" cy="12" r="1"></circle>
                              </svg>
                          </a>

                          <div class="dropdown-menu dropdown-menu-right">
                              <a class="dropdown-item" href="#">Action</a>
                              <a class="dropdown-item" href="#">Another action</a>
                              <a class="dropdown-item" href="#">Something else here</a>
                          </div>
                      </div>
                  </div>
                  <h5 class="card-title">Completed</h5>
              </div>
              <div class="card-body">

                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar6.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>In hac habitasse platea dictumst. Curabitur at lacus ac velit ornare lobortis. Curabitur a felis tristique.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar7.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc, eu sollicitudin urna dolor sagittis.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar8.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
                  <div class="card mb-3 bg-light">
                      <div class="card-body p-3">
                          <div class="float-right mr-n2">
                              <label class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input">
                                  <span class="custom-control-label"></span>
                              </label>
                          </div>
                          <p>Curabitur ligula sapien, tincidunt non, euismod vitae, posuere imperdiet, leo. Maecenas malesuada.</p>
                          <div class="float-right mt-n1">
                              <img src="https://bootdey.com/img/Content/avatar/avatar1.png" width="32" height="32" class="rounded-circle" alt="Avatar">
                          </div>
                          <a class="btn btn-outline-primary btn-sm" href="#">View</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
</div>
@stop
                  
 @section('css')
    
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.0/css/jquery.dataTables.min.css">
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.0/css/buttons.dataTables.min.css">
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
    $("#quotes").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#quotes_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop