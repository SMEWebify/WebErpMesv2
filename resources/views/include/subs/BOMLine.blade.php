<tr>
    <td>{{ $BOMProduct->ORDER }}</td>
    <td>{{ $BOMProduct->label }}</td>
    <td>{{ $BOMProduct->service['label'] }}</td>
    <td>{{ $BOMProduct->Component['code'] }}</td>
    <td>{{ $BOMProduct->qty }}</td>
    <td>{{ $BOMProduct->unit_cost }}</td>
    <td>{{ $BOMProduct->unit_price }}</td>
    <td>
      @if($BOMProduct->order_lines_id)
      {{ $BOMProduct->status['title'] }}
      @else
        Not for quote
      @endif
    </td>
    <td class=" py-0 align-middle">
      <!-- Modal -->
      @include('include.modals.modal-BOM-update')
      <!-- End Modal -->
      <div class="btn-group btn-group-sm">
        <a href="#" data-toggle="modal" data-target="#BOMUpdateModal{{ $BOMProduct->id }}" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
      </div>
      <div class="btn-group btn-group-sm">
        <a href="{{ route('task.delete', ['id_type'=> $id_type, 'id_page'=> $id_page, 'id_task' => $BOMProduct->id])}}" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
      </div>
    </td>
  </tr>
  