@extends('adminlte::page')

@section('title', 'Accounting')

@section('content_header')
    <h1>Accounting</h1>
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
      <li class="nav-item"><a class="nav-link active" href="#PaymentCondition" data-toggle="tab">Payment condition</a></li>
      <li class="nav-item"><a class="nav-link" href="#PaymentChoice" data-toggle="tab">Payment choice</a></li>
      <li class="nav-item"><a class="nav-link" href="#VAT" data-toggle="tab">VAT</a></li>
      <li class="nav-item"><a class="nav-link" href="#AccoutingAllocations" data-toggle="tab"> Accouting allocations</a></li>
      <li class="nav-item"><a class="nav-link" href="#Delevery" data-toggle="tab"> Delevery mode</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">

      <div class="tab-pane active" id="PaymentCondition">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Payment condition type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Number of month</th>
                        <th>Number of day</th>
                        <th>End of month</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($PaymentConditions as $PaymentCondition)
                      <tr>
                        <td>{{ $PaymentCondition->CODE }}</td>
                        <td>{{ $PaymentCondition->LABEL }}</td>
                        <td>{{ $PaymentCondition->NUMBER_OF_MONTH }}</td>
                        <td>{{ $PaymentCondition->NUMBER_OF_DAY }}</td>
                        <td>
                           @if($PaymentCondition->MONTH_END  == 1) Yes @endif
                           @if($PaymentCondition->MONTH_END  == 2) No @endif
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
                        <td></td> 
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Number of month</th>
                        <th>Number of day</th>
                        <th>End of month</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Payment condition mode</h3>
                  </div>
                  <form  method="POST" action="{{ route('accouting.paymentCondition.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                    </div>
                    <div class="form-group">
                      <label for="NUMBER_OF_MONTH">Number of month</label>
                      <input type="number" class="form-control" name="NUMBER_OF_MONTH"  id="NUMBER_OF_MONTH" placeholder="Number of month">
                    </div>
                    <div class="form-group">
                      <label for="NUMBER_OF_DAY">Number of day</label>
                      <input type="number" class="form-control" name="NUMBER_OF_DAY"  id="NUMBER_OF_DAY" placeholder="Number of day">
                    </div>
                    <div class="form-group">
                      <label for="MONTH_END">End of month</label>
                      <select class="form-control" name="MONTH_END" id="MONTH_END">
                          <option value="2">No</option>
                          <option value="1">Yes</option>
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

      <div class="tab-pane" id="PaymentChoice">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Payment choice type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Code account</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($PaymentMethods as $PaymentMethod)
                      <tr>
                        <td>{{ $PaymentMethod->CODE }}</td>
                        <td>{{ $PaymentMethod->LABEL }}</td>
                        <td>{{ $PaymentMethod->CODE_ACCOUNT }}</td>
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
                        <th>Code account</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New Payment choice mode</h3>
                  </div>
                  <form  method="POST" action="{{ route('accouting.paymentMethod.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                    </div>
                    <div class="form-group">
                      <label for="CODE_ACCOUNT">Code account</label>
                      <input type="text" class="form-control" name="CODE_ACCOUNT"  id="CODE_ACCOUNT" placeholder="Code account">
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

      <div class="tab-pane" id="VAT">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">VAT type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th>Rate</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($VATs as $VAT)
                      <tr>
                        <td>{{ $VAT->CODE }}</td>
                        <td>{{ $VAT->LABEL }}</td>
                        <td>{{ $VAT->RATE }}</td>
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
                        <th>Rate</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New VAT mode</h3>
                  </div>
                  <form  method="POST" action="{{ route('accouting.vat.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="CODE">External ID</label>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                    </div>
                    <div class="form-group">
                      <label for="RATE">RATE</label>
                      <input type="number" class="form-control" name="RATE"  id="RATE" placeholder="10 %" step=".01">
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

      <div class="tab-pane" id="AccoutingAllocations">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Accouting Allocations type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Account number</th>
                        <th>Desciption</th>
                        <th>VAT type</th>
                        <th>VAT account</th>
                        <th>Code account</th>
                        <th>Type of</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($Allocations as $Allocation)
                      <tr>
                        <td>{{ $Allocation->ACCOUNT }}</td>
                        <td>{{ $Allocation->LABEL }}</td>
                        <td>{{ $Allocation->VAT['LABEL'] }}</td>
                        <td>{{ $Allocation->VAT_ACCOUNT }}</td>
                        <td>{{ $Allocation->CODE_ACCOUNT }}</td>
                        <td>
                          @if($Allocation->TYPE_IMPUTATION  == 1) Purchase @endif
                          @if($Allocation->TYPE_IMPUTATION  == 2) Purchase (stock) @endif
                          @if($Allocation->TYPE_IMPUTATION  == 3) Advance payment @endif
                          @if($Allocation->TYPE_IMPUTATION  == 4) Advance payment (with VAT) @endif
                          @if($Allocation->TYPE_IMPUTATION  == 5) Other @endif
                          @if($Allocation->TYPE_IMPUTATION  == 6) VAT @endif
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
                        <td></td> 
                        <td></td>
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Account number</th>
                        <th>Desciption</th>
                        <th>VAT type</th>
                        <th>VAT account</th>
                        <th>Code account</th>
                        <th>Type of</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New accouting allocations type</h3>
                  </div>
                  <form  method="POST" action="{{ route('accouting.allocation.create') }}" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                      <label for="ACCOUNT">Account (or external ID)</label>
                      <input type="text" class="form-control" name="ACCOUNT" id="ACCOUNT" placeholder="Account">
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                    </div>
                    
                    <div class="form-group">
                       <label for="vat_id">VAT</label>
                      <select class="form-control" name="vat_id" id="vat_id">
                        @foreach ($VATSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="VAT_ACCOUNT">VAT account number</label>
                      <input type="number" class="form-control" name="VAT_ACCOUNT"  id="VAT_ACCOUNT" placeholder="VAT account number">
                    </div>
                    <div class="form-group">
                      <label for="CODE_ACCOUNT">Code account</label>
                      <input type="number" class="form-control" name="CODE_ACCOUNT"  id="CODE_ACCOUNT" placeholder="Code account">
                    </div>
                    <div class="form-group">
                      <label for="TYPE_IMPUTATION">End of month</label>
                      <select class="form-control" name="TYPE_IMPUTATION" id="TYPE_IMPUTATION">
                          <option value="1">Purchase</option>
                          <option value="2">Purchase (stock)</option>
                          <option value="3">Advance payment</option>
                          <option value="4">Advance payment (with VAT)</option>
                          <option value="5">Other</option>
                          <option value="6">VAT</option>
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

      <div class="tab-pane" id="Delevery">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Delevery type list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($Deleverys as $Delevery)
                      <tr>
                        <td>{{ $Delevery->CODE }}</td>
                        <td>{{ $Delevery->LABEL }}</td>
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
                    <tfoot>
                      <tr>
                        <th>External ID</th>
                        <th>Desciption</th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>

              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">New delevery mode</h3>
                  </div>
                  <form  method="POST" action="{{ route('accouting.delivery.create') }}" class="form-horizontal">
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