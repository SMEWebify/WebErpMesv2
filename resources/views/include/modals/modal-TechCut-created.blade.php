<div class="modal fade" id="TechnicalCutModal{{ $infoLine['id_line']  }}" tabindex="-1" role="dialog" aria-labelledby="TechnicalCutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <form method="POST" action="{{ $route }}" enctype="multipart/form-data">
          <div class="modal-body">
              @csrf
              <div class="row">
                <div class="col-4">
                  <label for="ORDER">Sort order</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                    </div>
                    <input type="number" class="form-control" name="ORDER" id="ORDER" placeholder="Order">
                    <input type="hidden" name="{{ $id_type }}" value="{{ $infoLine['id_line']  }}">
                    <input type="hidden" name="status_id" value="{{ $status_id }}">
                    <input type="hidden" name="qty"  id="qty"  value="{{ $infoLine['qty_line']  }}" value=".001">
                    
                  </div>
                </div>
                <div class="col-4">
                  <label for="methods_services_id">Services</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-list"></i></span>
                    </div>
                    <select class="methods_services_id form-control" name="methods_services_id" id="methods_services_id_{{ $infoLine['id_line']  }}">
                      <option>Select Services</option>
                      @foreach ($TechServicesSelect as $item)
                      <option value="{{ $item->id }}"  data-type-{{ $infoLine['id_line']  }}="{{ $item->type }}" data-txt-{{ $infoLine['id_line']  }}="{{ $item->label }}">{{ $item->code }}</option>
                      @endforeach
                    </select>
                    <!-- script or change label -->
                    <script>
                      $("#methods_services_id_" + {{ $infoLine['id_line']  }}).on('change',function(){
                        var val = $(this).val();
                        var txt = $(this).find('option:selected').data("txt-" + {{ $infoLine['id_line']  }});
                        var type = $(this).find('option:selected').data("type-" + {{ $infoLine['id_line']  }});
                        $("#LABEL_TechnicalCut_" + {{ $infoLine['id_line']  }}).val( txt );
                        $("#type_TechnicalCut_" + {{ $infoLine['id_line']  }}).val( type );
                    });
                    </script>
                    <!-- end -->
                    <input type="hidden" class="form-control" name="type" id="type_TechnicalCut_{{ $infoLine['id_line']  }}">
                  </div>
                </div>
                <div class="col-4">
                    <label for="label">Label</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="LABEL_TechnicalCut_{{ $infoLine['id_line']  }}" placeholder="Label">
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
  