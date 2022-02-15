  <div class="modal fade" id="BOMUpdateModal{{ $BOMProduct->id }}" tabindex="-1" role="dialog" aria-labelledby="BOMModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <form method="POST" action="{{ route('task.update', ['id' => $id_page]) }}" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <div class="row">
              <div class="col-3">
                <label for="ORDER">Sort order</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                  </div>
                  <input type="number" class="form-control" name="ORDER" id="ORDER" placeholder="Order" value="{{ $BOMProduct->ORDER }}">
                  <input type="hidden" class="form-control" name="{{ $id_type }}" value="{{ $infoLine['id_line']  }}">
                  <input type="hidden" class="form-control" name="id" value="{{ $BOMProduct->id }}">
                </div>
              </div>
              <div class="col-3">
                  <label for="methods_services_id">Services</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-list"></i></span>
                    </div>
                    <select class="methods_services_id form-control" name="methods_services_id" id="methods_services_id_BOM">
                      <option>Select Services</option>
                      @foreach ($BOMServicesSelect as $item)
                      <option value="{{ $item->id }}"  @if($item->id == $BOMProduct->methods_services_id ) Selected @endif class="{{ $item->id }}" data-type="{{ $item->type }}"  data-txt="{{ $item->label }}">{{ $item->code }}</option>
                      @endforeach
                    </select>
                    <input type="hidden" class="form-control" name="type" id="type_BOM" value="{{ $BOMProduct->type }}">
                  </div>
              </div>
              <div class="col-3">
                <label for="component_id">Component</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                  </div>
                  <select class="component_id form-control" name="component_id" id="component_id">
                    <option>Select Component</option>
                    @foreach ($ProductSelect as $item)
                    <option value="{{ $item->id }}" class="{{ $item->methods_services_id }}" @if($item->id == $BOMProduct->component_id ) Selected @endif>{{ $item->code }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-3">
                  <label for="label">Label</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control" name="label"  id="LABEL_BOM" placeholder="Label" value="{{ $BOMProduct->label }}">
                  </div>
              </div>
            </div>
            <div class="row">
              <div class="col-3">
                <label for="qty">Quantity</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-times"></i></span>
                  </div>
                  <input type="number" class="form-control" name="qty"  id="qty" placeholder="Quantity" step=".001" value="{{ $BOMProduct->qty }}">
                </div>
              </div>
              <div class="col-3">
                <label for="unit_cost">Unit cost</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text">{{ $Factory->curency }}</span>
                  </div>
                  <input type="number" class="form-control" name="unit_cost"  id="unit_cost" placeholder="Unit cost" step=".001" value="{{ $BOMProduct->unit_cost }}">
                </div>
              </div>
              <div class="col-3">
                <label for="unit_price">Unit price</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text">{{ $Factory->curency }}</span>
                  </div>
                  <input type="number" class="form-control" name="unit_price"  id="unit_price" placeholder="Unit time" step=".001" value="{{ $BOMProduct->unit_price }}">
                </div>
              </div>
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
  