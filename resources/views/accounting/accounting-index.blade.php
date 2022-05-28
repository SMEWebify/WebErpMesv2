@extends('adminlte::page')

@section('title', 'Accounting')

@section('content_header')
    <h1>Accounting</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @include('include.alert-result')
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
              <div class="col-md-6 card-primary">
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
                        <td>{{ $PaymentCondition->code }}</td>
                        <td>{{ $PaymentCondition->label }}</td>
                        <td>{{ $PaymentCondition->number_of_month }}</td>
                        <td>{{ $PaymentCondition->number_of_day }}</td>
                        <td>
                          @if($PaymentCondition->month_end  == 1) Yes @endif
                          @if($PaymentCondition->month_end  == 2) No @endif
                        </td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#PaymentCondition{{ $PaymentCondition->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $PaymentCondition->id }} -->
                          <div class="modal fade" id="PaymentCondition{{ $PaymentCondition->id }}" tabindex="-1" role="dialog" aria-labelledby="PaymentConditionTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $PaymentCondition->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('accouting.paymentCondition.update', ['id' => $PaymentCondition->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $PaymentCondition->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="number_of_month">Number of month</label>
                                      <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="Number of month" value="{{ $PaymentCondition->number_of_month }}">
                                    </div>
                                    <div class="form-group">
                                      <label for="number_of_day">Number of day</label>
                                      <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="Number of day" value="{{ $PaymentCondition->number_of_day }}">
                                    </div>
                                    <div class="form-group">
                                      <label for="month_end">End of month</label>
                                      <select class="form-control" name="month_end" id="month_end">
                                          <option value="2" @if($PaymentCondition->month_end == 2 ) Selected @endif>No</option>
                                          <option value="1" @if($PaymentCondition->month_end == 1 ) Selected @endif>Yes</option>
                                      </select>
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
                        <x-EmptyDataLine col="6" text="No lines found ..."  />
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
                      <label for="number_of_month">Number of month</label>
                      <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="Number of month">
                    </div>
                    <div class="form-group">
                      <label for="number_of_day">Number of day</label>
                      <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="Number of day">
                    </div>
                    <div class="form-group">
                      <label for="month_end">End of month</label>
                      <select class="form-control" name="month_end" id="month_end">
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
              <div class="col-md-6 card-primary">
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
                        <td>{{ $PaymentMethod->code }}</td>
                        <td>{{ $PaymentMethod->label }}</td>
                        <td>{{ $PaymentMethod->code_account }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#PaymentMethod{{ $PaymentMethod->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $PaymentMethod->id }} -->
                          <div class="modal fade" id="PaymentMethod{{ $PaymentMethod->id }}" tabindex="-1" role="dialog" aria-labelledby="PaymentMethodTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $PaymentMethod->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('accouting.paymentMethod.update', ['id' => $PaymentMethod->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $PaymentMethod->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="code_account">Code account</label>
                                      <input type="text" class="form-control" name="code_account"  id="code_account" placeholder="Code account" value="{{ $PaymentMethod->code_account }}">
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
                      <label for="code_account">Code account</label>
                      <input type="text" class="form-control" name="code_account"  id="code_account" placeholder="Code account">
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
            <x-InfocalloutComponent note="You can define as many tax rates as you want, depending on the types of the quoted or sold products / components."  />
            <div class="row">
              <div class="col-md-6 card-primary">
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
                        <td>{{ $VAT->code }}</td>
                        <td>{{ $VAT->label }}</td>
                        <td>{{ $VAT->rate }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#VAT{{ $VAT->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $VAT->id }} -->
                          <div class="modal fade" id="VAT{{ $VAT->id }}" tabindex="-1" role="dialog" aria-labelledby="VATTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $VAT->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('accouting.vat.update', ['id' => $VAT->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $VAT->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="rate">rate</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                        </div>
                                        <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01" value="{{ $VAT->rate }}">
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
                        <x-EmptyDataLine col="7" text="No lines found ..."  />
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
                      <label for="rate">rate</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                        </div>
                        <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01">
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

      <div class="tab-pane" id="AccoutingAllocations">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-primary">
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
                        <td>{{ $Allocation->account }}</td>
                        <td>{{ $Allocation->label }}</td>
                        <td>{{ $Allocation->VAT['label'] }}</td>
                        <td>{{ $Allocation->vat_account }}</td>
                        <td>{{ $Allocation->code_account }}</td>
                        <td>
                          @if($Allocation->type_imputation  == 1) Purchase @endif
                          @if($Allocation->type_imputation  == 2) Purchase (stock) @endif
                          @if($Allocation->type_imputation  == 3) Advance payment @endif
                          @if($Allocation->type_imputation  == 4) Advance payment (with VAT) @endif
                          @if($Allocation->type_imputation  == 5) Other @endif
                          @if($Allocation->type_imputation  == 6) VAT @endif
                        </td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#Allocation{{ $Allocation->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $Allocation->id }} -->
                          <div class="modal fade" id="Allocation{{ $Allocation->id }}" tabindex="-1" role="dialog" aria-labelledby="AllocationTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $Allocation->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('accouting.allocation.update', ['id' => $Allocation->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $Allocation->label }}">
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="accounting_vats_id">VAT</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                        </div>
                                        <select class="form-control" name="accounting_vats_id" id="accounting_vats_id">
                                          @forelse ($VATSelect as $item)
                                          <option value="{{ $item->id }}" @if($Allocation->accounting_vats_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                          @empty
                                          <option value="">Not VAT, please add VAT before</option>
                                          @endforelse
                                        </select>
                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label for="vat_account">VAT account number</label>
                                      <input type="number" class="form-control" name="vat_account"  id="vat_account" placeholder="VAT account number" value="{{ $Allocation->vat_account }}">
                                    </div>
                                    <div class="form-group">
                                      <label for="code_account">Code account</label>
                                      <input type="number" class="form-control" name="code_account"  id="code_account" placeholder="Code account" value="{{ $Allocation->code_account }}">
                                    </div>
                                    <div class="form-group">
                                      <label for="type_imputation">End of month</label>
                                      <select class="form-control" name="type_imputation" id="type_imputation">
                                          <option value="1" @if($Allocation->type_imputation == 1 ) Selected @endif>Purchase</option>
                                          <option value="2" @if($Allocation->type_imputation == 2 ) Selected @endif>Purchase (stock)</option>
                                          <option value="3" @if($Allocation->type_imputation == 3 ) Selected @endif>Advance payment</option>
                                          <option value="4" @if($Allocation->type_imputation == 4 ) Selected @endif>Advance payment (with VAT)</option>
                                          <option value="5" @if($Allocation->type_imputation == 5 ) Selected @endif>Other</option>
                                          <option value="6" @if($Allocation->type_imputation == 6 ) Selected @endif>VAT</option>
                                      </select>
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
                        <x-EmptyDataLine col="7" text="No lines found ..."  />
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
                      <label for="account">Account (or external ID)</label>
                      <input type="text" class="form-control" name="account" id="account" placeholder="Account">
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
                      <label for="accounting_vats_id">VAT</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                        </div>
                        <select class="form-control" name="accounting_vats_id" id="accounting_vats_id">
                          @forelse ($VATSelect as $item)
                          <option value="{{ $item->id }}">{{ $item->label }}</option>
                          @empty
                          <option value="">Not VAT, please add VAT before</option>
                          @endforelse
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="vat_account">VAT account number</label>
                      <input type="number" class="form-control" name="vat_account"  id="vat_account" placeholder="VAT account number">
                    </div>
                    <div class="form-group">
                      <label for="code_account">Code account</label>
                      <input type="number" class="form-control" name="code_account"  id="code_account" placeholder="Code account">
                    </div>
                    <div class="form-group">
                      <label for="type_imputation">End of month</label>
                      <select class="form-control" name="type_imputation" id="type_imputation">
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
              <div class="col-md-6 card-primary">
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
                        <td>{{ $Delevery->code }}</td>
                        <td>{{ $Delevery->label }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button trigger modal -->
                          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#Delevery{{ $Delevery->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $Delevery->id }} -->
                          <div class="modal fade" id="Delevery{{ $Delevery->id }}" tabindex="-1" role="dialog" aria-labelledby="DeleveryTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLongTitle">Update {{ $Delevery->label }}</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <form method="POST" action="{{ route('accouting.delivery.update', ['id' => $Delevery->id]) }}" enctype="multipart/form-data">
                                  @csrf
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="label">Label</label>
                                      <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $Delevery->label }}">
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
                        <x-EmptyDataLine col="3" text="No lines found ..."  />
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
@stop