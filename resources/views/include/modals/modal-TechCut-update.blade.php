  <div class="modal fade" id="#TechnicalCutUpdateModal{{ $TechProduct->id }}" tabindex="-1" role="dialog" aria-labelledby="TechnicalCutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <form method="POST" action="{{ route('task.update', ['id' => $id_page]) }}" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
            <div class="row">
              <div class="col-4">
                    <label for="ORDER">Sort order</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                      </div>
                      <input type="number" class="form-control" name="ORDER" id="ORDER" placeholder="Order">
                      <input type="hidden" class="form-control" name="{{ $id_type }}" value="{{ $infoLine['id_line']  }}">
                      <input type="hidden" class="form-control" name="id" value="{{ $TechProduct->id }}">
                      <input type="hidden" name="qty"  id="qty"  value="{{ $infoLine['qty_line']  }}" value=".001">
                    </div>
              </div>
              <div class="col-4">
                    <label for="methods_services_id">Services</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="methods_services_id form-control" name="methods_services_id" id="methods_services_id">
                        <option>Select Services</option>
                        @foreach ($TechServicesSelect as $item)
                        <option value="{{ $item->id }}"  data-type="{{ $item->type }}" data-txt="{{ $item->label }}">{{ $item->code }}</option>
                        @endforeach
                      </select>
                      <input type="hidden" class="form-control" name="type" id="type" value="{{ $TechProduct->type }}">
                    </div>
              </div>
              <div class="col-4">
                      <label for="label">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                      </div>
              </div>
            </div>
            <div class="row">
              <div class="col-3">
                    <label for="seting_time">Setting time</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                      </div>
                      <input type="number" class="form-control" name="seting_time"  id="seting_time" placeholder="Setting time" step=".001">
                    </div>
              </div>
              <div class="col-3">
                    <label for="unit_time">Unit time</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                      </div>
                      <input type="number" class="form-control" name="unit_time"  id="unit_time" placeholder="Unit time" step=".001">
                    </div>
              </div>
              <div class="col-3">
                    <label for="unit_cost">Unit cost</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text">{{ $Factory->curency }}</span>
                      </div>
                      <input type="number" class="form-control" name="unit_cost"  id="unit_cost" placeholder="Unit cost" step=".001">
                    </div>
              </div>
              <div class="col-3">
                    <label for="unit_price">Unit price</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text">{{ $Factory->curency }}</span>
                      </div>
                      <input type="number" class="form-control" name="unit_price"  id="unit_price" placeholder="Unit time" step=".001">
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
  