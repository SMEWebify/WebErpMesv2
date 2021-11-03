<div class="card card-secondary">
    <div class="card-header">
      <h3 class="card-title">Technical cut</h3>
      <div class="card-tools">
        <div class="btn-group btn-group-sm">
          <a href="#" data-toggle="modal" data-target="#TechnicalCutModal{{ $id }}" class="btn btn-warning">Add Tech Cut</a>
        </div>
        <!-- Modal -->
        @include('include.modal-TechCut')
        <!-- End Modal -->
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-minus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    
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
              <th>Action</th>
          </tr>
          </thead>
          <tbody>
            @forelse ($task as $TechProduct)
              @if(preg_match('(1|7)', $TechProduct->TYPE) === 1)
                @include('include.TechLine')
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
        <!-- Modal -->
      @include('include.modal-BOM')
      <!-- End Modal -->
        <div class="btn-group btn-group-sm">
            <a href="#" data-toggle="modal" data-target="#BOMModal{{ $id }}" class="btn btn-success">Add BOM</a>
        </div>
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-minus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
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
              <th>Action</th>
          </tr>
          </thead>
          <tbody>
            @forelse ($task as $BOMProduct)
              @if(preg_match('(3|4|5|6|8)', $BOMProduct->TYPE) === 1)
                @include('include.BOMLine')
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
              <th>Action</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <!-- /.card-body -->
  </div>