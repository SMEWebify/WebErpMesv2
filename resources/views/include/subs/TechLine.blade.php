<tr>
    <td>{{ $TechProduct->ORDER }}</td>
    <td>{{ $TechProduct->LABEL }}</td>
    <td>{{ $TechProduct->service['LABEL'] }}</td>
    <td>{{ $TechProduct->SETING_TIME }}</td>
    <td>{{ $TechProduct->UNIT_TIME }}</td>
    <td>{{ $TechProduct->UNIT_COST }}</td>
    <td>{{ $TechProduct->UNIT_PRICE }}</td>
    <td>
      @if($TechProduct->order_lines_id)
        @if(1 == $TechProduct->STATU )   <span class="badge badge-info"> Open</span>@endif
        @if(2 == $TechProduct->STATU )  <span class="badge badge-warning">Started</span>@endif
        @if(3 == $TechProduct->STATU )  <span class="badge badge-success">In progress</span>@endif
        @if(4 == $TechProduct->STATU )   <span class="badge badge-info"> Finished</span>@endif
        @if(5 == $TechProduct->STATU )  <span class="badge badge-warning">Suspended</span>@endif
        @if(6 == $TechProduct->STATU )  <span class="badge badge-success">To RFQ</span>@endif
        @if(7 == $TechProduct->STATU )  <span class="badge badge-warning">RFQ in progress</span>@endif
        @if(8 == $TechProduct->STATU )  <span class="badge badge-success">Outsourced</span>@endif
        @if(9 == $TechProduct->STATU )  <span class="badge badge-success">Supplied</span>@endif
      @else
        Not for quote
      @endif
    </td>
    <td class=" py-0 align-middle">
      <!-- Modal -->
      @include('include.modals.modal-TechCut-update')
      <!-- End Modal -->
      <div class="btn-group btn-group-sm">
        <a href="#" data-toggle="modal" data-target="#TechnicalCutUpdateModal{{ $TechProduct->id }}" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
      </div>
      <div class="btn-group btn-group-sm">
        <a href="{{ route('task.delete', ['id_type'=> $id_type, 'id_page'=> $id_page, 'id_task' => $TechProduct->id])}}" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
      </div>
    </td>
</tr>
