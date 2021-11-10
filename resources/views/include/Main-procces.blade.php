<div class="card card-secondary">
    <div class="card-header">
      <h3 class="card-title">Technical cut</h3>
      <div class="card-tools">
        <div class="btn-group btn-group-sm">
          <a href="#" data-toggle="modal" data-target="#TechnicalCutModal{{ $id_line }}" class="btn btn-warning">Add Tech Cut</a>
        </div>
        
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-minus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <!-- Modal -->
    @include('include.modals.modal-TechCut-created')
    <!-- End Modal -->
    <div class="card-body">
      <div class="row">
        <table id="example1" class="table table-bordered table-striped">
          <thead>
          <tr>
              <th>Sort order</th>
              <th>Label</th>
              <th>Service</th>
              <th>Setting time</th>
              <th>Unit time</th>
              <th>Unit cost</th>
              <th>Unit price</th>
              <th>Statu</th>
              <th>Action</th>
          </tr>
          </thead>
          <tbody>
            @forelse ($task as $TechProduct)
              @if(preg_match('(1|7)', $TechProduct->TYPE) === 1)
                @include('include.subs.TechLine')
              @endif
            @empty
            <tr>
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
              <th>Sort order</th>
              <th>Label</th>
              <th>Service</th>
              <th>Setting time</th>
              <th>Unit time</th>
              <th>Unit cost</th>
              <th>Unit price</th>
              <th>Statu</th>
              <th>Action</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <!-- /.card-body -->
  </div>
  
  <div class="card card-secondary">
    <div class="card-header">
      <h3 class="card-title">Bill of materials</h3>
      <div class="card-tools">
        <div class="btn-group btn-group-sm">
            <a href="#" data-toggle="modal" data-target="#BOMModal{{ $id_line }}" class="btn btn-success">Add BOM</a>
        </div>
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-minus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <!-- Modal -->
    @include('include.modals.modal-BOM-created')
    <!-- End Modal -->
    <div class="card-body">
      <div class="row">
        <table id="example1" class="table table-bordered table-striped">
          <thead>
          <tr>
              <th>Sort order</th>
              <th>Label</th>
              <th>Service</th>
              <th>Component</th>
              <th>Quantity</th>
              <th>Unit cost</th>
              <th>Unit price</th>
              <th>Statu</th>
              <th>Action</th>
          </tr>
          </thead>
          <tbody>
            @forelse ($task as $BOMProduct)
              @if(preg_match('(3|4|5|6|8)', $BOMProduct->TYPE) === 1)
                @include('include.subs.BOMLine')
              @endif
            @empty
            <tr>
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
              <th>Sort order</th>
              <th>Label</th>
              <th>Service</th>
              <th>Component</th>
              <th>Quantity</th>
              <th>Unit cost</th>
              <th>Unit price</th>
              <th>Statu</th>
              <th>Action</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <!-- /.card-body -->
  </div>