<tr>
    <td>{{ $BOMProduct->ORDER }}</td>
    <td>{{ $BOMProduct->LABEL }}</td>
    <td>{{ $BOMProduct->service['LABEL'] }}</td>
    <td>{{ $BOMProduct->Component['CODE'] }}</td>
    <td>{{ $BOMProduct->QTY }}</td>
    <td>{{ $BOMProduct->UNIT_COST }}</td>
    <td>{{ $BOMProduct->UNIT_PRICE }}</td>
    <td>
      @if($BOMProduct->order_lines_id)
        @if(1 == $BOMProduct->STATU )   <span class="badge badge-info"> Open</span>@endif
        @if(2 == $BOMProduct->STATU )  <span class="badge badge-warning">Started</span>@endif
        @if(3 == $BOMProduct->STATU )  <span class="badge badge-warning">In progress</span>@endif
        @if(4 == $BOMProduct->STATU )   <span class="badge badge-success"> Finished</span>@endif
        @if(5 == $BOMProduct->STATU )  <span class="badge badge-danger">Suspended</span>@endif
        @if(6 == $BOMProduct->STATU )  <span class="badge badge-danger">To RFQ</span>@endif
        @if(7 == $BOMProduct->STATU )  <span class="badge badge-danger">RFQ in progress</span>@endif
        @if(8 == $BOMProduct->STATU )  <span class="badge badge-warning">Outsourced</span>@endif
        @if(9 == $BOMProduct->STATU )  <span class="badge badge-warning">Supplied</span>@endif
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
  